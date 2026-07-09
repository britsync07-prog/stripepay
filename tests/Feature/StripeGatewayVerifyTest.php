<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Payments\Gateways\StripeGateway;
use App\Services\Payment\StripeCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session;
use Tests\TestCase;

/**
 * The security-critical part of Stripe money-in: verify() must only credit a
 * session that actually belongs to THIS consultation. A leaked/known session id
 * from a same-priced consultation must never be replayable against another one.
 */
class StripeGatewayVerifyTest extends TestCase
{
    use RefreshDatabase;

    private function consultation(int $amount = 5000): ConsultationRequest
    {
        return ConsultationRequest::create([
            'amount' => $amount,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
        ]);
    }

    private function fakeSession(Session $session): void
    {
        $this->mock(StripeCheckout::class, fn ($m) => $m
            ->shouldReceive('retrieveSession')->once()->andReturn($session));
    }

    public function test_verify_rejects_a_paid_session_bound_to_a_different_consultation(): void
    {
        $consultation = $this->consultation();

        $session = Session::constructFrom([
            'id' => 'cs_evil',
            'payment_status' => 'paid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'metadata' => ['consultation_request_uuid' => 'a-different-consultation'],
        ]);

        $this->fakeSession($session);

        $this->assertNull((new StripeGateway)->verify($consultation, ['session_id' => 'cs_evil']));
    }

    public function test_verify_rejects_a_paid_session_with_no_binding_metadata(): void
    {
        $consultation = $this->consultation();

        $session = Session::constructFrom([
            'id' => 'cs_nometa',
            'payment_status' => 'paid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'metadata' => [],
        ]);

        $this->fakeSession($session);

        $this->assertNull((new StripeGateway)->verify($consultation, ['session_id' => 'cs_nometa']));
    }

    public function test_verify_rejects_an_unpaid_session_even_when_correctly_bound(): void
    {
        $consultation = $this->consultation();

        $session = Session::constructFrom([
            'id' => 'cs_unpaid',
            'payment_status' => 'unpaid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'metadata' => ['consultation_request_uuid' => $consultation->uuid],
        ]);

        $this->fakeSession($session);

        $this->assertNull((new StripeGateway)->verify($consultation, ['session_id' => 'cs_unpaid']));
    }

    public function test_verify_accepts_a_paid_session_bound_to_this_consultation(): void
    {
        $consultation = $this->consultation();

        $session = Session::constructFrom([
            'id' => 'cs_ok',
            'payment_status' => 'paid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'payment_intent' => 'pi_123',
            'metadata' => ['consultation_request_uuid' => $consultation->uuid],
            'customer_details' => [
                'name' => 'Buyer One',
                'email' => 'buyer@example.com',
                'phone' => '15550000',
                'address' => ['country' => 'US'],
            ],
        ]);

        $this->fakeSession($session);

        $verified = (new StripeGateway)->verify($consultation, ['session_id' => 'cs_ok']);

        $this->assertNotNull($verified);
        $this->assertSame('cs_ok', $verified->reference);
        $this->assertSame(5000, $verified->amountMinor);
        $this->assertSame('USD', $verified->currency);
        $this->assertSame('buyer@example.com', $verified->payerEmail);
        $this->assertSame('US', $verified->payerCountry);
    }

    public function test_verify_returns_null_without_a_session_id(): void
    {
        $this->assertNull((new StripeGateway)->verify($this->consultation(), []));
    }

    public function test_verify_extracts_the_fee_and_net_from_the_balance_transaction(): void
    {
        $consultation = $this->consultation();

        $session = Session::constructFrom([
            'id' => 'cs_fees',
            'payment_status' => 'paid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'metadata' => ['consultation_request_uuid' => $consultation->uuid],
            'customer_details' => ['email' => 'buyer@example.com'],
            'payment_intent' => [
                'id' => 'pi_1',
                'object' => 'payment_intent',
                'latest_charge' => [
                    'id' => 'ch_1',
                    'object' => 'charge',
                    'balance_transaction' => [
                        'id' => 'txn_1',
                        'object' => 'balance_transaction',
                        'fee' => 175,
                        'net' => 4825,
                    ],
                ],
            ],
        ]);

        $this->fakeSession($session);

        $verified = (new StripeGateway)->verify($consultation, ['session_id' => 'cs_fees']);

        $this->assertNotNull($verified);
        $this->assertSame(175, $verified->feeMinor);
        $this->assertSame(4825, $verified->netMinor); // gross 5000 - fee 175
        // The payment intent id is still extracted from the expanded object.
        $this->assertSame('pi_1', $verified->raw['payment_intent']);
    }

    public function test_verify_converts_a_cross_currency_fee_back_to_the_charged_currency(): void
    {
        // Real case: a $12,000 USD charge on an AED-settling account. The balance
        // transaction is in AED; the fee must be converted back to USD so it
        // reconciles with the USD gross.
        $consultation = $this->consultation(1200000);

        $session = Session::constructFrom([
            'id' => 'cs_aed',
            'payment_status' => 'paid',
            'amount_total' => 1200000,
            'currency' => 'usd',
            'metadata' => ['consultation_request_uuid' => $consultation->uuid],
            'customer_details' => ['email' => 'buyer@example.com'],
            'payment_intent' => [
                'id' => 'pi_aed',
                'object' => 'payment_intent',
                'latest_charge' => [
                    'id' => 'ch_aed',
                    'object' => 'charge',
                    'balance_transaction' => [
                        'id' => 'txn_aed',
                        'object' => 'balance_transaction',
                        'currency' => 'aed',
                        'amount' => 4407300,
                        'fee' => 226861,
                        'net' => 4180439,
                        'exchange_rate' => 3.67275,
                    ],
                ],
            ],
        ]);

        $this->fakeSession($session);

        $verified = (new StripeGateway)->verify($consultation, ['session_id' => 'cs_aed']);

        $this->assertNotNull($verified);
        $this->assertSame(61769, $verified->feeMinor); // 226861 AED / 3.67275 ≈ $617.69
        $this->assertSame(1138231, $verified->netMinor); // gross 1,200,000 - fee
        // Fee + net reconciles exactly with the USD gross.
        $this->assertSame(1200000, $verified->feeMinor + $verified->netMinor);
    }
}

<?php

namespace Tests\Unit\Payments;

use App\Models\ConsultationRequest;
use App\Payments\Gateways\PaypalGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaypalGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);
    }

    private function consultation(string $uuid = 'u-123', string $currency = 'USD', int $amount = 5000): ConsultationRequest
    {
        return new ConsultationRequest(['uuid' => $uuid, 'currency' => $currency, 'amount' => $amount]);
    }

    /** Fakes the read-order (GET) used for pre-capture validation + the capture. */
    private function fakePaypal(string $customId = 'u-123', string $value = '50.00', string $captureStatus = 'COMPLETED'): void
    {
        $order = [
            'id' => 'ORDER1',
            'status' => 'APPROVED',
            'purchase_units' => [[
                'custom_id' => $customId,
                'amount' => ['value' => $value, 'currency_code' => 'USD'],
            ]],
        ];

        $capture = [
            'status' => $captureStatus,
            'payer' => [
                'name' => ['given_name' => 'Ann', 'surname' => 'Lee'],
                'email_address' => 'ann@example.com',
                'phone' => ['phone_number' => ['national_number' => '7700900000']],
                'address' => ['country_code' => 'AE'],
            ],
            'purchase_units' => [[
                'custom_id' => $customId,
                'payments' => ['captures' => [[
                    'id' => 'CAP1',
                    'amount' => ['value' => $value, 'currency_code' => 'USD'],
                ]]],
            ]],
        ];

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/checkout/orders/*/capture' => Http::response($capture),
            '*/v2/checkout/orders/*' => Http::response($order),
        ]);
    }

    private function assertNotCaptured(): void
    {
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/capture'));
    }

    public function test_metadata(): void
    {
        $gateway = new PaypalGateway;

        $this->assertSame('paypal', $gateway->key());
        $this->assertSame('PayPal', $gateway->label());
        $this->assertSame('buttons', $gateway->mode());
    }

    public function test_is_configured_reflects_credentials(): void
    {
        config(['services.paypal.client_id' => null, 'services.paypal.client_secret' => null]);
        $this->assertFalse((new PaypalGateway)->isConfigured());

        config(['services.paypal.client_id' => 'cid', 'services.paypal.client_secret' => 'sec']);
        $this->assertTrue((new PaypalGateway)->isConfigured());
    }

    public function test_public_config_exposes_client_id_and_uppercased_currency(): void
    {
        $config = (new PaypalGateway)->publicConfig($this->consultation(currency: 'eur'));

        $this->assertSame('cid', $config['clientId']);
        $this->assertSame('EUR', $config['currency']);
    }

    public function test_verify_normalizes_a_completed_capture(): void
    {
        $this->fakePaypal();

        $payment = (new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']);

        $this->assertNotNull($payment);
        $this->assertSame('ORDER1', $payment->reference);
        $this->assertSame(5000, $payment->amountMinor);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame('Ann Lee', $payment->payerName);
        $this->assertSame('ann@example.com', $payment->payerEmail);
        $this->assertSame('7700900000', $payment->payerPhone);
        $this->assertSame('AE', $payment->payerCountry);
    }

    public function test_capture_sends_a_well_formed_empty_json_body(): void
    {
        // Regression: PayPal rejects an empty/`[]` body with MALFORMED_REQUEST_JSON;
        // the capture call must send a well-formed empty object `{}`.
        $this->fakePaypal();

        (new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']);

        Http::assertSent(fn (Request $request) => ! str_contains($request->url(), '/capture')
            || $request->body() === '{}');
    }

    public function test_a_mismatched_custom_id_is_rejected_without_capturing(): void
    {
        $this->fakePaypal(customId: 'SOMEONE-ELSE');

        $this->assertNull((new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']));
        $this->assertNotCaptured(); // money is never taken
    }

    public function test_a_mismatched_amount_is_rejected_without_capturing(): void
    {
        // The approved order is for $5.00, the consultation expects $50.00.
        $this->fakePaypal(value: '5.00');

        $this->assertNull((new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']));
        $this->assertNotCaptured(); // money is never taken
    }

    public function test_an_order_without_a_binding_is_rejected_without_capturing(): void
    {
        // No custom_id on the order → fail closed (never credit the wrong consultation).
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v2/checkout/orders/*/capture' => Http::response(['status' => 'COMPLETED']),
            '*/v2/checkout/orders/*' => Http::response([
                'id' => 'ORDER1',
                'status' => 'APPROVED',
                'purchase_units' => [['amount' => ['value' => '50.00', 'currency_code' => 'USD']]],
            ]),
        ]);

        $this->assertNull((new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']));
        $this->assertNotCaptured();
    }

    public function test_verify_returns_null_when_capture_not_completed(): void
    {
        $this->fakePaypal(captureStatus: 'PENDING');

        $this->assertNull((new PaypalGateway)->verify($this->consultation(), ['orderID' => 'ORDER1']));
    }

    public function test_verify_returns_null_without_an_order_id(): void
    {
        $this->assertNull((new PaypalGateway)->verify($this->consultation(), []));
    }
}

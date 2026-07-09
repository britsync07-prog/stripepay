<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaypalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ConsultationRequest $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');

        // PayPal configured; Stripe intentionally left unconfigured for this test.
        config([
            'services.paypal.client_id' => 'test-client-id',
            'services.paypal.client_secret' => 'test-secret',
            'services.paypal.mode' => 'sandbox',
            'services.paypal.webhook_id' => 'WH-TEST',
        ]);

        $plan = Plan::create([
            'name' => 'Strategy Call',
            'slug' => 'strategy-call',
            'price' => 5000,
            'currency' => 'USD',
        ]);

        $this->consultation = ConsultationRequest::create([
            'plan_id' => $plan->id,
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
    }

    /** PayPal's sandbox API, faked. */
    private function fakePaypal(string $captureStatus = 'COMPLETED', string $value = '50.00'): void
    {
        $uuid = $this->consultation->uuid;

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3200]),
            '*/v2/checkout/orders/*/capture' => Http::response([
                'status' => $captureStatus,
                'payer' => [
                    'name' => ['given_name' => 'Test', 'surname' => 'Buyer'],
                    'email_address' => 'buyer@example.com',
                    'address' => ['country_code' => 'AE'],
                ],
                'purchase_units' => [[
                    'custom_id' => $uuid,
                    'payments' => ['captures' => [[
                        'id' => 'CAP123',
                        'amount' => ['value' => $value, 'currency_code' => 'USD'],
                        'seller_receivable_breakdown' => [
                            'gross_amount' => ['value' => $value, 'currency_code' => 'USD'],
                            'paypal_fee' => ['value' => '2.00', 'currency_code' => 'USD'],
                            'net_amount' => ['value' => '48.00', 'currency_code' => 'USD'],
                        ],
                    ]]],
                ]],
            ]),
            // Read-order (GET) used to validate before capturing.
            '*/v2/checkout/orders/*' => Http::response([
                'id' => 'ORDER123',
                'status' => 'APPROVED',
                'purchase_units' => [[
                    'custom_id' => $uuid,
                    'amount' => ['value' => $value, 'currency_code' => 'USD'],
                ]],
            ]),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDER123', 'status' => 'CREATED']),
            '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
    }

    public function test_creating_a_session_returns_the_paypal_order_id(): void
    {
        $this->fakePaypal();

        $this->postJson(route('consultation.pay.session', [$this->consultation, 'paypal']))
            ->assertOk()
            ->assertJson(['mode' => 'buttons', 'id' => 'ORDER123']);
    }

    public function test_capturing_an_approved_order_records_the_payment(): void
    {
        $this->fakePaypal();

        $this->postJson(
            route('consultation.pay.capture', [$this->consultation, 'paypal']),
            ['orderID' => 'ORDER123'],
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('payments', [
            'consultation_request_id' => $this->consultation->id,
            'gateway' => 'paypal',
            'reference' => 'ORDER123',
            'status' => 'paid',
            'amount' => 5000,
            'fee' => 200,
            'net' => 4800,
        ]);

        $this->assertTrue($this->consultation->fresh()->isPaid());

        // The paying client is provisioned and marked verified.
        $client = User::where('email', 'buyer@example.com')->first();
        $this->assertNotNull($client);
        $this->assertTrue($client->hasVerifiedEmail());
    }

    public function test_paying_verifies_a_pre_linked_unverified_lead(): void
    {
        // A custom plan request links the client up-front, but unverified.
        $lead = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => null,
        ]);
        $lead->assignRole('client');
        $this->consultation->update([
            'user_id' => $lead->id,
            'email' => 'buyer@example.com',
        ]);
        $this->assertFalse($lead->hasVerifiedEmail());

        $this->fakePaypal();

        $this->postJson(
            route('consultation.pay.capture', [$this->consultation, 'paypal']),
            ['orderID' => 'ORDER123'],
        )->assertOk();

        // Payment must verify the previously-unverified lead.
        $this->assertTrue($lead->fresh()->hasVerifiedEmail());
    }

    public function test_amount_mismatch_is_rejected_before_any_money_is_captured(): void
    {
        // The approved order is for $5.00 but the consultation expects $50.00.
        $this->fakePaypal(value: '5.00');

        $this->postJson(
            route('consultation.pay.capture', [$this->consultation, 'paypal']),
            ['orderID' => 'ORDER123'],
        )->assertStatus(422);

        // No capture call was made — the buyer is never charged — and nothing recorded.
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/capture'));
        $this->assertDatabaseMissing('payments', ['reference' => 'ORDER123']);
        $this->assertFalse($this->consultation->fresh()->isPaid());
    }

    public function test_webhook_is_idempotent_with_the_on_page_capture(): void
    {
        $this->fakePaypal();

        // 1) on-page capture records the payment
        $this->postJson(
            route('consultation.pay.capture', [$this->consultation, 'paypal']),
            ['orderID' => 'ORDER123'],
        )->assertOk();

        // 2) the webhook for the same order arrives — must NOT double-record
        $event = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP123',
                'custom_id' => $this->consultation->uuid,
                'amount' => ['value' => '50.00', 'currency_code' => 'USD'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'ORDER123']],
            ],
        ];

        $this->postJson('/paypal/webhook', $event, [
            'paypal-transmission-id' => 'tx-1',
            'paypal-transmission-sig' => 'sig',
            'paypal-cert-url' => 'https://example.test/cert',
            'paypal-auth-algo' => 'SHA256withRSA',
            'paypal-transmission-time' => '2026-06-16T00:00:00Z',
        ])->assertOk();

        $this->assertSame(1, Payment::where('reference', 'ORDER123')->count());
    }

    public function test_webhook_rejects_an_unverifiable_event(): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token']),
            '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'FAILURE']),
        ]);

        $this->postJson('/paypal/webhook', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['custom_id' => $this->consultation->uuid],
        ], ['paypal-transmission-id' => 'tx-1'])->assertStatus(400);

        $this->assertDatabaseCount('payments', 0);
    }
}

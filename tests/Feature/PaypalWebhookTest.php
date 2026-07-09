<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\PaymentFlaggedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaypalWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        config([
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
            'services.paypal.webhook_id' => 'WH-TEST',
        ]);

        $plan = Plan::create(['name' => 'Call', 'slug' => 'call-'.uniqid(), 'price' => 5000, 'currency' => 'USD']);
        $consultation = ConsultationRequest::create([
            'plan_id' => $plan->id, 'amount' => 5000, 'currency' => 'USD', 'status' => 'paid',
        ]);

        $this->payment = Payment::create([
            'consultation_request_id' => $consultation->id,
            'gateway' => 'paypal',
            'reference' => 'ORDER1',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
            'payload' => ['capture_id' => 'CAP1', 'transaction_id' => 'CAP1'],
        ]);

        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'token']),
            '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
    }

    private function postEvent(array $event): TestResponse
    {
        return $this->postJson('/paypal/webhook', $event, ['paypal-transmission-id' => 'tx-1']);
    }

    public function test_a_refund_event_flags_the_payment_refunded(): void
    {
        $this->postEvent([
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND1',
                'links' => [['rel' => 'up', 'href' => 'https://api-m.paypal.com/v2/payments/captures/CAP1']],
            ],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Refunded, $this->payment->fresh()->status);
        Notification::assertSentTo($this->admin, PaymentFlaggedNotification::class);
    }

    public function test_a_partial_refund_does_not_mark_the_payment_fully_refunded(): void
    {
        // Paid $50.00; PayPal refunds only $20.00 → must NOT flip to Refunded.
        $this->postEvent([
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND_PARTIAL',
                'amount' => ['value' => '20.00', 'currency_code' => 'USD'],
                'links' => [['rel' => 'up', 'href' => 'https://api-m.paypal.com/v2/payments/captures/CAP1']],
            ],
        ])->assertOk();

        $payment = $this->payment->fresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(2000, (int) ($payment->payload['refunded_total'] ?? 0));
        Notification::assertSentTo($this->admin, PaymentFlaggedNotification::class);
    }

    public function test_a_partial_refund_is_idempotent_on_the_refund_id(): void
    {
        $event = [
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND_DUP',
                'amount' => ['value' => '20.00', 'currency_code' => 'USD'],
                'links' => [['rel' => 'up', 'href' => 'https://api/v2/payments/captures/CAP1']],
            ],
        ];

        $this->postEvent($event)->assertOk();
        $this->postEvent($event)->assertOk();

        // The same refund event must be counted once, not twice.
        $this->assertSame(2000, (int) ($this->payment->fresh()->payload['refunded_total'] ?? 0));
    }

    public function test_a_full_amount_refund_marks_the_payment_refunded(): void
    {
        $this->postEvent([
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND_FULL',
                'amount' => ['value' => '50.00', 'currency_code' => 'USD'],
                'links' => [['rel' => 'up', 'href' => 'https://api/v2/payments/captures/CAP1']],
            ],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Refunded, $this->payment->fresh()->status);
    }

    public function test_a_denied_event_flags_the_payment_failed(): void
    {
        $this->postEvent([
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => ['id' => 'CAP1'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Failed, $this->payment->fresh()->status);
    }

    public function test_a_dispute_event_flags_the_payment_disputed(): void
    {
        $this->postEvent([
            'event_type' => 'CUSTOMER.DISPUTE.CREATED',
            'resource' => ['disputed_transactions' => [['seller_transaction_id' => 'CAP1']]],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Disputed, $this->payment->fresh()->status);
    }

    public function test_flagging_is_idempotent(): void
    {
        $this->payment->update(['status' => 'refunded']);

        $this->postEvent([
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => ['links' => [['rel' => 'up', 'href' => 'https://api/v2/payments/captures/CAP1']]],
        ])->assertOk();

        Notification::assertNotSentTo($this->admin, PaymentFlaggedNotification::class);
    }

    public function test_an_unrelated_event_is_a_noop(): void
    {
        $this->postEvent([
            'event_type' => 'BILLING.SUBSCRIPTION.CREATED',
            'resource' => [],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Paid, $this->payment->fresh()->status);
        Notification::assertNothingSent();
    }
}

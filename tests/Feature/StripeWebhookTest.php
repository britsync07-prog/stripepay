<?php

namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\StripeCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Stripe\Checkout\Session;
use Stripe\Event;
use Tests\TestCase;

/**
 * The Stripe money-in backstop: a signature-verified webhook that records a
 * settled checkout via the same idempotent funnel the browser-return uses, so a
 * payment is never silently lost when the buyer's browser fails to return.
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private ConsultationRequest $consultation;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        User::factory()->create()->assignRole('admin');

        config(['services.stripe.key' => 'pk', 'services.stripe.secret' => 'sk']);

        $plan = Plan::create(['name' => 'Call', 'slug' => 'call-'.uniqid(), 'price' => 5000, 'currency' => 'USD']);
        $this->consultation = ConsultationRequest::create([
            'plan_id' => $plan->id, 'amount' => 5000, 'currency' => 'USD', 'status' => 'pending',
        ]);
    }

    private function paidSession(?string $uuid = null, array $overrides = []): Session
    {
        return Session::constructFrom([
            'id' => 'cs_hook',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'amount_total' => 5000,
            'currency' => 'usd',
            'payment_intent' => 'pi_1',
            'metadata' => ['consultation_request_uuid' => $uuid ?? $this->consultation->uuid],
            'customer_details' => [
                'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '1', 'address' => ['country' => 'US'],
            ],
            ...$overrides,
        ]);
    }

    private function eventFor(Session $session): Event
    {
        return Event::constructFrom(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);
    }

    /**
     * Mock the Stripe wrapper. The webhook locates the consultation from the
     * (signature-verified) event session, then RE-retrieves the session from
     * Stripe via verify() — so $retrieved is the authoritative source the binding
     * and amount guards run against. Defaults to echoing the event's session.
     */
    private function fakeStripe(?Event $event, ?Session $retrieved = null): void
    {
        $this->mock(StripeCheckout::class, function ($m) use ($event, $retrieved) {
            $m->shouldReceive('verifyWebhook')->andReturn($event);

            $session = $retrieved ?? ($event && $event->type === 'checkout.session.completed' ? $event->data->object : null);
            if ($session) {
                $m->shouldReceive('retrieveSession')->andReturn($session);
            }
        });
    }

    private function postWebhook(): TestResponse
    {
        return $this->postJson('/stripe/webhook', ['anything' => true], ['Stripe-Signature' => 'sig']);
    }

    public function test_a_completed_session_records_the_payment(): void
    {
        $this->fakeStripe($this->eventFor($this->paidSession()));

        $this->postWebhook()->assertOk();

        $this->assertDatabaseHas('payments', [
            'consultation_request_id' => $this->consultation->id,
            'gateway' => 'stripe',
            'reference' => 'cs_hook',
            'amount' => 5000,
            'status' => 'paid',
        ]);
        $this->assertTrue($this->consultation->fresh()->isPaid());
    }

    /**
     * The reason the webhook exists: the buyer's browser never returned, so the
     * webhook must do the full job the browser-return would have — record AND
     * provision the client (which downstream creates the project + invoice).
     */
    public function test_the_backstop_provisions_the_client_when_the_browser_never_returned(): void
    {
        $this->fakeStripe($this->eventFor($this->paidSession()));

        $this->postWebhook()->assertOk();

        $this->assertDatabaseHas('payments', ['reference' => 'cs_hook', 'status' => 'paid']);
        $this->assertDatabaseHas('users', ['email' => 'buyer@example.com']);
        $this->assertDatabaseHas('projects', ['consultation_request_id' => $this->consultation->id]);
        $this->assertDatabaseHas('invoices', ['consultation_request_id' => $this->consultation->id]);

        $client = User::where('email', 'buyer@example.com')->firstOrFail();
        $this->assertTrue($client->hasRole('client'));
        $this->assertNotNull($client->email_verified_at);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $this->fakeStripe(null);

        $this->postWebhook()->assertStatus(400);
        $this->assertSame(0, Payment::count());
    }

    public function test_recording_is_idempotent_with_an_already_paid_consultation(): void
    {
        $this->consultation->update(['status' => 'paid', 'paid_at' => now()]);
        Payment::create([
            'consultation_request_id' => $this->consultation->id,
            'gateway' => 'stripe', 'reference' => 'cs_hook', 'amount' => 5000,
            'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);

        $this->fakeStripe($this->eventFor($this->paidSession()));

        $this->postWebhook()->assertOk();

        $this->assertSame(1, Payment::where('reference', 'cs_hook')->count());
    }

    public function test_an_unrelated_event_type_is_a_noop(): void
    {
        $event = Event::constructFrom(['type' => 'payment_intent.created', 'data' => ['object' => []]]);
        $this->fakeStripe($event);

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
    }

    /**
     * The event locates this consultation, but the session Stripe hands back on
     * re-retrieval is bound to a DIFFERENT consultation. The binding guard must
     * reject it — a known/leaked session id must never credit another request.
     */
    public function test_a_session_bound_to_another_consultation_is_rejected(): void
    {
        $locator = $this->paidSession(); // metadata → this consultation, so it is loaded
        $tampered = $this->paidSession('not-this-consultation-uuid'); // what Stripe actually returns
        $this->fakeStripe($this->eventFor($locator), $tampered);

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
        $this->assertFalse($this->consultation->fresh()->isPaid());
    }

    /** The amount actually charged must match the consultation; otherwise nothing is recorded. */
    public function test_a_session_charged_a_different_amount_is_not_recorded(): void
    {
        $locator = $this->paidSession();
        $wrongAmount = $this->paidSession(null, ['amount_total' => 9999]);
        $this->fakeStripe($this->eventFor($locator), $wrongAmount);

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
        $this->assertFalse($this->consultation->fresh()->isPaid());
    }

    /** checkout.session.completed can fire before an async payment settles — don't record an unpaid session. */
    public function test_an_unpaid_session_is_not_recorded(): void
    {
        $locator = $this->paidSession();
        $unpaid = $this->paidSession(null, ['payment_status' => 'unpaid']);
        $this->fakeStripe($this->eventFor($locator), $unpaid);

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
    }

    public function test_a_session_without_metadata_is_a_noop(): void
    {
        $this->fakeStripe($this->eventFor($this->paidSession(null, ['metadata' => []])));

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
    }

    public function test_an_unknown_consultation_uuid_is_a_noop(): void
    {
        $this->fakeStripe($this->eventFor($this->paidSession('00000000-0000-0000-0000-000000000000')));

        $this->postWebhook()->assertOk();
        $this->assertSame(0, Payment::count());
    }

    /**
     * A transient processing failure must return non-2xx so Stripe retries — the
     * backstop's whole value is durability, and recording is idempotent so a retry
     * is safe. Returning 200 here would silently drop the payment forever.
     */
    public function test_a_processing_failure_returns_500_so_stripe_retries(): void
    {
        $event = $this->eventFor($this->paidSession());
        $this->mock(StripeCheckout::class, function ($m) use ($event) {
            $m->shouldReceive('verifyWebhook')->andReturn($event);
            $m->shouldReceive('retrieveSession')->andThrow(new \RuntimeException('stripe api down'));
        });

        $this->postWebhook()->assertStatus(500);
        $this->assertSame(0, Payment::count());
    }
}

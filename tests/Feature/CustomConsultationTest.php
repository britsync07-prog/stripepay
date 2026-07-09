<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Notifications\PaymentLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature coverage for a custom request's payment link: emailing/expiring it,
 * and the public checkout behaviour for custom + expired requests. (Custom
 * requests are client-initiated; admins price them, they don't create them.)
 */
class CustomConsultationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('client');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function customRequest(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'title' => 'Bespoke Advisory',
            'name' => 'Jane Client',
            'email' => 'jane@example.com',
            'amount' => 150000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
            'expires_at' => now()->addDays(7),
        ], $overrides));
    }

    // --- Sending the payment link ------------------------------------------

    public function test_send_link_notifies_a_linked_client(): void
    {
        Notification::fake();

        $client = User::factory()->create(['email' => 'linked@example.com']);
        $client->assignRole('client');
        $request = $this->customRequest(['user_id' => $client->id, 'email' => 'linked@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $request))
            ->assertRedirect();

        Notification::assertSentTo($client, PaymentLinkNotification::class);
    }

    public function test_send_link_uses_on_demand_mail_for_an_unprovisioned_buyer(): void
    {
        Notification::fake();

        $request = $this->customRequest(['user_id' => null, 'email' => 'guest@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $request));

        Notification::assertSentOnDemand(
            PaymentLinkNotification::class,
            fn (PaymentLinkNotification $n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'guest@example.com'
                && $channels === ['mail'],
        );
    }

    public function test_send_link_is_blocked_for_a_paid_request(): void
    {
        Notification::fake();

        $request = $this->customRequest(['status' => ConsultationStatus::Paid]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $request))
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_send_link_is_blocked_for_an_expired_request(): void
    {
        Notification::fake();

        $request = $this->customRequest(['expires_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $request));

        Notification::assertNothingSent();
    }

    // --- Public checkout ----------------------------------------------------

    public function test_checkout_renders_the_offer_prop_for_a_custom_request(): void
    {
        config([
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);

        $request = $this->customRequest([
            'title' => 'Bespoke Advisory',
            'message' => 'Scope detail.',
            'amount' => 150000,
        ]);

        $this->get(route('consultation.checkout', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/checkout')
                ->where('offer.title', 'Bespoke Advisory')
                ->where('offer.description', 'Scope detail.')
                ->where('offer.amount', '$1,500')
                ->where('plan', null),
            );
    }

    public function test_checkout_renders_the_expired_page_for_an_expired_request(): void
    {
        $request = $this->customRequest(['expires_at' => now()->subDay()]);

        $this->get(route('consultation.checkout', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/expired')
                ->where('offer.title', 'Bespoke Advisory'),
            );
    }

    public function test_gateway_session_returns_410_for_an_expired_request(): void
    {
        config([
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);

        $request = $this->customRequest(['expires_at' => now()->subDay()]);

        $this->post(route('consultation.pay.session', [$request, 'paypal']))
            ->assertStatus(410);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\Admin\CreateProjectFromConsultation;
use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CustomPlanReceivedNotification;
use App\Notifications\CustomPlanRequestedNotification;
use App\Notifications\PaymentLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The inbound "client requests a custom plan" flow: a guest submits a tailored
 * request (no plan, unpriced), admins are notified, and an admin then prices the
 * lead — turning it into a payable request whose checkout is reachable.
 */
class RequestCustomPlanTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'company' => 'Acme Co',
            'country' => 'US',
            'message' => 'We need cross-border tax advisory for a UAE entity.',
        ], $overrides);
    }

    /** A priced, plan-based request whose checkout should always be reachable. */
    private function pricedRequest(array $overrides = []): ConsultationRequest
    {
        $plan = Plan::create([
            'name' => 'Call',
            'slug' => 'call-'.uniqid(),
            'price' => 5000,
            'currency' => 'USD',
        ]);

        return ConsultationRequest::create(array_merge([
            'plan_id' => $plan->id,
            'name' => 'Paid Client',
            'email' => 'paid@example.com',
            'amount' => 5000,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
        ], $overrides));
    }

    /** An unpriced custom lead, as produced by the inbound request. */
    private function customLead(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'title' => 'Custom plan request',
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
        ], $overrides));
    }

    private function paypalConfig(): void
    {
        config([
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.paypal.client_id' => 'cid',
            'services.paypal.client_secret' => 'secret',
            'services.paypal.mode' => 'sandbox',
        ]);
    }

    // --- Inbound request creation ------------------------------------------

    public function test_a_guest_can_request_a_custom_plan_and_admins_are_notified(): void
    {
        Notification::fake();

        $this->post(route('custom-plan.store'), $this->payload())
            ->assertRedirect('/');

        $lead = ConsultationRequest::firstOrFail();

        $this->assertNull($lead->plan_id);
        $this->assertTrue($lead->isCustom());
        $this->assertSame('Custom plan request', $lead->title);
        $this->assertSame(0, $lead->amount);
        $this->assertSame('USD', $lead->currency);
        $this->assertSame(ConsultationStatus::Pending, $lead->status);
        $this->assertSame('Jane Prospect', $lead->name);
        $this->assertSame('jane@example.com', $lead->email);
        $this->assertSame('Acme Co', $lead->company);
        $this->assertSame('US', $lead->country);

        // An inbound request must enter the CRM pipeline as a new lead.
        $this->assertTrue($lead->isLead());
        $this->assertSame(LeadStage::New, $lead->lead_stage);
        $this->assertSame('form', $lead->lead_source);

        Notification::assertSentTo($this->admin, CustomPlanRequestedNotification::class);
    }

    public function test_the_client_receives_a_confirmation_email_on_a_custom_request(): void
    {
        Notification::fake();

        $this->post(route('custom-plan.store'), $this->payload(['email' => 'jane@example.com']))
            ->assertRedirect('/');

        // The (account-less) client is emailed a receipt at their submitted address.
        Notification::assertSentTo(
            new AnonymousNotifiable,
            CustomPlanReceivedNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'jane@example.com',
        );
    }

    public function test_a_planning_project_is_created_when_a_custom_request_arrives(): void
    {
        $this->post(route('custom-plan.store'), $this->payload())->assertRedirect('/');

        $lead = ConsultationRequest::firstOrFail();
        $project = Project::where('consultation_request_id', $lead->id)->firstOrFail();

        // Delivery workspace exists pre-payment, in Planning, and unlinked (no
        // client account until payment).
        $this->assertSame(ProjectStatus::Planning, $project->status);
        $this->assertNull($project->user_id);
    }

    public function test_project_flips_from_planning_to_active_once_the_consultation_is_paid(): void
    {
        $lead = $this->customLead(['amount' => 150000]);

        $project = CreateProjectFromConsultation::run($lead);
        $this->assertSame(ProjectStatus::Planning, $project->status);

        // Payment lands; re-running is idempotent and takes the project live.
        $lead->update(['status' => ConsultationStatus::Paid, 'paid_at' => now()]);
        $again = CreateProjectFromConsultation::run($lead->fresh());

        $this->assertTrue($project->is($again));
        $this->assertSame(ProjectStatus::Active, $again->fresh()->status);
    }

    public function test_request_validation_rejects_missing_name_email_and_message(): void
    {
        $this->post(route('custom-plan.store'), $this->payload([
            'name' => '',
            'email' => '',
            'message' => '',
        ]))->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ConsultationRequest::count());
    }

    public function test_request_validation_rejects_an_invalid_email(): void
    {
        $this->post(route('custom-plan.store'), $this->payload([
            'email' => 'not-an-email',
        ]))->assertSessionHasErrors(['email']);

        $this->assertSame(0, ConsultationRequest::count());
    }

    public function test_budget_is_prepended_into_the_message_when_provided(): void
    {
        $this->post(route('custom-plan.store'), $this->payload([
            'budget' => '$10k–$20k',
            'message' => 'Need a fractional CFO.',
        ]));

        $lead = ConsultationRequest::firstOrFail();

        $this->assertStringStartsWith('Budget: $10k–$20k', $lead->message);
        $this->assertStringContainsString('Need a fractional CFO.', $lead->message);
    }

    public function test_message_is_unchanged_when_no_budget_is_given(): void
    {
        $this->post(route('custom-plan.store'), $this->payload([
            'message' => 'Just the message.',
        ]));

        $this->assertSame('Just the message.', ConsultationRequest::firstOrFail()->message);
    }

    public function test_request_does_not_link_or_touch_an_existing_account_before_payment(): void
    {
        // Submitting a stranger's (unverified) email must not bind the lead to
        // their account or alter it — provisioning happens only on payment.
        $user = User::factory()->create([
            'email' => 'known@example.com',
            'email_verified_at' => null,
        ]);

        $this->post(route('custom-plan.store'), $this->payload([
            'email' => 'known@example.com',
        ]));

        $this->assertNull(ConsultationRequest::firstOrFail()->user_id);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_request_does_not_create_an_account_before_payment(): void
    {
        $before = User::count();

        $this->post(route('custom-plan.store'), $this->payload([
            'name' => 'New Lead',
            'email' => 'stranger@example.com',
        ]));

        // No account is provisioned for the unverified lead; the consultation is
        // left unlinked until the client actually pays.
        $this->assertSame($before, User::count());
        $this->assertNull(User::where('email', 'stranger@example.com')->first());
        $this->assertNull(ConsultationRequest::firstOrFail()->user_id);
    }

    public function test_the_custom_plan_request_page_renders(): void
    {
        $this->get(route('custom-plan'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('consultation/custom-plan'));
    }

    public function test_consultations_list_type_filter_separates_requests_from_consultations(): void
    {
        $lead = $this->customLead(['amount' => 0]); // unpriced → a "request"
        $priced = $this->customLead([ // priced → a "consultation"
            'amount' => 50000,
            'email' => 'priced@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultations', ['filter' => ['type' => 'requests']]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/consultations')
                ->has('consultations.data', 1)
                ->where('consultations.data.0.uuid', $lead->uuid)
            );

        $this->actingAs($this->admin)
            ->get(route('admin.consultations', ['filter' => ['type' => 'consultations']]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/consultations')
                ->has('consultations.data', 1)
                ->where('consultations.data.0.uuid', $priced->uuid)
            );
    }

    public function test_admin_dashboard_counts_pending_custom_requests(): void
    {
        $this->customLead(['amount' => 0]); // unpriced lead — counts
        $this->customLead(['amount' => 50000]); // priced — does not count

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('needsAttention.0.key', 'price')
                ->where('needsAttention.0.count', 1)
            );
    }

    // --- Unpriced lead is not payable --------------------------------------

    public function test_checkout_404s_for_an_unpriced_custom_lead(): void
    {
        $this->paypalConfig();

        $lead = $this->customLead(['amount' => 0]);

        $this->get(route('consultation.checkout', $lead))->assertNotFound();
    }

    // --- Admin pricing ------------------------------------------------------

    public function test_admin_prices_a_custom_lead_and_checkout_becomes_reachable(): void
    {
        $this->paypalConfig();

        $lead = $this->customLead(['amount' => 0, 'message' => 'Scope detail.']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'title' => 'Cross-border advisory retainer',
                'amount' => '1500',
                'currency' => 'usd',
                'expires_in_days' => 14,
            ])
            ->assertRedirect();

        $lead->refresh();

        // 1500 USD → 150000 minor units; currency upper-cased.
        $this->assertSame(150000, $lead->amount);
        $this->assertSame('USD', $lead->currency);
        $this->assertSame('Cross-border advisory retainer', $lead->title);
        $this->assertNotNull($lead->expires_at);
        $this->assertEqualsWithDelta(
            now()->addDays(14)->timestamp,
            $lead->expires_at->timestamp,
            60,
        );

        // Once priced, the checkout no longer 404s.
        $this->get(route('consultation.checkout', $lead))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/checkout')
                ->where('offer.title', 'Cross-border advisory retainer')
                ->where('offer.amount', '$1,500'),
            );
    }

    public function test_pricing_redirects_to_the_dedicated_payment_link_page(): void
    {
        $lead = $this->customLead(['amount' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'title' => 'Retainer',
                'amount' => '1500',
                'expires_in_days' => 14,
            ])
            ->assertRedirect(route('admin.consultation.payment-link', $lead));
    }

    public function test_the_payment_link_page_renders_for_a_priced_custom_lead(): void
    {
        $lead = $this->customLead([
            'amount' => 150000,
            'title' => 'Retainer',
            'email' => 'lead@example.com',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultation.payment-link', $lead))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/consultation-payment-link')
                ->where('consultation.uuid', $lead->uuid)
                ->where('paymentLink.unpriced', false)
                ->where('priceInitial.amount', '1500.00')
            );
    }

    public function test_the_payment_link_page_404s_for_a_plan_based_request(): void
    {
        $request = $this->pricedRequest();

        $this->actingAs($this->admin)
            ->get(route('admin.consultation.payment-link', $request))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_price_a_custom_lead(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $lead = $this->customLead();

        $this->actingAs($client)
            ->post(route('admin.consultations.price', $lead), [
                'amount' => '1500',
                'currency' => 'USD',
                'expires_in_days' => 7,
            ])
            ->assertForbidden();

        $this->assertSame(0, (int) $lead->fresh()->amount);
    }

    public function test_pricing_a_plan_based_request_404s(): void
    {
        $request = $this->pricedRequest();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $request), [
                'amount' => '1500',
                'currency' => 'USD',
                'expires_in_days' => 7,
            ])
            ->assertNotFound();
    }

    public function test_pricing_validation_rejects_an_amount_below_one(): void
    {
        $lead = $this->customLead();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'amount' => '0',
                'currency' => 'USD',
                'expires_in_days' => 7,
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(0, (int) $lead->fresh()->amount);
    }

    public function test_pricing_an_already_paid_request_conflicts(): void
    {
        $lead = $this->customLead(['status' => ConsultationStatus::Paid]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'amount' => '1500',
                'currency' => 'USD',
                'expires_in_days' => 7,
            ])
            ->assertStatus(409);
    }

    // --- Sending the payment link ------------------------------------------

    public function test_payment_link_cannot_be_sent_for_an_unpriced_lead(): void
    {
        Notification::fake();

        // A link to an unpriced lead would 404 at checkout (see ShowCheckout),
        // so SendPaymentLink must refuse it and tell the admin to price first.
        $lead = $this->customLead(['amount' => 0]);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $lead))
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_payment_link_is_sent_once_a_lead_is_priced(): void
    {
        Notification::fake();

        // No linked user → SendPaymentLink routes the mail to the buyer's email.
        $lead = $this->customLead(['amount' => 150000, 'email' => 'lead@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.send-link', $lead))
            ->assertRedirect();

        Notification::assertSentTo(
            new AnonymousNotifiable,
            PaymentLinkNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'lead@example.com',
        );
    }
}

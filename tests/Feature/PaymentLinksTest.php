<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentLinksTest extends TestCase
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

    public function test_admin_creates_a_payment_link_with_only_a_price(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.store'), ['amount' => '100'])
            ->assertRedirect();

        $link = ConsultationRequest::firstOrFail();

        // Priced, no plan, and NOT a CRM lead — the buyer fills their own details.
        $this->assertNull($link->plan_id);
        $this->assertNull($link->lead_stage);
        $this->assertNull($link->name);
        $this->assertNull($link->email);
        $this->assertSame(10000, (int) $link->amount);
        $this->assertSame(ConsultationStatus::Pending, $link->status);
        $this->assertNotNull($link->expires_at);
        $this->assertTrue($link->expires_at->between(now()->addDays(2), now()->addDays(4)));
        $this->assertTrue($link->isPaymentLink());
        $this->assertFalse($link->isLead());
    }

    public function test_price_converts_to_minor_units_without_float_drift(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.store'), ['amount' => '10.005', 'title' => 'Audit']);

        $link = ConsultationRequest::firstOrFail();

        $this->assertSame(1001, (int) $link->amount);
        $this->assertSame('Audit', $link->title);
    }

    public function test_amount_is_required_and_must_be_positive(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.store'), ['amount' => '0'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, ConsultationRequest::count());
    }

    public function test_optional_expiry_sets_a_pay_by_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.store'), ['amount' => '50', 'expires_in_days' => '7']);

        $link = ConsultationRequest::firstOrFail();

        $this->assertNotNull($link->expires_at);
        $this->assertTrue($link->expires_at->isFuture());
    }

    public function test_list_shows_only_payment_links_not_leads_or_plan_consultations(): void
    {
        // A payment link (priced custom, no lead stage).
        $link = ConsultationRequest::create([
            'plan_id' => null, 'title' => 'Consultation', 'amount' => 5000,
            'currency' => 'USD', 'status' => ConsultationStatus::Pending,
        ]);

        // A CRM lead (has a lead stage) — must NOT appear.
        ConsultationRequest::create([
            'plan_id' => null, 'name' => 'Lead', 'email' => 'lead@example.com',
            'amount' => 9000, 'currency' => 'USD', 'status' => ConsultationStatus::Pending,
            'lead_stage' => LeadStage::New, 'lead_source' => 'manual',
        ]);

        // A plan-based consultation — must NOT appear.
        $plan = Plan::create(['name' => 'Call', 'slug' => 'call-'.uniqid(), 'price' => 7000, 'currency' => 'USD']);
        ConsultationRequest::create([
            'plan_id' => $plan->id, 'amount' => 7000, 'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.payment-links'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payment-links')
                ->has('links.data', 1)
                ->where('links.data.0.uuid', $link->uuid)
                ->where('stats.total', 1)
                ->where('stats.unpaid', 1)
                ->where('stats.paid', 0)
            );
    }

    public function test_payment_link_checkout_is_publicly_payable(): void
    {
        $link = ConsultationRequest::create([
            'plan_id' => null, 'title' => 'Strategy call', 'amount' => 15000,
            'currency' => 'USD', 'status' => ConsultationStatus::Pending,
        ]);

        // A guest (no account) can open the link and pay — details collected at checkout.
        $this->get(route('consultation.checkout', $link))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/checkout')
                ->where('offer.title', 'Strategy call')
            );
    }

    public function test_non_admin_cannot_create_a_payment_link(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.payment-links.store'), ['amount' => '100'])
            ->assertForbidden();

        $this->assertSame(0, ConsultationRequest::count());
    }
}

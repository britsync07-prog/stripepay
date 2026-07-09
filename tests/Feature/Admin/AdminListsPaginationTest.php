<?php

namespace Tests\Feature\Admin;

use App\Enums\InvoiceStatus;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Covers the server-side pagination + Spatie QueryBuilder filtering + global
 * money stats on the admin list endpoints (payments / payouts / clients /
 * experts) and the per-expert detail page (ShowExpert).
 */
class AdminListsPaginationTest extends TestCase
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

        // Keep the payout-status refresh concern from reaching Stripe: an
        // unconfigured rail short-circuits before any API call.
        config([
            'services.stripe.secret' => null,
            'services.payouts.default' => 'stripe',
            'services.payouts.rails' => ['stripe'],
        ]);
    }

    private function plan(string $name = 'Strategy Call'): Plan
    {
        return Plan::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'price' => 5000,
            'currency' => 'USD',
        ]);
    }

    private function consultation(Plan $plan, string $status, int $amount): ConsultationRequest
    {
        return ConsultationRequest::create([
            'plan_id' => $plan->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $status,
        ]);
    }

    private function payment(string $status, int $amount, ?ConsultationRequest $consultation = null, string $gateway = 'stripe', ?string $reference = null): Payment
    {
        return Payment::create([
            'consultation_request_id' => $consultation?->id,
            'gateway' => $gateway,
            'reference' => $reference ?? 'ref_'.uniqid(),
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    private function expert(array $overrides = []): Expert
    {
        return Expert::create(array_merge([
            'name' => 'Jane Expert',
            'email' => 'jane'.uniqid().'@example.com',
            'is_active' => true,
            'preferred_payout_method' => 'stripe',
        ], $overrides));
    }

    private function payout(Expert $expert, string $status, int $amount, ?string $reference = null): Payout
    {
        return Payout::create([
            'expert_id' => $expert->id,
            'amount' => $amount,
            'currency' => 'USD',
            'method' => 'stripe',
            'status' => $status,
            'reference' => $reference ?? 'po_'.uniqid(),
            'idempotency_key' => 'idem_'.uniqid(),
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    // ---- Payments -------------------------------------------------------

    public function test_payments_list_is_paginated_and_respects_per_page(): void
    {
        $plan = $this->plan();
        $consultation = $this->consultation($plan, 'paid', 5000);

        for ($i = 0; $i < 15; $i++) {
            $this->payment('paid', 1000, $consultation);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.payments'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payments')
                ->has('payments.data', 12)
                ->has('payments.links')
                ->where('payments.total', 15)
                ->where('payments.per_page', 12)
            );
    }

    public function test_payments_status_filter_narrows_results(): void
    {
        $plan = $this->plan();
        $consultation = $this->consultation($plan, 'paid', 5000);

        $this->payment('paid', 1000, $consultation);
        $this->payment('paid', 1000, $consultation);
        $this->payment('refunded', 1000, $consultation);

        $this->actingAs($this->admin)
            ->get(route('admin.payments', ['filter' => ['status' => 'paid']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payments.total', 2)
                ->where('filters.status', 'paid')
            );
    }

    public function test_payments_search_filter_matches_reference_and_plan(): void
    {
        $needlePlan = $this->plan('Tax Advisory Deep Dive');
        $otherPlan = $this->plan('Quick Question');
        $cNeedle = $this->consultation($needlePlan, 'paid', 5000);
        $cOther = $this->consultation($otherPlan, 'paid', 5000);

        $this->payment('paid', 1000, $cNeedle, reference: 'find-me-123');
        $this->payment('paid', 1000, $cOther, reference: 'ignore-me');
        $this->payment('paid', 1000, $cOther, reference: 'ignore-too');

        // By reference substring
        $this->actingAs($this->admin)
            ->get(route('admin.payments', ['filter' => ['search' => 'find-me']]))
            ->assertInertia(fn (Assert $page) => $page->where('payments.total', 1));

        // By related plan name
        $this->actingAs($this->admin)
            ->get(route('admin.payments', ['filter' => ['search' => 'Tax Advisory']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payments.total', 1)
                ->where('filters.search', 'Tax Advisory')
            );
    }

    public function test_payments_stats_count_only_paid_rows(): void
    {
        $plan = $this->plan();
        $consultation = $this->consultation($plan, 'paid', 5000);

        $this->payment('paid', 4000, $consultation);
        $this->payment('paid', 6000, $consultation);
        $this->payment('refunded', 9999, $consultation); // must NOT count toward received

        $this->actingAs($this->admin)
            ->get(route('admin.payments'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.received', '$100') // (4000 + 6000) minor = $100
                ->where('stats.total', 3)
                ->where('stats.refunded', 1)
            );
    }

    public function test_payments_unknown_filter_does_not_500(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.payments', ['filter' => ['bogus' => 'x']]))
            ->assertOk();
    }

    // ---- Payouts --------------------------------------------------------

    public function test_payouts_list_is_paginated_with_stats(): void
    {
        $expert = $this->expert();

        for ($i = 0; $i < 15; $i++) {
            $this->payout($expert, 'paid', 2000);
        }
        $this->payout($expert, 'pending', 7777);

        $this->actingAs($this->admin)
            ->get(route('admin.payouts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payouts')
                ->has('payouts.data', 12)
                ->where('payouts.total', 16)
                ->where('payouts.per_page', 12)
                ->where('stats.paidOut', '$300') // 15 * 2000 minor = $300
                ->where('stats.pending', 1)
                ->where('stats.total', 16)
            );
    }

    public function test_payouts_status_filter_narrows_results(): void
    {
        $expert = $this->expert();
        $this->payout($expert, 'paid', 2000);
        $this->payout($expert, 'failed', 2000);
        $this->payout($expert, 'failed', 2000);

        $this->actingAs($this->admin)
            ->get(route('admin.payouts', ['filter' => ['status' => 'failed']]))
            ->assertInertia(fn (Assert $page) => $page->where('payouts.total', 2));
    }

    // ---- Clients --------------------------------------------------------

    public function test_clients_list_is_paginated_and_searchable(): void
    {
        $plan = $this->plan();

        foreach (range(1, 15) as $i) {
            $u = User::factory()->create(['name' => "Client {$i}", 'email' => "client{$i}@example.com"]);
            $u->assignRole('client');
        }
        $special = User::factory()->create(['name' => 'Zelda Searchable', 'email' => 'zelda@example.com']);
        $special->assignRole('client');

        // global stats: paid consultations sum
        ConsultationRequest::create([
            'plan_id' => $plan->id,
            'user_id' => $special->id,
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.clients'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/clients')
                ->has('clients.data', 12)
                ->where('clients.total', 16)
                ->where('clients.per_page', 12)
                ->where('stats.total', 16)
                ->where('stats.spent', '$50')
            );

        $this->actingAs($this->admin)
            ->get(route('admin.clients', ['filter' => ['search' => 'Zelda']]))
            ->assertInertia(fn (Assert $page) => $page->where('clients.total', 1));
    }

    public function test_clients_list_excludes_admins(): void
    {
        // Only the admin from setUp exists with no client role — list must be empty.
        $this->actingAs($this->admin)
            ->get(route('admin.clients'))
            ->assertInertia(fn (Assert $page) => $page->where('clients.total', 0));
    }

    // ---- Experts --------------------------------------------------------

    public function test_experts_list_is_paginated_and_searchable(): void
    {
        foreach (range(1, 15) as $i) {
            $this->expert(['name' => "Expert {$i}", 'specialization' => 'General']);
        }
        $this->expert(['name' => 'Quantum Specialist', 'specialization' => 'Quantum Physics']);

        $this->actingAs($this->admin)
            ->get(route('admin.experts'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/experts')
                ->has('experts.data', 12)
                ->where('experts.total', 16)
                ->where('experts.per_page', 12)
            );

        // search by specialization
        $this->actingAs($this->admin)
            ->get(route('admin.experts', ['filter' => ['search' => 'Quantum']]))
            ->assertInertia(fn (Assert $page) => $page->where('experts.total', 1));
    }

    public function test_experts_unknown_filter_does_not_500(): void
    {
        $this->expert();

        $this->actingAs($this->admin)
            ->get(route('admin.experts', ['filter' => ['nope' => 'y']]))
            ->assertOk();
    }

    // ---- ShowExpert -----------------------------------------------------

    public function test_show_expert_lists_only_that_experts_payouts(): void
    {
        $expert = $this->expert(['name' => 'Owner']);
        $other = $this->expert(['name' => 'Other']);

        // 15 for our expert, plenty for the other one.
        for ($i = 0; $i < 15; $i++) {
            $this->payout($expert, 'paid', 1000);
        }
        $this->payout($other, 'paid', 99999);
        $this->payout($other, 'pending', 99999);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.show', $expert))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/experts/show')
                ->where('expert.id', $expert->id)
                ->has('payouts.data', 10) // paginate(10)
                ->where('payouts.total', 15) // ONLY this expert's payouts
                ->where('payouts.per_page', 10)
                ->where('stats.count', 15)
                ->where('stats.totalPaid', '$150')
            );
    }

    public function test_show_expert_status_filter_works(): void
    {
        $expert = $this->expert();
        $this->payout($expert, 'paid', 1000);
        $this->payout($expert, 'pending', 1000);
        $this->payout($expert, 'pending', 1000);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.show', [$expert, 'filter' => ['status' => 'pending']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('payouts.total', 2)
                ->where('stats.pending', 2) // stats are global to the expert, not filtered
                ->where('stats.count', 3)
            );
    }

    public function test_show_expert_pending_stats_count_pending_and_processing(): void
    {
        $expert = $this->expert();
        $this->payout($expert, 'pending', 1000);
        $this->payout($expert, 'processing', 1000);
        $this->payout($expert, 'paid', 1000);

        $this->actingAs($this->admin)
            ->get(route('admin.experts.show', $expert))
            ->assertInertia(fn (Assert $page) => $page->where('stats.pending', 2));
    }

    // ---- Dashboard money flow ------------------------------------------

    public function test_dashboard_money_flow_reflects_only_paid_rows(): void
    {
        $plan = $this->plan();
        $consultation = $this->consultation($plan, 'paid', 5000);

        $this->payment('paid', 10000, $consultation);
        $this->payment('refunded', 9999, $consultation); // ignored

        $expert = $this->expert();
        $this->payout($expert, 'paid', 3000);
        $this->payout($expert, 'pending', 9999); // ignored

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('composition.received', 10000)
                ->where('composition.paidOut', 3000)
                ->where('composition.netMargin', 7000)
            );
    }

    public function test_dashboard_money_flow_breaks_out_collected_tax(): void
    {
        $plan = $this->plan();
        $consultation = $this->consultation($plan, 'paid', 10000);
        $this->payment('paid', 10000, $consultation);

        Invoice::create([
            'consultation_request_id' => $consultation->id,
            'currency' => 'USD',
            'status' => InvoiceStatus::Paid,
            'subtotal' => 9000,
            'tax_rate' => 11.11,
            'tax_amount' => 1000, // $10 inclusive tax
            'total' => 10000,
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dashboard')
                ->where('composition.received', 10000)
                ->where('composition.tax', 1000)
                ->where('composition.netMargin', 9000) // 10000 - 1000 tax - 0 fees - 0 payouts
            );
    }

    public function test_dashboard_range_param_windows_the_money_composition(): void
    {
        $plan = $this->plan();

        $recent = $this->consultation($plan, 'paid', 10000);
        Payment::create([
            'consultation_request_id' => $recent->id, 'gateway' => 'stripe', 'reference' => 'in-window',
            'amount' => 10000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);

        $old = $this->consultation($plan, 'paid', 70000);
        Payment::create([
            'consultation_request_id' => $old->id, 'gateway' => 'stripe', 'reference' => 'out-of-window',
            'amount' => 70000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()->subMonths(8),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard', ['range' => '3m']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('range', '3m')
                ->where('composition.received', 10000)
            );

        $this->actingAs($this->admin)
            ->get(route('dashboard', ['range' => 'bogus']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('range', 'all')
                ->where('composition.received', 80000)
            );
    }

    // ---- Consultations assigned filter -----------------------------------

    public function test_consultations_assigned_filter_narrows_to_unassigned(): void
    {
        $plan = $this->plan();

        $unassigned = $this->consultation($plan, 'paid', 5000);
        $assigned = $this->consultation($plan, 'paid', 5000);
        $assigned->update(['expert_id' => $this->expert()->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.consultations', ['filter' => ['assigned' => 'no']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/consultations')
                ->has('consultations.data', 1)
                ->where('consultations.data.0.uuid', $unassigned->uuid)
                ->where('filters.assigned', 'no')
            );

        $this->actingAs($this->admin)
            ->get(route('admin.consultations', ['filter' => ['assigned' => 'yes']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('consultations.data', 1)
                ->where('consultations.data.0.uuid', $assigned->uuid)
            );
    }

    // ---- Access guard ---------------------------------------------------

    public function test_non_admin_cannot_access_admin_lists(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->get(route('admin.payments'))
            ->assertForbidden();
    }
}

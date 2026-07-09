<?php

namespace Tests\Feature;

use App\Actions\Payments\RecordConsultationPayment;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Payments\VerifiedPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadsPipelineTest extends TestCase
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

    private function lead(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => 'pending',
            'lead_stage' => LeadStage::New,
            'lead_source' => 'manual',
        ], $overrides));
    }

    public function test_the_board_renders_a_column_per_stage(): void
    {
        $this->lead();

        $this->actingAs($this->admin)
            ->get(route('admin.leads'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leads')
                ->has('columns', 5)
                ->where('stats.total', 1)
                ->where('stats.open', 1)
            );
    }

    public function test_admin_can_add_a_manual_lead(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.leads.store'), [
                'name' => 'New Prospect',
                'email' => 'prospect@example.com',
                'company' => 'Acme',
                'country' => 'US',
                'message' => 'Met at a conference.',
            ])
            ->assertRedirect();

        $lead = ConsultationRequest::firstOrFail();
        $this->assertNull($lead->plan_id);
        $this->assertSame(LeadStage::New, $lead->lead_stage);
        $this->assertSame('manual', $lead->lead_source);
        $this->assertSame(0, (int) $lead->amount);
    }

    public function test_admin_can_move_a_lead_between_stages(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $lead), ['stage' => 'contacted'])
            ->assertRedirect();

        $this->assertSame(LeadStage::Contacted, $lead->fresh()->lead_stage);
    }

    public function test_a_lead_cannot_be_marked_won_by_hand_while_unpaid(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Quoted, 'amount' => 50000]);

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $lead), ['stage' => 'won'])
            ->assertSessionHasErrors('stage');

        $this->assertSame(LeadStage::Quoted, $lead->fresh()->lead_stage);
    }

    public function test_a_won_lead_cannot_be_moved_back_out_of_the_won_column(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Won, 'amount' => 50000, 'status' => 'paid']);

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $lead), ['stage' => 'contacted'])
            ->assertSessionHasErrors('stage');

        $this->assertSame(LeadStage::Won, $lead->fresh()->lead_stage);
    }

    public function test_marking_a_lead_lost_records_the_reason_and_clears_follow_up(): void
    {
        $lead = $this->lead([
            'lead_stage' => LeadStage::Contacted,
            'next_follow_up_at' => now()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $lead), [
                'stage' => 'lost',
                'lost_reason' => 'Went with a competitor.',
            ])
            ->assertRedirect();

        $fresh = $lead->fresh();
        $this->assertSame(LeadStage::Lost, $fresh->lead_stage);
        $this->assertSame('Went with a competitor.', $fresh->lost_reason);
        $this->assertNull($fresh->next_follow_up_at);
    }

    public function test_reopening_a_lost_lead_clears_its_stale_reason(): void
    {
        $lead = $this->lead(['lead_stage' => LeadStage::Lost, 'lost_reason' => 'Too expensive.']);

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $lead), ['stage' => 'contacted'])
            ->assertRedirect();

        $fresh = $lead->fresh();
        $this->assertSame(LeadStage::Contacted, $fresh->lead_stage);
        $this->assertNull($fresh->lost_reason);
    }

    public function test_admin_can_set_and_clear_a_follow_up_date(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)
            ->post(route('admin.leads.follow-up', $lead), ['next_follow_up_at' => '2026-07-15'])
            ->assertRedirect();
        $this->assertSame('2026-07-15', $lead->fresh()->next_follow_up_at->toDateString());

        $this->actingAs($this->admin)
            ->post(route('admin.leads.follow-up', $lead), ['next_follow_up_at' => null])
            ->assertRedirect();
        $this->assertNull($lead->fresh()->next_follow_up_at);
    }

    public function test_a_non_lead_consultation_is_not_addressable_as_a_lead(): void
    {
        $consultation = ConsultationRequest::create([
            'name' => 'Buyer', 'amount' => 5000, 'currency' => 'USD', 'status' => 'pending',
        ]); // plan-based-style, lead_stage null

        $this->actingAs($this->admin)
            ->post(route('admin.leads.stage', $consultation), ['stage' => 'contacted'])
            ->assertNotFound();
    }

    public function test_a_paid_lead_is_automatically_won(): void
    {
        Notification::fake();

        $lead = $this->lead(['lead_stage' => LeadStage::Quoted, 'amount' => 50000]);

        RecordConsultationPayment::run($lead, 'stripe', new VerifiedPayment(
            reference: 'cs_lead_win',
            amountMinor: 50000,
            currency: 'USD',
            payerEmail: 'jane@example.com',
            payerName: 'Jane Prospect',
        ));

        $this->assertSame(LeadStage::Won, $lead->fresh()->lead_stage);
    }

    public function test_store_lead_rejects_a_malformed_email_but_allows_a_blank_one(): void
    {
        // Name is now optional; only a *provided* email must be well-formed.
        $this->actingAs($this->admin)
            ->post(route('admin.leads.store'), ['name' => '', 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors('name');

        $this->assertSame(0, ConsultationRequest::count());
    }

    public function test_a_lead_can_be_added_with_no_client_details(): void
    {
        Notification::fake();

        // The admin doesn't know the client yet — they'll self-fill via the link.
        $this->actingAs($this->admin)
            ->post(route('admin.leads.store'), ['message' => 'Referral from a partner'])
            ->assertRedirect();

        $lead = ConsultationRequest::firstOrFail();

        $this->assertNull($lead->name);
        $this->assertNull($lead->email);
        $this->assertNull($lead->contact_id);
        $this->assertSame(LeadStage::New, $lead->lead_stage);
        // No email → no prospect notification is sent.
        Notification::assertNothingSent();
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $this->lead(['name' => 'Alice']);
        $this->lead(['name' => 'Bob', 'email' => 'bob@example.com']);

        // A bare "%" must not match every lead (it would, unescaped).
        $this->actingAs($this->admin)
            ->get(route('admin.leads', ['filter' => ['search' => '%']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 2)
                ->where('columns.0.count', 0));
    }

    public function test_a_follow_up_due_today_is_not_overdue_but_yesterday_is(): void
    {
        $today = $this->lead(['lead_stage' => LeadStage::Contacted, 'next_follow_up_at' => now()->startOfDay()]);
        $yesterday = $this->lead(['lead_stage' => LeadStage::Contacted, 'next_follow_up_at' => now()->subDay()]);

        $this->assertFalse($today->fresh()->isFollowUpOverdue());
        $this->assertTrue($yesterday->fresh()->isFollowUpOverdue());
    }

    public function test_a_non_admin_cannot_access_the_pipeline(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)->get(route('admin.leads'))->assertForbidden();
        $this->actingAs($client)
            ->post(route('admin.leads.store'), ['name' => 'X', 'email' => 'x@example.com'])
            ->assertForbidden();
    }

    public function test_a_follow_up_cannot_be_scheduled_on_a_non_lead(): void
    {
        $consultation = ConsultationRequest::create([
            'name' => 'Buyer', 'amount' => 5000, 'currency' => 'USD', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.leads.follow-up', $consultation), ['next_follow_up_at' => '2026-07-15'])
            ->assertNotFound();
    }

    public function test_the_board_only_contains_leads(): void
    {
        $this->lead(['name' => 'A real lead']);
        ConsultationRequest::create([
            'name' => 'Plan buyer', 'amount' => 5000, 'currency' => 'USD', 'status' => 'paid',
        ]); // lead_stage null — not a lead

        $this->actingAs($this->admin)
            ->get(route('admin.leads'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 1)
                ->where('columns.0.count', 1)
                ->has('columns.0.leads', 1)
                ->where('columns.0.leads.0.name', 'A real lead')
            );
    }

    public function test_search_scopes_the_board_columns(): void
    {
        $this->lead(['name' => 'Alice Northwind', 'email' => 'alice@example.com']);
        $this->lead(['name' => 'Bob Southgate', 'email' => 'bob@example.com']);

        $this->actingAs($this->admin)
            ->get(route('admin.leads', ['filter' => ['search' => 'Northwind']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Northwind')
                ->where('columns.0.count', 1)
                ->has('columns.0.leads', 1)
                ->where('columns.0.leads.0.name', 'Alice Northwind')
            );
    }

    public function test_terminal_columns_are_capped_but_report_the_true_count(): void
    {
        foreach (range(1, 51) as $i) {
            $this->lead(['lead_stage' => LeadStage::Lost, 'name' => "Lost {$i}", 'lost_reason' => 'n/a']);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.leads'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.4.stage', 'lost')
                ->where('columns.4.count', 51)
                ->where('columns.4.capped', true)
                ->has('columns.4.leads', 50)
            );
    }

    public function test_lead_data_exposes_the_expected_shape(): void
    {
        $this->lead([
            'lead_stage' => LeadStage::Quoted,
            'name' => 'Shape Check',
            'company' => 'Acme',
            'amount' => 50000,
            'next_follow_up_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.leads'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('columns.2.leads.0', fn (Assert $lead) => $lead
                    ->where('stage', 'quoted')
                    ->where('stage_label', 'Quoted')
                    ->where('is_terminal', false)
                    ->where('name', 'Shape Check')
                    ->where('company', 'Acme')
                    ->where('unpriced', false)
                    ->where('follow_up_overdue', true)
                    ->where('is_paid', false)
                    ->etc()
                )
            );
    }

    public function test_a_manual_unpriced_lead_is_marked_unpriced(): void
    {
        $this->lead(['name' => 'No price yet']);

        $this->actingAs($this->admin)
            ->get(route('admin.leads'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.0.leads.0.unpriced', true)
            );
    }

    public function test_auto_won_on_payment_is_idempotent(): void
    {
        Notification::fake();

        $lead = $this->lead(['lead_stage' => LeadStage::Quoted, 'amount' => 50000]);

        $payment = fn () => new VerifiedPayment(
            reference: 'cs_lead_idem',
            amountMinor: 50000,
            currency: 'USD',
            payerEmail: 'jane@example.com',
            payerName: 'Jane Prospect',
        );

        RecordConsultationPayment::run($lead, 'stripe', $payment());
        RecordConsultationPayment::run($lead->fresh(), 'stripe', $payment());

        $this->assertSame(LeadStage::Won, $lead->fresh()->lead_stage);
        $this->assertSame(1, $lead->payments()->count());
    }
}

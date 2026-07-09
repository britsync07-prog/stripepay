<?php

namespace Tests\Feature;

use App\Enums\LeadStage;
use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\Expert;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client');
    }

    private function paidConsultation(int $amount, ?string $country = null, ?int $planId = null, ?Carbon $paidAt = null, string $paymentStatus = 'paid'): ConsultationRequest
    {
        $paidAt ??= now();

        $c = ConsultationRequest::create([
            'plan_id' => $planId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => $paidAt,
            'country' => $country,
        ]);

        Payment::create([
            'consultation_request_id' => $c->id,
            'gateway' => 'stripe',
            'reference' => 'ref-'.$c->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $paymentStatus,
            'paid_at' => $paidAt,
        ]);

        return $c;
    }

    private function plan(string $name): Plan
    {
        return Plan::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.uniqid(),
            'price' => 0,
            'currency' => 'USD',
        ]);
    }

    public function test_composition_reports_gateway_fees_and_net_margin(): void
    {
        $c = ConsultationRequest::create([
            'amount' => 100000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);
        Payment::create([
            'consultation_request_id' => $c->id, 'gateway' => 'stripe', 'reference' => 'r1',
            'amount' => 100000, 'fee' => 3000, 'net' => 97000, 'currency' => 'USD',
            'status' => 'paid', 'paid_at' => now(),
        ]);
        Payout::create([
            'expert_id' => Expert::create(['name' => 'E', 'is_active' => true])->id,
            'amount' => 20000, 'currency' => 'USD', 'method' => 'stripe',
            'status' => 'paid', 'idempotency_key' => 'kf', 'paid_at' => now(),
        ]);

        $comp = DashboardMetrics::financialComposition();

        $this->assertSame('$1,000.00', $comp['received_label']);  // gross
        $this->assertSame('$30.00', $comp['fees_label']);         // processing fee
        $this->assertSame('$770.00', $comp['netMargin_label']);   // received - fees - paid out
    }

    public function test_composition_and_gateway_breakdown_respect_the_range_window(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        // Inside the 3-month window (April onwards).
        $this->paidConsultation(100000, paidAt: Carbon::parse('2026-05-10 09:00:00'));
        // Outside it.
        $this->paidConsultation(700000, paidAt: Carbon::parse('2025-11-10 09:00:00'));

        $since = DashboardMetrics::sinceForRange('3m');

        $this->assertSame(100000, DashboardMetrics::financialComposition($since)['received']);
        $this->assertSame(800000, DashboardMetrics::financialComposition()['received']);

        $gateways = collect(DashboardMetrics::paymentsByGateway($since))->keyBy('gateway');
        $this->assertSame(100000, $gateways['stripe']['grossRaw']);

        $byPlan = collect(DashboardMetrics::revenueByPlan($since))->keyBy('plan');
        $this->assertSame(1000, $byPlan['Consultation']['total']);

        Carbon::setTestNow();
    }

    public function test_needs_attention_counts_actionable_items(): void
    {
        $expert = Expert::create(['name' => 'Mara', 'is_active' => true]);

        // Each record is engineered to land in exactly one bucket.
        // price: unpriced pending custom, no follow-up.
        ConsultationRequest::create(['amount' => 0, 'currency' => 'USD', 'status' => 'pending', 'lead_stage' => LeadStage::New, 'lead_source' => 'form']);
        // followups: open lead with a past follow-up, already paid + assigned (so it's not in price/payment/assign).
        ConsultationRequest::create(['amount' => 0, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(), 'expert_id' => $expert->id, 'lead_stage' => LeadStage::Contacted, 'next_follow_up_at' => now()->subDay()]);
        // assign: paid plan consultation with no expert.
        ConsultationRequest::create(['amount' => 5000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        // payment: priced but unpaid.
        ConsultationRequest::create(['amount' => 8000, 'currency' => 'USD', 'status' => 'pending']);

        $items = collect(DashboardMetrics::needsAttention())->keyBy('key');

        $this->assertSame(1, $items['price']['count']);
        $this->assertSame(1, $items['followups']['count']);
        $this->assertSame(1, $items['assign']['count']);
        $this->assertSame(1, $items['payment']['count']);
        $this->assertSame(0, $items['payouts']['count']);
    }

    public function test_leads_pipeline_reports_stages_open_value_and_win_rate(): void
    {
        ConsultationRequest::create(['amount' => 0, 'currency' => 'USD', 'status' => 'pending', 'lead_stage' => LeadStage::New]);
        ConsultationRequest::create(['amount' => 100000, 'currency' => 'USD', 'status' => 'pending', 'lead_stage' => LeadStage::Quoted]);
        ConsultationRequest::create(['amount' => 50000, 'currency' => 'USD', 'status' => 'pending', 'lead_stage' => LeadStage::Quoted]);
        ConsultationRequest::create(['amount' => 70000, 'currency' => 'USD', 'status' => 'paid', 'lead_stage' => LeadStage::Won]);
        ConsultationRequest::create(['amount' => 0, 'currency' => 'USD', 'status' => 'pending', 'lead_stage' => LeadStage::Lost]);

        $pipe = DashboardMetrics::leadsPipeline();
        $stages = collect($pipe['stages'])->keyBy('label');

        $this->assertSame(1, $stages['New']['count']);
        $this->assertSame(2, $stages['Quoted']['count']);
        $this->assertSame('$1,500', $pipe['openValue']); // 100000 + 50000 quoted-open
        $this->assertSame(50, $pipe['winRate']); // 1 won of (1 won + 1 lost)
    }

    public function test_financial_composition_splits_received_into_margin_fees_and_payouts(): void
    {
        $c = ConsultationRequest::create(['amount' => 1000000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        Payment::create(['consultation_request_id' => $c->id, 'gateway' => 'stripe', 'reference' => 'r1',
            'amount' => 1000000, 'fee' => 30000, 'net' => 970000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        Payout::create([
            'expert_id' => Expert::create(['name' => 'E', 'is_active' => true])->id,
            'amount' => 200000, 'currency' => 'USD', 'method' => 'stripe',
            'status' => 'paid', 'idempotency_key' => 'kc', 'paid_at' => now(),
        ]);

        $comp = DashboardMetrics::financialComposition();

        // Raw minor units (for the bar widths) + formatted labels.
        $this->assertSame(1000000, $comp['received']);
        $this->assertSame(30000, $comp['fees']);
        $this->assertSame(200000, $comp['paidOut']);
        $this->assertSame(770000, $comp['netMargin']); // 1,000,000 - 30,000 - 200,000
        $this->assertSame('$10,000.00', $comp['received_label']);
        $this->assertSame('$7,700.00', $comp['netMargin_label']);
        // The segments fill the whole received amount.
        $this->assertSame($comp['received'], $comp['netMargin'] + $comp['fees'] + $comp['paidOut']);
    }

    public function test_payments_by_gateway_breaks_down_gross_fees_and_net(): void
    {
        $c1 = ConsultationRequest::create(['amount' => 1200000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        Payment::create(['consultation_request_id' => $c1->id, 'gateway' => 'stripe', 'reference' => 's1',
            'amount' => 1200000, 'fee' => 61769, 'net' => 1138231, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);

        $c2 = ConsultationRequest::create(['amount' => 500000, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);
        Payment::create(['consultation_request_id' => $c2->id, 'gateway' => 'paypal', 'reference' => 'p1',
            'amount' => 500000, 'fee' => 19530, 'net' => 480470, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()]);

        $rows = collect(DashboardMetrics::paymentsByGateway())->keyBy('gateway');

        $this->assertSame('$12,000.00', $rows['stripe']['gross']);
        $this->assertSame('$617.69', $rows['stripe']['fees']);
        $this->assertSame('$11,382.31', $rows['stripe']['net']);
        $this->assertSame(1, $rows['stripe']['count']);

        $this->assertSame('$5,000.00', $rows['paypal']['gross']);
        $this->assertSame('$195.30', $rows['paypal']['fees']);
        $this->assertSame('$4,804.70', $rows['paypal']['net']);
    }

    public function test_stats_aggregate_money_and_counts(): void
    {
        $this->paidConsultation(150000);
        $this->paidConsultation(250000);

        Payout::create([
            'expert_id' => Expert::create(['name' => 'Sara', 'is_active' => true])->id,
            'amount' => 100000, 'currency' => 'USD', 'method' => 'stripe',
            'status' => 'paid', 'idempotency_key' => 'k1', 'paid_at' => now(),
        ]);
        Expert::create(['name' => 'Inactive', 'is_active' => false]);

        ConsultationRequest::create([
            'title' => 'Custom', 'amount' => 0, 'currency' => 'USD', 'status' => 'pending',
        ]);
        User::factory()->create()->assignRole('client');

        Project::create(['title' => 'A', 'status' => ProjectStatus::Active]);
        Project::create(['title' => 'B', 'status' => ProjectStatus::Completed]);

        $stats = DashboardMetrics::stats();
        $comp = DashboardMetrics::financialComposition();
        $attention = collect(DashboardMetrics::needsAttention())->keyBy('key');

        // 150000 + 250000 = 400000 minor units = $4,000
        $this->assertSame('$4,000.00', $comp['received_label']);
        $this->assertSame('$2,000', $stats['avgDeal']); // 400000 / 2 = $2,000 (stat tiles stay whole-dollar)
        $this->assertSame('$1,000.00', $comp['paidOut_label']);
        $this->assertSame('$3,000.00', $comp['netMargin_label']); // 400000 - 100000 = $3,000
        $this->assertSame(1, $stats['clients']);
        $this->assertSame(1, $stats['consultants']);
        $this->assertSame(1, $attention['price']['count']);
        $this->assertSame(1, $stats['projectsActive']);
        $this->assertSame(1, $stats['projectsCompleted']);
        $this->assertSame(0, $attention['payouts']['count']);
    }

    public function test_revenue_series_returns_requested_months_with_this_month_total(): void
    {
        $this->paidConsultation(250000);

        $series = DashboardMetrics::revenueSeries(6);

        $this->assertCount(6, $series);
        $this->assertSame(2500, $series[5]['total']); // latest = this month, in whole units
    }

    public function test_top_countries_aggregate_and_resolve_names(): void
    {
        Country::create(['code' => 'AE', 'name' => 'United Arab Emirates']);
        $this->paidConsultation(1000, 'AE');
        $this->paidConsultation(1000, 'AE');
        $this->paidConsultation(1000, 'US'); // no Country row → falls back to code

        $countries = DashboardMetrics::topCountries();

        $this->assertSame('United Arab Emirates', $countries[0]['name']);
        $this->assertSame(2, $countries[0]['total']);
        $this->assertSame('US', $countries[1]['name']);
        $this->assertSame(1, $countries[1]['total']);
    }

    public function test_revenue_by_plan_groups_paid_payments_and_falls_back_for_planless(): void
    {
        $starter = $this->plan('Starter');

        $this->paidConsultation(100000, planId: $starter->id);
        $this->paidConsultation(50000, planId: $starter->id);
        $this->paidConsultation(30000); // no plan → "Consultation" bucket

        // Refunded payment must be excluded from paid revenue.
        $this->paidConsultation(99999, planId: $starter->id, paymentStatus: 'refunded');

        $byPlan = collect(DashboardMetrics::revenueByPlan())->keyBy('plan');

        // 100000 + 50000 = 150000 minor units → 1500 whole units; refund excluded.
        $this->assertSame(1500, $byPlan['Starter']['total']);
        $this->assertSame(300, $byPlan['Consultation']['total']);
    }

    public function test_received_this_month_and_delta_against_last_month(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        // Last month (May): $1,000 received.
        $this->paidConsultation(100000, paidAt: Carbon::parse('2026-05-10 09:00:00'));
        // This month (June): $1,500 received.
        $this->paidConsultation(150000, paidAt: Carbon::parse('2026-06-05 09:00:00'));

        $stats = DashboardMetrics::stats();

        $this->assertSame('$1,500', $stats['receivedThisMonth']);
        // (150000 - 100000) / 100000 * 100 = +50%
        $this->assertSame(50, $stats['receivedDelta']);

        Carbon::setTestNow();
    }

    public function test_received_delta_is_null_when_no_revenue_last_month(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        $this->paidConsultation(150000, paidAt: Carbon::parse('2026-06-05 09:00:00'));

        $this->assertNull(DashboardMetrics::stats()['receivedDelta']);

        Carbon::setTestNow();
    }

    public function test_pending_consultation_and_payout_counts(): void
    {
        $this->paidConsultation(100000); // paid — must not be counted as pending

        ConsultationRequest::create([
            'title' => 'Awaiting payment', 'amount' => 50000, 'currency' => 'USD', 'status' => 'pending',
        ]);

        $expert = Expert::create(['name' => 'Sara', 'is_active' => true]);
        $i = 0;
        foreach (['pending', 'processing', 'paid', 'failed'] as $status) {
            Payout::create([
                'expert_id' => $expert->id,
                'amount' => 10000, 'currency' => 'USD', 'method' => 'stripe',
                'status' => $status, 'idempotency_key' => 'k'.$i++, 'paid_at' => now(),
            ]);
        }

        $items = collect(DashboardMetrics::needsAttention())->keyBy('key');

        $this->assertSame(1, $items['payment']['count']);
        // pending + processing only; paid + failed excluded.
        $this->assertSame(2, $items['payouts']['count']);
    }
}

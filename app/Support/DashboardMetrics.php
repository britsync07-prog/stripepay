<?php

namespace App\Support;

use App\Enums\ConsultationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStage;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProjectStatus;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Services\SettingsService;
use Carbon\CarbonInterface;

/**
 * Read-model for the admin dashboard: every figure the overview renders, kept
 * out of the Action so each metric is reusable and unit-testable in isolation.
 *
 * The money aggregates accept an optional $since so the dashboard's range
 * selector (last 3/6/12 months vs all-time) scopes them without new methods.
 */
final class DashboardMetrics
{
    public const RANGES = ['3m' => 3, '6m' => 6, '12m' => 12, 'all' => null];

    /**
     * Window start for a range key, aligned to calendar months so the
     * composition totals agree with the monthly revenue series buckets.
     */
    public static function sinceForRange(string $range): ?CarbonInterface
    {
        $months = self::RANGES[$range] ?? null;

        return $months === null ? null : now()->subMonths($months - 1)->startOfMonth();
    }

    /**
     * @return array<string, mixed>
     */
    public static function stats(): array
    {
        $totals = self::paidPaymentTotals();
        $received = (int) $totals->amount;
        $paidConsultations = ConsultationRequest::where('status', ConsultationStatus::Paid->value)->count();
        $receivedThisMonth = self::receivedBetween(now()->startOfMonth(), now());

        return [
            'receivedThisMonth' => self::money($receivedThisMonth),
            'receivedDelta' => self::monthOverMonthDelta($receivedThisMonth),
            'avgDeal' => self::money($paidConsultations > 0 ? intdiv($received, $paidConsultations) : 0),
            'clients' => User::role('client')->count(),
            'consultants' => Expert::where('is_active', true)->count(),
            'projectsActive' => Project::where('status', ProjectStatus::Active->value)->count(),
            'projectsCompleted' => Project::where('status', ProjectStatus::Completed->value)->count(),
        ];
    }

    /**
     * Paid revenue per calendar month for the last N months (oldest first).
     *
     * @return array<int, array{month: string, total: int}>
     */
    public static function revenueSeries(int $months = 6): array
    {
        // One windowed fetch, then bucket per month in memory — avoids firing a
        // separate SUM query for each of the last N months. Kept DB-portable
        // (no DATE_FORMAT/strftime) so it runs the same on MySQL and SQLite.
        $windowStart = now()->subMonths($months - 1)->startOfMonth();

        $paid = Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $windowStart)
            ->get(['paid_at', 'amount']);

        return collect(range($months - 1, 0))
            ->map(function (int $monthsAgo) use ($paid, $months) {
                $start = now()->subMonths($monthsAgo)->startOfMonth();
                $end = (clone $start)->endOfMonth();

                return [
                    'month' => $start->format($months > 12 ? 'M y' : 'M'),
                    'total' => (int) round((int) $paid->whereBetween('paid_at', [$start, $end])->sum('amount') / 100),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Action queue — things an admin needs to act on, each a count + a target.
     *
     * @return array<int, array{key: string, count: int, label: string}>
     */
    public static function needsAttention(): array
    {
        $customToPrice = self::customToPriceCount();

        $overdueFollowUps = ConsultationRequest::leads()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now()->startOfDay())
            ->whereNotIn('lead_stage', [LeadStage::Won->value, LeadStage::Lost->value])
            ->count();

        $unassignedPaid = ConsultationRequest::where('status', ConsultationStatus::Paid->value)
            ->whereNull('expert_id')
            ->count();

        $awaitingPayment = ConsultationRequest::where('status', ConsultationStatus::Pending->value)
            ->where('amount', '>', 0)
            ->count();

        $stuckPayouts = Payout::whereIn('status', [
            PayoutStatus::Pending->value,
            PayoutStatus::Processing->value,
        ])->count();

        return [
            ['key' => 'price', 'count' => $customToPrice, 'label' => 'Custom requests to price'],
            ['key' => 'followups', 'count' => $overdueFollowUps, 'label' => 'Overdue follow-ups'],
            ['key' => 'assign', 'count' => $unassignedPaid, 'label' => 'Paid, awaiting an expert'],
            ['key' => 'payment', 'count' => $awaitingPayment, 'label' => 'Awaiting payment'],
            ['key' => 'payouts', 'count' => $stuckPayouts, 'label' => 'Payouts in flight'],
        ];
    }

    /**
     * Pipeline health: open leads by stage, open pipeline value, and win rate.
     *
     * @return array<string, mixed>
     */
    public static function leadsPipeline(): array
    {
        $byStage = ConsultationRequest::leads()
            ->selectRaw('lead_stage, COUNT(*) as c')
            ->groupBy('lead_stage')
            ->pluck('c', 'lead_stage');

        $won = (int) ($byStage[LeadStage::Won->value] ?? 0);
        $lost = (int) ($byStage[LeadStage::Lost->value] ?? 0);
        $decided = $won + $lost;

        $openValue = (int) ConsultationRequest::leads()
            ->whereIn('lead_stage', [LeadStage::New->value, LeadStage::Contacted->value, LeadStage::Quoted->value])
            ->where('amount', '>', 0)
            ->sum('amount');

        return [
            'stages' => [
                ['label' => 'New', 'count' => (int) ($byStage[LeadStage::New->value] ?? 0)],
                ['label' => 'Contacted', 'count' => (int) ($byStage[LeadStage::Contacted->value] ?? 0)],
                ['label' => 'Quoted', 'count' => (int) ($byStage[LeadStage::Quoted->value] ?? 0)],
            ],
            'openValue' => self::money($openValue),
            'won' => $won,
            'lost' => $lost,
            'winRate' => $decided > 0 ? (int) round($won / $decided * 100) : null,
        ];
    }

    /**
     * The money composition (raw minor units + formatted labels) for the
     * dashboard's "where your money goes" bar: received splits into net margin,
     * tax, fees and payouts.
     *
     * @return array<string, int|string>
     */
    public static function financialComposition(?CarbonInterface $since = null): array
    {
        $totals = self::paidPaymentTotals($since);
        $received = (int) $totals->amount;
        $fees = (int) $totals->fee;
        $tax = self::taxCollected($since);
        $paidOut = self::paidOutTotal($since);
        $netMargin = $received - $tax - $fees - $paidOut;

        return [
            'received' => $received,
            'tax' => $tax,
            'fees' => $fees,
            'paidOut' => $paidOut,
            'netMargin' => $netMargin,
            'received_label' => self::moneyExact($received),
            'tax_label' => self::moneyExact($tax),
            'fees_label' => self::moneyExact($fees),
            'paidOut_label' => self::moneyExact($paidOut),
            'netMargin_label' => self::moneyExact($netMargin),
        ];
    }

    /**
     * Gross / fees / net per money-in rail, so the cost of each gateway is visible.
     *
     * @return array<int, array{gateway: string, label: string, count: int, gross: string, fees: string, net: string, grossRaw: int, feesRaw: int, netRaw: int}>
     */
    public static function paymentsByGateway(?CarbonInterface $since = null): array
    {
        return Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->when($since, fn ($q) => $q->where('paid_at', '>=', $since))
            ->groupBy('gateway')
            ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as gross, SUM(fee) as fees')
            ->orderByDesc('gross')
            ->get()
            ->map(function ($row) {
                $gross = (int) $row->gross;
                $fees = (int) $row->fees;

                return [
                    'gateway' => $row->gateway,
                    'label' => ucfirst((string) $row->gateway),
                    'count' => (int) $row->count,
                    'gross' => self::moneyExact($gross),
                    'fees' => self::moneyExact($fees),
                    'net' => self::moneyExact($gross - $fees),
                    'grossRaw' => $gross,
                    'feesRaw' => $fees,
                    'netRaw' => $gross - $fees,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{plan: string, total: int, total_label: string}>
     */
    public static function revenueByPlan(?CarbonInterface $since = null): array
    {
        return Payment::query()
            ->where('payments.status', PaymentStatus::Paid->value)
            ->when($since, fn ($q) => $q->where('payments.paid_at', '>=', $since))
            ->join('consultation_requests', 'consultation_requests.id', '=', 'payments.consultation_request_id')
            ->leftJoin('plans', 'plans.id', '=', 'consultation_requests.plan_id')
            ->selectRaw('COALESCE(plans.name, ?) as plan, SUM(payments.amount) as total', ['Consultation'])
            ->groupBy('plan')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'plan' => $row->plan,
                'total' => (int) round($row->total / 100),
                'total_label' => self::money((int) $row->total),
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, total: int}>
     */
    public static function topCountries(int $limit = 6): array
    {
        return ConsultationRequest::query()
            ->whereNotNull('consultation_requests.country')
            ->leftJoin('countries', 'countries.code', '=', 'consultation_requests.country')
            ->selectRaw('COALESCE(countries.name, consultation_requests.country) as country_name, COUNT(*) as total')
            ->groupBy('countries.name', 'consultation_requests.country')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->country_name, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * Paid-payment totals (gross + fees) in a single query, optionally windowed.
     * Memoised per request with once() so stats() and financialComposition()
     * share one round trip instead of each summing amount and fee separately;
     * the captured $since is part of once()'s cache key, so each window gets
     * its own entry. toBase() skips model hydration/casting on the aggregate
     * row. once() is flushed between tests by the framework, so this never
     * serves stale data to the metric unit tests.
     */
    private static function paidPaymentTotals(?CarbonInterface $since = null): object
    {
        return once(fn () => Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->when($since, fn ($q) => $q->where('paid_at', '>=', $since))
            ->toBase()
            ->selectRaw('COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(fee), 0) as fee')
            ->first());
    }

    private static function paidOutTotal(?CarbonInterface $since = null): int
    {
        return once(fn () => (int) Payout::where('status', PayoutStatus::Paid->value)
            ->when($since, fn ($q) => $q->where('paid_at', '>=', $since))
            ->sum('amount'));
    }

    private static function taxCollected(?CarbonInterface $since = null): int
    {
        return once(fn () => (int) Invoice::where('status', InvoiceStatus::Paid->value)
            ->when($since, fn ($q) => $q->where('issued_at', '>=', $since))
            ->sum('tax_amount'));
    }

    private static function customToPriceCount(): int
    {
        return once(fn () => ConsultationRequest::whereNull('plan_id')
            ->where('amount', '<=', 0)
            ->where('status', ConsultationStatus::Pending->value)
            ->count());
    }

    private static function receivedBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return (int) Payment::where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');
    }

    private static function monthOverMonthDelta(int $thisMonth): ?int
    {
        // Compare month-to-date against the SAME window last month (start of last
        // month → same day-of-month) so a partial month isn't measured against a
        // full one — otherwise early in the month the delta reads wildly negative.
        $lastMonth = self::receivedBetween(
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow(),
        );

        return $lastMonth > 0
            ? (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100)
            : null;
    }

    private static function money(int $minor): string
    {
        return ConsultationRequest::formatMoneyMinor($minor, SettingsService::currencyCode());
    }

    /**
     * Money-flow figures (fees, net, per-gateway) shown to the cent — sub-dollar
     * processing fees would otherwise truncate to "$0" in the whole-dollar money().
     */
    private static function moneyExact(int $minor): string
    {
        return ConsultationRequest::formatMoneyMinorExact($minor, SettingsService::currencyCode());
    }
}

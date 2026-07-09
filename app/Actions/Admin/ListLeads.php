<?php

namespace App\Actions\Admin;

use App\Data\Lead\LeadData;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\Country;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ListLeads
{
    use AsAction;

    /** Won/Lost grow unbounded; cap each terminal column and surface the true count. */
    private const TERMINAL_LIMIT = 50;

    public function asController(Request $request)
    {
        $search = trim((string) $request->input('filter.search', ''));
        // Escape LIKE wildcards so "%" / "_" are matched literally, not as patterns.
        $term = '%'.addcslashes($search, '%_\\').'%';

        $searchWhere = fn ($q) => $q->where('reference', 'like', $term)
            ->orWhere('name', 'like', $term)
            ->orWhere('email', 'like', $term)
            ->orWhere('company', 'like', $term)
            ->orWhere('title', 'like', $term);

        // One grouped query for every column's (search-filtered) count, instead of
        // a separate COUNT per stage. toBase() keeps the keys as raw stage strings
        // regardless of any lead_stage cast. Stats below reuse this when unfiltered.
        $columnCounts = ConsultationRequest::leads()
            ->when($search !== '', fn ($q) => $q->where($searchWhere))
            ->toBase()
            ->groupBy('lead_stage')
            ->selectRaw('lead_stage, COUNT(*) as aggregate')
            ->pluck('aggregate', 'lead_stage');

        $columns = collect(LeadStage::cases())->map(function (LeadStage $stage) use ($search, $searchWhere, $columnCounts) {
            $base = ConsultationRequest::query()
                ->leads()
                ->where('lead_stage', $stage->value)
                ->when($search !== '', fn ($q) => $q->where($searchWhere));

            $count = (int) ($columnCounts[$stage->value] ?? 0);

            // Active columns: surface soonest/overdue follow-ups first. Terminal
            // columns: most recently changed, capped.
            $leads = $stage->isTerminal()
                ? $base->latest('updated_at')->limit(self::TERMINAL_LIMIT)->get()
                : $base->orderByRaw('next_follow_up_at is null')
                    ->orderBy('next_follow_up_at')
                    ->latest('created_at')
                    ->get();

            return [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'tone' => $stage->badge(),
                'count' => $count,
                'capped' => $stage->isTerminal() && $count > self::TERMINAL_LIMIT,
                'leads' => LeadData::collect($leads),
            ];
        })->values();

        // Pipeline KPIs are unfiltered. With no search the column counts already
        // are the unfiltered per-stage totals, so reuse them rather than fire a
        // second identical grouped query.
        $stageCounts = $search === ''
            ? $columnCounts
            : ConsultationRequest::leads()
                ->toBase()
                ->groupBy('lead_stage')
                ->selectRaw('lead_stage, COUNT(*) as aggregate')
                ->pluck('aggregate', 'lead_stage');

        $won = (int) ($stageCounts[LeadStage::Won->value] ?? 0);
        $lost = (int) ($stageCounts[LeadStage::Lost->value] ?? 0);
        $total = (int) $stageCounts->sum();

        return Inertia::render('admin/leads', [
            'columns' => $columns,
            'stageOptions' => LeadStage::options(),
            'countries' => Country::options(),
            'filters' => ['search' => $search],
            'stats' => [
                'total' => $total,
                'open' => $total - $won - $lost,
                'overdue' => ConsultationRequest::leads()
                    ->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '<', now()->startOfDay())
                    ->whereNotIn('lead_stage', [LeadStage::Won->value, LeadStage::Lost->value])
                    ->count(),
                'won' => $won,
            ],
        ]);
    }
}

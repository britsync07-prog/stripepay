<?php

namespace App\Actions\Admin;

use App\Actions\Concerns\RefreshesPayoutStatus;
use App\Data\Expert\ExpertListData;
use App\Models\Expert;
use App\Payments\PayoutRailManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListExperts
{
    use AsAction;
    use RefreshesPayoutStatus;

    public function asController(Request $request, PayoutRailManager $rails)
    {
        $search = (string) $request->input('filter.search', '');

        $experts = QueryBuilder::for(Expert::class)
            ->allowedFilters(...[
                AllowedFilter::callback('search', fn (Builder $query, $value) => $this->applySearch($query, (string) $value)),
            ])
            ->defaultSort('name')
            ->paginate(12)
            ->withQueryString();

        // Sync any mid-onboarding experts on this page from the rail (webhook-independent).
        $this->refreshPendingPayoutStatus($experts->getCollection(), $rails);

        return Inertia::render('admin/experts', [
            'experts' => $experts->through(fn (Expert $expert) => ExpertListData::fromModel($expert)),
            'filters' => ['search' => $search],
            // Stats mirror the active search so the cards always match the table below.
            'stats' => [
                'total' => $this->applySearch(Expert::query(), $search)->count(),
                'ready' => $this->applySearch(Expert::query(), $search)->where('stripe_payouts_enabled', true)->count(),
                'active' => $this->applySearch(Expert::query(), $search)->where('is_active', true)->count(),
            ],
        ]);
    }

    /** Apply the shared name/email/specialization search to any expert query. */
    protected function applySearch(Builder $query, string $value): Builder
    {
        if ($value === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('specialization', 'like', "%{$value}%");
        });
    }
}

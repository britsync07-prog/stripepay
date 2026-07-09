<?php

namespace App\Actions\Admin;

use App\Data\Expert\ExpertData;
use App\Data\Payout\PayoutData;
use App\Enums\PayoutStatus;
use App\Models\Expert;
use App\Models\Payout;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ShowExpert
{
    use AsAction;

    public function asController(Request $request, Expert $expert)
    {
        $payouts = QueryBuilder::for(Payout::where('expert_id', $expert->id))
            ->with('expert', 'media')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
            ])
            ->defaultSort('-created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/experts/show', [
            'expert' => ExpertData::fromModel($expert),
            'payouts' => $payouts->through(fn (Payout $payout) => PayoutData::fromModel($payout)),
            'filters' => ['status' => (string) $request->input('filter.status', '')],
            'statusOptions' => PayoutStatus::options(),
            'stats' => [
                'totalPaid' => Payout::formatMoneyMinor((int) $expert->payouts()->where('status', 'paid')->sum('amount'), SettingsService::currencyCode()),
                'count' => $expert->payouts()->count(),
                'pending' => $expert->payouts()->whereIn('status', ['pending', 'processing'])->count(),
            ],
        ]);
    }
}

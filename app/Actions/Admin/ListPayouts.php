<?php

namespace App\Actions\Admin;

use App\Actions\Concerns\RefreshesPayoutStatus;
use App\Data\Payout\PayoutData;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Payments\PayoutRailManager;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListPayouts
{
    use AsAction;
    use RefreshesPayoutStatus;

    public function asController(Request $request, PayoutRailManager $rails)
    {
        $payouts = QueryBuilder::for(Payout::class)
            ->with('expert', 'media')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhereHas('expert', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        // Settle any still-Processing payouts on this page (webhook-independent).
        $this->refreshProcessingPayouts($payouts->getCollection(), $rails);

        return Inertia::render('admin/payouts', [
            'payouts' => $payouts->through(fn (Payout $payout) => PayoutData::fromModel($payout)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => PayoutStatus::options(),
            'stats' => [
                'paidOut' => Payout::formatMoneyMinor((int) Payout::where('status', 'paid')->sum('amount'), SettingsService::currencyCode()),
                'pending' => Payout::whereIn('status', ['pending', 'processing'])->count(),
                'total' => Payout::count(),
            ],
        ]);
    }
}

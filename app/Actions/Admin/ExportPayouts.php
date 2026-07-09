<?php

namespace App\Actions\Admin;

use App\Exports\SpreadsheetExport;
use App\Models\Payout;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the payouts list to .xlsx, honouring the same search/status filters
 * as ListPayouts.
 */
class ExportPayouts
{
    use AsAction;

    public function asController(Request $request): StreamedResponse
    {
        $payouts = QueryBuilder::for(Payout::class)
            ->with('expert')
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
            ->get();

        $rows = $payouts->map(fn (Payout $payout) => [
            $payout->paid_at?->format('Y-m-d H:i') ?? $payout->created_at?->format('Y-m-d H:i'),
            $payout->reference,
            $payout->expert?->name,
            $payout->method->label(),
            Payout::formatMoneyMinorExact((int) $payout->amount, (string) $payout->currency),
            $payout->status->label(),
            $payout->paid_at?->format('Y-m-d H:i'),
            $payout->note,
        ])->all();

        return (new SpreadsheetExport(
            'Payouts',
            ['Date', 'Reference', 'Expert', 'Method', 'Amount', 'Status', 'Paid at', 'Note'],
            $rows,
        ))->download('payouts-'.now()->format('Y-m-d').'.xlsx');
    }
}

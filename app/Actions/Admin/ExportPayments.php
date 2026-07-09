<?php

namespace App\Actions\Admin;

use App\Exports\SpreadsheetExport;
use App\Models\Payment;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the payments list to .xlsx, honouring the same search/status filters
 * as ListPayments so "Export" always reflects what the admin is looking at.
 */
class ExportPayments
{
    use AsAction;

    public function asController(Request $request): StreamedResponse
    {
        $payments = QueryBuilder::for(Payment::class)
            ->with('consultationRequest.plan')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhere('gateway', 'like', "%{$value}%")
                            ->orWhereHas('consultationRequest.plan', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->get();

        $rows = $payments->map(fn (Payment $payment) => [
            $payment->paid_at?->format('Y-m-d H:i') ?? $payment->created_at?->format('Y-m-d H:i'),
            $payment->payload['transaction_id'] ?? $payment->reference,
            $payment->gateway,
            $payment->consultationRequest?->plan?->name ?? 'Consultation',
            $payment->consultationRequest?->uuid,
            Payment::formatMoneyMinorExact((int) $payment->amount, (string) $payment->currency),
            $payment->status->label(),
        ])->all();

        return (new SpreadsheetExport(
            'Payments',
            ['Date', 'Reference', 'Gateway', 'Plan', 'Consultation', 'Amount', 'Status'],
            $rows,
        ))->download('payments-'.now()->format('Y-m-d').'.xlsx');
    }
}

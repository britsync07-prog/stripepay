<?php

namespace App\Actions\Admin;

use App\Exports\SpreadsheetExport;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the consultations list to .xlsx, honouring the same search/status/type
 * filters as ListConsultations.
 */
class ExportConsultations
{
    use AsAction;

    public function asController(Request $request): StreamedResponse
    {
        $consultations = QueryBuilder::for(ConsultationRequest::class)
            ->with('plan', 'expert')
            ->allowedFilters(...[
                AllowedFilter::exact('status'),
                AllowedFilter::callback('type', function ($query, string $value) {
                    if ($value === 'requests') {
                        $query->whereNull('plan_id')->where('amount', '<=', 0);
                    } elseif ($value === 'consultations') {
                        $query->where(fn ($q) => $q->whereNotNull('plan_id')->orWhere('amount', '>', 0));
                    }
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                            ->orWhere('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('title', 'like', "%{$value}%")
                            ->orWhereHas('plan', fn ($q) => $q->where('name', 'like', "%{$value}%"));
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->get();

        $rows = $consultations->map(function (ConsultationRequest $c) {
            $unpriced = $c->isCustom() && (int) $c->amount <= 0;

            return [
                $c->reference,
                $c->offerTitle(),
                $c->name,
                $c->email,
                $c->phone,
                $c->countryName(),
                $unpriced ? 'Unpriced' : ConsultationRequest::formatMoneyMinorExact((int) $c->amount, (string) $c->currency),
                $c->status->label(),
                $c->work_status?->label(),
                $c->created_at?->format('Y-m-d H:i'),
                $c->paid_at?->format('Y-m-d H:i'),
            ];
        })->all();

        return (new SpreadsheetExport(
            'Consultations',
            ['Reference', 'Title', 'Name', 'Email', 'Phone', 'Country', 'Amount', 'Payment status', 'Work status', 'Created', 'Paid at'],
            $rows,
        ))->download('consultations-'.now()->format('Y-m-d').'.xlsx');
    }
}

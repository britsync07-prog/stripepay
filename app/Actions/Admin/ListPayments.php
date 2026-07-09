<?php

namespace App\Actions\Admin;

use App\Data\Payment\PaymentData;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListPayments
{
    use AsAction;

    public function asController(Request $request)
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
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/payments', [
            'payments' => $payments->through(fn (Payment $payment) => PaymentData::fromModel($payment)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => PaymentStatus::options(),
            'stats' => [
                'received' => Payment::formatMoneyMinor((int) Payment::where('status', 'paid')->sum('amount'), SettingsService::currencyCode()),
                'total' => Payment::count(),
                'refunded' => Payment::where('status', PaymentStatus::Refunded->value)->count(),
            ],
        ]);
    }
}

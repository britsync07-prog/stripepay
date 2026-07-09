<?php

namespace App\Actions\Admin;

use App\Data\Payment\PaymentData;
use App\Data\User\ClientData;
use App\Enums\PaymentStatus;
use App\Models\Country;
use App\Models\Payment;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ShowClient
{
    use AsAction;

    public function asController(Request $request, User $client)
    {
        abort_unless($client->hasRole('client'), 404);

        $ofClient = fn ($query) => $query->whereHas(
            'consultationRequest',
            fn ($q) => $q->where('user_id', $client->id),
        );

        $payments = QueryBuilder::for($ofClient(Payment::query()))
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
            ->paginate(10)
            ->withQueryString();

        $client->loadCount('consultationRequests')
            ->loadSum(
                ['consultationRequests as paid_amount' => fn ($q) => $q->where('status', 'paid')],
                'amount',
            );

        return Inertia::render('admin/clients/show', [
            'client' => ClientData::fromModel($client),
            'payments' => $payments->through(fn (Payment $payment) => PaymentData::fromModel($payment)),
            'filters' => [
                'search' => (string) $request->input('filter.search', ''),
                'status' => (string) $request->input('filter.status', ''),
            ],
            'statusOptions' => PaymentStatus::options(),
            'countries' => Country::options(),
            'stats' => [
                'spent' => Payment::formatMoneyMinor((int) $ofClient(Payment::query())->where('status', 'paid')->sum('amount'), SettingsService::currencyCode()),
                'payments' => $ofClient(Payment::query())->count(),
                'consultations' => $client->consultationRequests()->count(),
            ],
        ]);
    }
}

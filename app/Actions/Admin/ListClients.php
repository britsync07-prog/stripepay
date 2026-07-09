<?php

namespace App\Actions\Admin;

use App\Data\User\ClientData;
use App\Models\ConsultationRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListClients
{
    use AsAction;

    public function asController(Request $request)
    {
        $base = User::role('client')
            ->with('contact')
            ->withCount('consultationRequests')
            ->withSum(
                ['consultationRequests as paid_amount' => fn ($q) => $q->where('status', 'paid')],
                'amount',
            );

        $clients = QueryBuilder::for($base)
            ->allowedFilters(...[
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('admin/clients', [
            'clients' => $clients->through(fn (User $user) => ClientData::fromModel($user)),
            'filters' => ['search' => (string) $request->input('filter.search', '')],
            'countries' => Country::options(),
            'stats' => [
                'total' => User::role('client')->count(),
                'spent' => ConsultationRequest::formatMoneyMinor((int) ConsultationRequest::where('status', 'paid')->sum('amount'), SettingsService::currencyCode()),
            ],
        ]);
    }
}

<?php

namespace App\Actions\Admin;

use App\Exports\SpreadsheetExport;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the clients list to .xlsx, honouring the same search filter as
 * ListClients so "Export" always reflects what the admin is looking at.
 */
class ExportClients
{
    use AsAction;

    public function asController(Request $request): StreamedResponse
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
                    $query->where(fn ($q) => $q
                        ->where('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%"));
                }),
            ])
            ->defaultSort('-created_at')
            ->get();

        $rows = $clients->map(fn (User $user) => [
            $user->name,
            $user->email,
            $user->contact?->company,
            $user->contact?->country,
            $user->contact?->phone,
            (int) ($user->consultation_requests_count ?? 0),
            ConsultationRequest::formatMoneyMinorExact((int) ($user->paid_amount ?? 0), SettingsService::currencyCode()),
            $user->created_at?->format('Y-m-d'),
        ])->all();

        return (new SpreadsheetExport(
            'Clients',
            ['Name', 'Email', 'Company', 'Country', 'Phone', 'Consultations', 'Paid', 'Joined'],
            $rows,
        ))->download('clients-'.now()->format('Y-m-d').'.xlsx');
    }
}

<?php

namespace App\Actions\Clients;

use App\Data\ConsultationRequest\ConsultationData;
use App\Enums\ConsultationStatus;
use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientDashboard
{
    use AsAction;

    public function asController(Request $request)
    {
        $user = $request->user();

        $consultations = $user->consultationRequests()
            ->with('plan')
            ->latest()
            ->get();

        $paid = $consultations->where('status', ConsultationStatus::Paid);
        $currency = SettingsService::currencyCode();

        return Inertia::render('portal/dashboard', [
            'client' => [
                'name' => $user->name,
                'first_name' => Str::of($user->name)->explode(' ')->first(),
            ],
            'stats' => [
                'total' => $consultations->count(),
                'paid' => $paid->count(),
                'pending' => $consultations->where('status', ConsultationStatus::Pending)->count(),
                'spent' => ConsultationRequest::formatMoneyMinor((int) $paid->sum('amount'), $currency),
            ],
            'recent' => $consultations
                ->take(5)
                ->map(fn (ConsultationRequest $c) => ConsultationData::fromModelForClient($c))
                ->values(),
            'active' => $paid
                ->filter(fn (ConsultationRequest $c) => $c->work_status !== ConsultationWorkStatus::Closed)
                ->map(fn (ConsultationRequest $c) => [
                    'uuid' => $c->uuid,
                    'title' => $c->offerTitle(),
                    'work_status' => $c->work_status->value,
                    'work_status_label' => $c->work_status->label(),
                    'updated' => $c->updated_at?->diffForHumans(),
                ])
                ->values(),
            // Unpaid, priced, still-valid requests the client can settle right
            // now. Unpriced custom leads (amount 0) are awaiting admin review,
            // so they are not yet payable and must not appear here.
            'outstanding' => $consultations
                ->filter(fn (ConsultationRequest $c) => $c->status === ConsultationStatus::Pending
                    && (int) $c->amount > 0
                    && ! $c->isExpired())
                ->map(fn (ConsultationRequest $c) => [
                    'uuid' => $c->uuid,
                    'title' => $c->offerTitle(),
                    'amount' => $c->formattedAmount(),
                    'expiresAt' => $c->expires_at?->toFormattedDayDateString(),
                    'payUrl' => $c->checkoutUrl(),
                ])
                ->values(),
        ]);
    }
}

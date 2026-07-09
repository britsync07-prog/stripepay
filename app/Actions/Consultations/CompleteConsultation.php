<?php

namespace App\Actions\Consultations;

use App\Actions\Payments\RecordConsultationPayment;
use App\Data\Plan\PlanData;
use App\Models\ConsultationRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Renders the post-payment completion page. Payment recording happens upstream
 * in {@see ReturnFromGateway} → {@see RecordConsultationPayment},
 * so by the time the buyer lands here the consultation status is already settled.
 */
class CompleteConsultation
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        $consultationRequest->loadMissing('plan');

        return Inertia::render('consultation/complete', [
            'request' => [
                'name' => $consultationRequest->name,
                'email' => $consultationRequest->email,
                'status' => $consultationRequest->status->value,
                'status_label' => $consultationRequest->status->label(),
            ],
            'plan' => $consultationRequest->plan
                ? PlanData::fromModel($consultationRequest->plan)
                : null,
            'isAuthenticated' => Auth::check(),
            'portalUrl' => route('portal.dashboard'),
        ]);
    }
}

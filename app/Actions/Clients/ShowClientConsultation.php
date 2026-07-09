<?php

namespace App\Actions\Clients;

use App\Data\ConsultationRequest\ConsultationData;
use App\Data\Payment\PaymentData;
use App\Enums\ConsultationWorkStatus;
use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientConsultation
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->user_id === $request->user()->id, 404);

        $consultationRequest->load('plan', 'expert', 'payments');

        $work = $consultationRequest->work_status;

        $project = Project::where('consultation_request_id', $consultationRequest->id)->first();
        $invoice = Invoice::where('consultation_request_id', $consultationRequest->id)->first();

        return Inertia::render('portal/consultation', [
            'consultation' => ConsultationData::fromModelForClient($consultationRequest),
            'payments' => $consultationRequest->payments->map(fn (Payment $p) => PaymentData::fromModel($p)),
            'workStatus' => $work->value,
            'workStatusSteps' => ConsultationWorkStatus::progressSteps(),
            'related' => [
                'project' => $project ? ['uuid' => $project->uuid, 'reference' => $project->reference] : null,
                'invoice' => $invoice ? ['uuid' => $invoice->uuid, 'reference' => $invoice->reference] : null,
            ],
        ]);
    }
}

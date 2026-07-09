<?php

namespace App\Actions\Admin;

use App\Data\Activity\TimelineEventData;
use App\Data\ConsultationRequest\ConsultationData;
use App\Data\ConsultationRequest\PaymentLinkData;
use App\Data\Lead\LeadScoreData;
use App\Data\Media\MediaData;
use App\Data\Payment\PaymentData;
use App\Enums\ContactChannel;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\Invoice;
use App\Models\Project;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowConsultation
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest)
    {
        $consultationRequest->load('plan', 'expert');

        $work = $consultationRequest->work_status;
        $project = Project::where('consultation_request_id', $consultationRequest->id)->first();
        $invoice = Invoice::where('consultation_request_id', $consultationRequest->id)->first();

        return Inertia::render('admin/consultation', [
            'consultation' => ConsultationData::fromModel($consultationRequest),
            'paymentLink' => PaymentLinkData::fromModel($consultationRequest),
            'payments' => Inertia::defer(
                fn () => PaymentData::collect($consultationRequest->payments),
                'detail',
            ),
            'workStatus' => $work->value,
            'statusOptions' => $work->transitionOptions(),
            'channelOptions' => ContactChannel::options(),
            'experts' => Expert::activeOptions(),
            'assignedExpertId' => $consultationRequest->expert_id ? (string) $consultationRequest->expert_id : 'none',
            'leadScore' => $consultationRequest->isLead()
                ? LeadScoreData::fromModel($consultationRequest)
                : null,
            'project' => $project ? ['uuid' => $project->uuid, 'reference' => $project->reference] : null,
            'invoice' => $invoice ? ['uuid' => $invoice->uuid, 'reference' => $invoice->reference] : null,
            'attachments' => MediaData::collect($consultationRequest->getMedia('attachments')),
            'timeline' => Inertia::defer(
                fn () => TimelineEventData::forSubject($consultationRequest, 'consultation'),
                'detail',
            ),
        ]);
    }
}

<?php

namespace App\Actions\Admin;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Nudges a lead forward in the pipeline off the back of real activity (a logged
 * note → Contacted, an uploaded attachment → Quoted). Only ever moves forward,
 * never regresses, never touches a non-lead or a terminal (Won/Lost) stage, and
 * writes an activity('lead') entry so the timeline shows why the stage changed.
 */
class AutoAdvanceLead
{
    use AsAction;

    public function handle(ConsultationRequest $consultation, LeadStage $target, ?User $causer = null): bool
    {
        $from = $consultation->lead_stage;

        if ($from === null || ! $from->canAutoAdvanceTo($target)) {
            return false;
        }

        $consultation->update(['lead_stage' => $target]);

        activity('lead')
            ->causedBy($causer)
            ->performedOn($consultation)
            ->withProperties([
                'type' => 'stage',
                'from_label' => $from->label(),
                'to_label' => $target->label(),
                'message' => 'Lead advanced to '.$target->label(),
            ])
            ->log('Lead stage changed');

        return true;
    }
}

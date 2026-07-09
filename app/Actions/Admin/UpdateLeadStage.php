<?php

namespace App\Actions\Admin;

use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Moves a lead between pipeline stages (from a board drag or an inline change).
 * "Won" is reached only by a recorded payment, never by hand; moving to "Lost"
 * captures an optional reason and clears any pending follow-up.
 */
class UpdateLeadStage
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isLead(), 404);

        $data = $request->validate([
            'stage' => ['required', Rule::enum(LeadStage::class)],
            'lost_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $stage = LeadStage::from($data['stage']);
        $from = $consultationRequest->lead_stage;

        if ($from === $stage) {
            return back();
        }

        // Won is closed (the lead paid) — it is never re-opened by hand.
        if ($from === LeadStage::Won) {
            throw ValidationException::withMessages([
                'stage' => 'A won lead is closed and cannot be moved.',
            ]);
        }

        if ($stage === LeadStage::Won && ! $consultationRequest->isPaid()) {
            throw ValidationException::withMessages([
                'stage' => 'A lead becomes Won when its payment is recorded — send the payment link instead.',
            ]);
        }

        $attributes = ['lead_stage' => $stage];

        if ($stage === LeadStage::Lost) {
            $attributes['lost_reason'] = $data['lost_reason'] ?? null;
            $attributes['next_follow_up_at'] = null;
        } elseif ($from === LeadStage::Lost) {
            // Re-opening a lost lead — drop the now-stale reason.
            $attributes['lost_reason'] = null;
        }

        $consultationRequest->update($attributes);

        activity('lead')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'stage',
                'from_label' => $from?->label(),
                'to_label' => $stage->label(),
                'message' => 'Lead moved to '.$stage->label(),
            ])
            ->log('Lead stage changed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lead moved to '.$stage->label().'.']);

        return back();
    }
}

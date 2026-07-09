<?php

namespace App\Actions\Admin;

use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

/** Sets or clears a lead's next follow-up date (overdue dates are flagged on the board). */
class ScheduleLeadFollowUp
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        abort_unless($consultationRequest->isLead(), 404);

        $data = $request->validate([
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $consultationRequest->update(['next_follow_up_at' => $data['next_follow_up_at'] ?? null]);

        activity('lead')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'follow_up',
                'message' => $data['next_follow_up_at']
                    ? 'Follow-up set for '.$data['next_follow_up_at']
                    : 'Follow-up cleared',
            ])
            ->log('Lead follow-up updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Follow-up updated.']);

        return back();
    }
}

<?php

namespace App\Actions\Admin;

use App\Events\ExpertAssigned;
use App\Models\ConsultationRequest;
use App\Models\Expert;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class AssignExpert
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        $data = $request->validate([
            'expert_id' => ['nullable', Rule::exists('experts', 'id')->where('is_active', true)],
        ]);

        $expertId = ($data['expert_id'] ?? null) !== null ? (int) $data['expert_id'] : null;

        if ($consultationRequest->expert_id == $expertId) {
            return back();
        }

        $from = $consultationRequest->expert;

        $consultationRequest->update([
            'expert_id' => $expertId,
            'assigned_at' => $expertId ? now() : null,
        ]);

        $to = $expertId ? Expert::find($expertId) : null;

        ExpertAssigned::dispatch($consultationRequest, $to);

        activity('consultation')
            ->causedBy($request->user())
            ->performedOn($consultationRequest)
            ->withProperties([
                'type' => 'assigned',
                'from_label' => $from?->name,
                'to_label' => $to?->name ?? 'Unassigned',
            ])
            ->log('Expert assigned');

        Project::where('consultation_request_id', $consultationRequest->id)
            ->update(['expert_id' => $expertId]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $to ? "Assigned to {$to->name}." : 'Expert unassigned.',
        ]);

        return back();
    }
}

<?php

namespace App\Actions\Admin;

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMilestoneStatus
{
    use AsAction;

    public function asController(Request $request, Project $project, Milestone $milestone)
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::enum(MilestoneStatus::class)],
        ]);

        $from = $milestone->status;
        $to = MilestoneStatus::from($data['status']);

        if ($to !== $from && ! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Can't move from {$from->label()} to {$to->label()}.",
            ]);
        }

        $milestone->update([
            'status' => $to,
            'completed_at' => $to->isComplete() ? ($milestone->completed_at ?? now()) : null,
        ]);

        $project->recomputeCompletion();

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties([
                'type' => 'milestone',
                'message' => "Milestone \"{$milestone->title}\" → {$to->label()}",
            ])
            ->log('Milestone status changed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone updated.']);

        return back();
    }
}

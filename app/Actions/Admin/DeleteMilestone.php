<?php

namespace App\Actions\Admin;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteMilestone
{
    use AsAction;

    public function asController(Request $request, Project $project, Milestone $milestone)
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $title = $milestone->title;
        $milestone->delete();

        $project->recomputeCompletion();

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'milestone', 'message' => "Milestone \"{$title}\" removed"])
            ->log('Milestone removed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone removed.']);

        return back();
    }
}

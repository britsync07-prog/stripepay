<?php

namespace App\Actions\Admin;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMilestone
{
    use AsAction;

    public function asController(Request $request, Project $project, Milestone $milestone)
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $milestone->update($data);

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'milestone', 'message' => "Milestone \"{$milestone->title}\" updated"])
            ->log('Milestone updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone updated.']);

        return back();
    }
}

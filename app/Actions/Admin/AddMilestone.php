<?php

namespace App\Actions\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMilestone
{
    use AsAction;

    public function asController(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $milestone = $project->milestones()->create([
            ...$data,
            'sort_order' => (int) ($project->milestones()->max('sort_order') ?? 0) + 1,
        ]);

        $project->recomputeCompletion();

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'milestone', 'message' => "Milestone \"{$milestone->title}\" added"])
            ->log('Milestone added');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone added.']);

        return back();
    }
}

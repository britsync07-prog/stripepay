<?php

namespace App\Actions\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderMilestones
{
    use AsAction;

    public function asController(Request $request, Project $project)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($project, $data) {
            foreach ($data['ids'] as $position => $id) {
                $project->milestones()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back();
    }
}

<?php

namespace App\Actions\Clients;

use App\Data\Project\ProjectListData;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientProjects
{
    use AsAction;

    public function asController(Request $request)
    {
        $projects = $request->user()
            ->projects()
            ->with('expert')
            ->withCount('milestones')
            ->latest()
            ->get()
            ->map(fn (Project $p) => ProjectListData::fromModelForClient($p));

        return Inertia::render('portal/projects', [
            'projects' => $projects,
        ]);
    }
}

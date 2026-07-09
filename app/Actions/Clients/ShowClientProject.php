<?php

namespace App\Actions\Clients;

use App\Data\Media\MediaData;
use App\Data\Project\ProjectData;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowClientProject
{
    use AsAction;

    public function asController(Request $request, Project $project)
    {
        abort_unless($project->user_id === $request->user()->id, 404);

        $project->load('expert', 'consultationRequest', 'milestones');

        return Inertia::render('portal/project', [
            'project' => ProjectData::fromModel($project),
            'documents' => MediaData::collect($project->getMedia('documents')),
        ]);
    }
}

<?php

namespace App\Actions\Admin;

use App\Data\Media\MediaData;
use App\Data\Project\ProjectData;
use App\Data\Project\ProjectHealthData;
use App\Models\Project;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class ShowProject
{
    use AsAction;

    public function asController(Project $project)
    {
        $project->load('client', 'expert', 'consultationRequest', 'milestones');

        return Inertia::render('admin/projects/show', [
            'project' => ProjectData::fromModel($project),
            'health' => ProjectHealthData::fromModel($project),
            'documents' => MediaData::collect($project->getMedia('documents')),
            'consultationAttachments' => MediaData::collect(
                $project->consultationRequest?->getMedia('attachments') ?? collect()
            ),
        ]);
    }
}

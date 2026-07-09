<?php

namespace App\Actions\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteProjectDocument
{
    use AsAction;

    public function asController(Request $request, Project $project, Media $media)
    {
        abort_unless(
            $media->model_type === Project::class
                && (int) $media->model_id === $project->id
                && $media->collection_name === 'documents',
            404,
        );

        $name = $media->file_name;
        $media->delete();

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'document', 'message' => "Document \"{$name}\" removed"])
            ->log('Document removed');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document removed.']);

        return back();
    }
}

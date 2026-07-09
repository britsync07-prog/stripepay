<?php

namespace App\Actions\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadProjectDocument
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

        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $project->user_id === $user->id,
            403,
        );

        return $request->boolean('download')
            ? $media->toResponse($request)
            : $media->toInlineResponse($request);
    }
}

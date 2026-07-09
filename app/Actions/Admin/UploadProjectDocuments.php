<?php

namespace App\Actions\Admin;

use App\Models\Project;
use App\Services\Media\MediaUploader;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UploadProjectDocuments
{
    use AsAction;

    public function asController(Request $request, Project $project)
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => Uploads::documentRules(),
        ]);

        MediaUploader::make()->storeMany($project, $request->file('files'), 'documents');

        activity('project')
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['type' => 'document', 'message' => 'Documents uploaded'])
            ->log('Documents uploaded');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Documents uploaded.']);

        return back();
    }
}

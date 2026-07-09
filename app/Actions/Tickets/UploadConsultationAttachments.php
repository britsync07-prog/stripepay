<?php

namespace App\Actions\Tickets;

use App\Models\ConsultationRequest;
use App\Services\Media\MediaUploader;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;

class UploadConsultationAttachments
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest)
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => Uploads::documentRules(),
        ]);

        MediaUploader::make()->storeMany(
            $consultationRequest,
            $request->file('files'),
            'attachments',
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Files uploaded.']);

        return back();
    }
}

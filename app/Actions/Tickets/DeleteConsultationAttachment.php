<?php

namespace App\Actions\Tickets;

use App\Models\ConsultationRequest;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteConsultationAttachment
{
    use AsAction;

    public function asController(ConsultationRequest $consultationRequest, Media $media)
    {
        abort_unless(
            $media->model_type === ConsultationRequest::class
                && (int) $media->model_id === $consultationRequest->id
                && $media->collection_name === 'attachments',
            404,
        );

        $media->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attachment deleted.']);

        return back();
    }
}

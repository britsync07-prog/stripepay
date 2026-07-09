<?php

namespace App\Actions\Tickets;

use App\Models\ConsultationRequest;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadConsultationAttachment
{
    use AsAction;

    public function asController(Request $request, ConsultationRequest $consultationRequest, Media $media)
    {
        abort_unless(
            $media->model_type === ConsultationRequest::class
                && (int) $media->model_id === $consultationRequest->id
                && $media->collection_name === 'attachments',
            404,
        );

        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $consultationRequest->user_id === $user->id,
            403,
        );

        return $request->boolean('download')
            ? $media->toResponse($request)
            : $media->toInlineResponse($request);
    }
}

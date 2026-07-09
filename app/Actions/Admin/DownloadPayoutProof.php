<?php

namespace App\Actions\Admin;

use App\Models\Payout;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DownloadPayoutProof
{
    use AsAction;

    public function asController(Request $request, Payout $payout, Media $media)
    {
        abort_unless(
            $media->model_type === Payout::class
                && (int) $media->model_id === $payout->id
                && $media->collection_name === 'proof',
            404,
        );

        return $request->boolean('download')
            ? $media->toResponse($request)
            : $media->toInlineResponse($request);
    }
}

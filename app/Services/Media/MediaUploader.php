<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaUploader
{
    public static function make(): self
    {
        return new self;
    }

    public function store(HasMedia $model, UploadedFile $file, string $collection = 'attachments'): Media
    {
        return $model->addMedia($file)->toMediaCollection($collection);
    }

    /**
     * @param  iterable<UploadedFile>  $files
     * @return array<int, Media>
     */
    public function storeMany(HasMedia $model, iterable $files, string $collection = 'attachments'): array
    {
        return collect($files)
            ->map(fn (UploadedFile $file) => $this->store($model, $file, $collection))
            ->all();
    }

    public function remove(HasMedia $model, int $mediaId, ?string $collection = null): bool
    {
        $media = $model->media()
            ->when($collection !== null, fn ($q) => $q->where('collection_name', $collection))
            ->whereKey($mediaId)
            ->first();

        if ($media === null) {
            return false;
        }

        $media->delete();

        return true;
    }
}

<?php

namespace App\Data\Media;

use Spatie\LaravelData\Data;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $size,
        public bool $is_image,
        public bool $is_pdf,
        public ?string $mime_type,
        public ?string $created_at,
    ) {}

    public static function fromModel(Media $media): self
    {
        $mime = (string) $media->mime_type;

        return new self(
            id: $media->id,
            name: $media->file_name,
            size: self::humanSize((int) $media->size),
            is_image: str_starts_with($mime, 'image/'),
            is_pdf: $mime === 'application/pdf',
            mime_type: $media->mime_type,
            created_at: $media->created_at?->diffForHumans(),
        );
    }

    private static function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}

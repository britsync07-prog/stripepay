<?php

namespace App\Support;

class Uploads
{
    public const IMAGE_EXTENSIONS = 'jpg,jpeg,png,webp,gif,bmp,tif,tiff,heic,heif,avif';

    public const DOCUMENT_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,rtf,txt,csv,zip,rar,7z,'.self::IMAGE_EXTENSIONS;

    public const PROOF_EXTENSIONS = 'pdf,'.self::IMAGE_EXTENSIONS;

    public const MAX_KB = 20480;

    /**
     * Documents & attachments: docs, archives and images.
     *
     * @return array<int, string>
     */
    public static function documentRules(int $maxKb = self::MAX_KB): array
    {
        return ['file', 'max:'.$maxKb, 'extensions:'.self::DOCUMENT_EXTENSIONS];
    }

    /**
     * Images only (avatars, covers, screenshots) — incl. modern phone formats.
     *
     * @return array<int, string>
     */
    public static function imageRules(int $maxKb = 5120): array
    {
        return ['file', 'max:'.$maxKb, 'extensions:'.self::IMAGE_EXTENSIONS];
    }

    /**
     * Payment/payout proof: a receipt PDF or an image.
     *
     * @return array<int, string>
     */
    public static function proofRules(int $maxKb = 10240): array
    {
        return ['file', 'max:'.$maxKb, 'extensions:'.self::PROOF_EXTENSIONS];
    }

    /**
     * react-dropzone `accept` maps per profile — the single source of truth for
     * which file types each upload surface offers, shared to the frontend so it
     * never hardcodes them.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    public static function acceptMaps(): array
    {
        $images = self::dot(self::IMAGE_EXTENSIONS);

        return [
            'image' => ['image/*' => $images],
            'proof' => ['application/pdf' => ['.pdf'], 'image/*' => $images],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function dot(string $csv): array
    {
        return array_map(fn (string $ext) => '.'.$ext, explode(',', $csv));
    }
}

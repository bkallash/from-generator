<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;

final readonly class ImagePaths
{
    public function __construct(
        public string $originalPath,
        public string $thumbPath,
    ) {}
}

/**
 * Compresses an uploaded image and generates a thumbnail.
 *
 * - Original : quality-80 WebP, stored on the 'submissions' private disk
 * - Thumbnail: 160×160 cropped-centre WebP at quality 60 (≈ 5–15 KB)
 *
 * Storage path: {userId}/{formId}/{fieldId}/{uuid}.webp (and _thumb.webp)
 */
final class ImageUploadService
{
    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function store(
        UploadedFile $file,
        int          $userId,
        int          $formId,
        string       $fieldId,
    ): ImagePaths {
        $uuid    = (string) Str::uuid();
        $dir     = "{$userId}/{$formId}/{$fieldId}";
        $origKey = "{$dir}/{$uuid}.webp";
        $thumbKey = "{$dir}/{$uuid}_thumb.webp";

        $image = $this->manager->decodePath($file->getRealPath());

        // ── Compressed original (WebP q80) ──────────────────────────────
        $originalEncoded = $image
            ->scaleDown(width: 2400, height: 2400)   // never upscale
            ->encode(new WebpEncoder(80));

        Storage::disk('submissions')->put($origKey, (string) $originalEncoded);

        // ── Thumbnail 160×160 crop-centre (WebP q60) ────────────────────
        $thumbEncoded = $image
            ->cover(width: 160, height: 160)
            ->encode(new WebpEncoder(60));

        Storage::disk('submissions')->put($thumbKey, (string) $thumbEncoded);

        return new ImagePaths(
            originalPath: $origKey,
            thumbPath:    $thumbKey,
        );
    }
}


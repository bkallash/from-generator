<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\JpegEncoder;

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
 * - Original : quality-80 WebP (or Jpeg fallback), stored on the 'submissions' private disk
 * - Thumbnail: 160×160 cropped-centre WebP (or Jpeg fallback) at quality 60 (≈ 5–15 KB)
 *
 * Storage path: {userId}/{formId}/{fieldId}/{uuid}.webp (and _thumb.webp)
 */
final class ImageUploadService
{
    private readonly ImageManager $manager;

    public function __construct()
    {
        if (extension_loaded('imagick') && class_exists(\Intervention\Image\Drivers\Imagick\Driver::class)) {
            $driver = new \Intervention\Image\Drivers\Imagick\Driver();
        } else {
            $driver = new \Intervention\Image\Drivers\Gd\Driver();
        }
        $this->manager = new ImageManager($driver);
    }

    public function store(
        UploadedFile $file,
        int          $userId,
        int          $formId,
        string       $fieldId,
    ): ImagePaths {
        $uuid    = (string) Str::uuid();
        $dir     = "{$userId}/{$formId}/{$fieldId}";

        // Initialise outside try so the return statement can always reference them
        $origKey  = '';
        $thumbKey = '';

        // Store the upload in the private submissions disk first to avoid /tmp
        // or open_basedir restrictions that are common on shared/cPanel hosts.
        $tempPath = $file->storeAs('temp', $uuid, 'submissions');

        try {
            // Use decodeBinary() with raw content instead of decodePath() — avoids filesystem
            // path resolution issues inside Railway/Docker containers.
            $image = $this->manager->decodeBinary(
                Storage::disk('submissions')->get($tempPath)
            );

            // Try to encode as WebP first, fallback to Jpeg if not supported
            try {
                $originalEncoded = $image
                    ->scaleDown(width: 2400, height: 2400)   // never upscale
                    ->encode(new WebpEncoder(80));
                $extension = 'webp';
                $thumbSuffix = '_thumb.webp';
            } catch (\Throwable $e) {
                $originalEncoded = $image
                    ->scaleDown(width: 2400, height: 2400)   // never upscale
                    ->encode(new JpegEncoder(80));
                $extension = 'jpg';
                $thumbSuffix = '_thumb.jpg';
            }

            $origKey = "{$dir}/{$uuid}.{$extension}";
            $thumbKey = "{$dir}/{$uuid}{$thumbSuffix}";

            // Use ->toString() instead of (string) cast — safer on strict production PHP builds
            Storage::disk('submissions')->put($origKey, $originalEncoded->toString());

            // Generate thumbnail in the same format
            if ($extension === 'webp') {
                $thumbEncoded = $image
                    ->cover(width: 160, height: 160)
                    ->encode(new WebpEncoder(60));
            } else {
                $thumbEncoded = $image
                    ->cover(width: 160, height: 160)
                    ->encode(new JpegEncoder(60));
            }

            Storage::disk('submissions')->put($thumbKey, $thumbEncoded->toString());
        } finally {
            // Always clean up the temporary file
            Storage::disk('submissions')->delete($tempPath);
        }

        return new ImagePaths(
            originalPath: $origKey,
            thumbPath:    $thumbKey,
        );
    }
}

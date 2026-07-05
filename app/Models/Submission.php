<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Submission extends Model
{
    protected $fillable = [
        'form_id',
        'content',
        'ip_address',
        'ai_metadata',
    ];

    protected $casts = [
        'content' => 'array', // Automatically decodes JSON to Array
        'ai_metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (Submission $submission): void {
            $submission->clearDashboardCaches();
            dispatch(new \App\Jobs\AnalyzeSubmissionSentiment($submission));
        });

        static::saved(function (Submission $submission): void {
            if (! $submission->wasRecentlyCreated) {
                $submission->clearDashboardCaches();
            }
        });

        static::deleting(function (Submission $submission): void {
            foreach ($submission->uploadedFilePaths() as $path) {
                // Delete from submissions private disk
                try {
                    if (Storage::disk('submissions')->exists($path)) {
                        Storage::disk('submissions')->delete($path);
                    }
                    // Delete thumbnail if it exists
                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    $basePath = substr($path, 0, -(strlen($extension) + 1));
                    $thumbPath = $basePath . '_thumb.' . $extension;
                    if (Storage::disk('submissions')->exists($thumbPath)) {
                        Storage::disk('submissions')->delete($thumbPath);
                    }
                } catch (Throwable) {
                    // Ignore
                }

                // Check local/public for legacy files
                foreach (['local', 'public'] as $disk) {
                    try {
                        if (Storage::disk($disk)->exists($path)) {
                            Storage::disk($disk)->delete($path);
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }
        });

        static::deleted(function (Submission $submission): void {
            $submission->clearDashboardCaches();
        });
    }

    public function clearDashboardCaches(): void
    {
        $userId = $this->form?->user_id;

        if (! $userId) {
            return;
        }

        Cache::forget("dashboard_overview_{$userId}");
        Cache::forget("user_anomaly_alerts_{$userId}");
    }

    protected function uploadedFilePaths(): array
    {
        $paths = [];
        $content = (array) $this->content;

        array_walk_recursive($content, function ($value) use (&$paths): void {
            if (is_string($value)) {
                // Legacy path starts with submissions/
                // New paths look like: {userId}/{formId}/{fieldId}/{uuid}.webp (or similar)
                if (str_starts_with($value, 'submissions/') || preg_match('/^\d+\/\d+\/field_/', $value)) {
                    $paths[] = $value;
                }
            }
        });

        return array_values(array_unique($paths));
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}

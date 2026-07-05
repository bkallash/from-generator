<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Form extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'heading',
        'description',
        'slug',
        'schema',
        'settings',
        'ai_insights',
        'ai_insights_updated_at',
    ];

    protected $casts = [
        'schema' => 'array', // Automatically decodes JSON to Array
        'settings' => 'array',
        'is_active' => 'boolean',
        'ai_insights_updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (Form $form): void {
            self::clearDashboardCaches((int) $form->user_id);
        });

        static::deleting(function (Form $form): void {
            $form->submissions()->chunkById(100, function ($submissions): void {
                foreach ($submissions as $submission) {
                    $submission->delete();
                }
            });
        });

        static::deleted(function (Form $form): void {
            self::clearDashboardCaches((int) $form->user_id);
        });
    }

    public static function clearDashboardCaches(int $userId): void
    {
        Cache::forget("dashboard_overview_{$userId}");
        Cache::forget("user_anomaly_alerts_{$userId}");
    }

    public function getPages(): array
    {
        if (isset($this->schema['pages'])) {
            return $this->schema['pages'];
        }

        if (isset($this->schema['fields'])) {
            return [
                [
                    'id' => 'page_1',
                    'title' => '',
                    'description' => '',
                    'fields' => $this->schema['fields'],
                    'conditionalLogic' => null,
                ]
            ];
        }

        return [];
    }

    public function getPageFields(int $pageIndex): array
    {
        $pages = $this->getPages();
        return $pages[$pageIndex]['fields'] ?? [];
    }

    public function getPageCount(): int
    {
        return count($this->getPages());
    }

    public function getFields(): array
    {
        $fields = [];
        foreach ($this->getPages() as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    public function drafts()
    {
        return $this->hasMany(FormDraft::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Submission;
use App\Services\AiAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateIntelligenceDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiAnalyticsService $aiAnalyticsService): void
    {
        // 1. Clear any existing anomaly cache to ensure a fresh generation
        Cache::forget("user_anomaly_alerts_{$this->user->id}");

        // 2. Detect alerts
        $alerts = $aiAnalyticsService->detectAnomalies($this->user);
        usort($alerts, fn($a, $b) => ($b['severity'] ?? 0) <=> ($a['severity'] ?? 0));

        // 3. Gather active form stats for the digest
        $formsBaseQuery = $this->user->forms();
        $formStats = (clone $formsBaseQuery)
            ->selectRaw('COUNT(*) as total_forms, SUM(CASE WHEN is_active = ? THEN 1 ELSE 0 END) as active_forms', [true])
            ->first();

        $totalForms = (int) ($formStats?->total_forms ?? 0);
        $activeForms = (int) ($formStats?->active_forms ?? 0);
        
        $formIds = $this->user->forms()->select('id')->pluck('id')->all();
        $thisWeekSubmissions = Submission::query()
            ->whereIn('form_id', $formIds)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        // 4. Generate AI Digest
        $aiDigest = $aiAnalyticsService->generateDashboardDigest($this->user, $totalForms, $activeForms, $thisWeekSubmissions);

        $alertCacheKey = md5($this->user->id . '_' . floor(time() / 36000));

        // 5. Save everything in a unified cache with 10 hours TTL
        Cache::put("user_intelligence_data_{$this->user->id}", [
            'status' => 'ready',
            'aiAlerts' => $alerts,
            'aiDigest' => $aiDigest,
            'alertCacheKey' => $alertCacheKey,
        ], 36000);
    }
}

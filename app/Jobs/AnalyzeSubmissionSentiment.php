<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Submission;
use App\Services\AiAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AnalyzeSubmissionSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Submission $submission
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiAnalyticsService $service): void
    {
        $form = $this->submission->form;
        if (!$form) {
            return;
        }

        if (!$service->isEnabled()) {
            return;
        }

        $sentiments = $service->analyzeSubmissionSentiment(
            $this->submission->content,
            $form->getFields()
        );

        if (!empty($sentiments)) {
            $metadata = $this->submission->ai_metadata ?? [];
            $metadata['sentiment'] = $sentiments;
            
            $this->submission->update([
                'ai_metadata' => $metadata
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('AnalyzeSubmissionSentiment job failed: ' . $e->getMessage(), [
            'submission_id' => $this->submission->id
        ]);
    }
}

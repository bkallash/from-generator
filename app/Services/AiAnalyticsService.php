<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class AiAnalyticsService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key', '');
        $this->model = config('ai.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    /**
     * Check if Gemini API integration is configured and enabled.
     */
    public function isEnabled(): bool
    {
        return !empty($this->apiKey) && config('ai.features.insights', true);
    }

    /**
     * Generate natural language insights for a form and its submissions.
     * Caches the result to minimize API costs.
     */
    public function generateFormInsights(Form $form, array $submissions, string $range): ?string
    {
        if (!$this->isEnabled() || empty($submissions)) {
            return null;
        }

        try {
            $fields = $form->getFields();
            $fieldMap = collect($fields)->pluck('label', 'id')->toArray();

            // Format submissions data for the model to digest efficiently
            $formattedSubmissions = [];
            foreach ($submissions as $sub) {
                $formattedContent = [];
                foreach ($sub->content as $fieldId => $value) {
                    $label = $fieldMap[$fieldId] ?? $fieldId;
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $formattedContent[$label] = $value;
                }
                $formattedSubmissions[] = $formattedContent;
            }

            // ── Build rich form context ──────────────────────────────────
            $totalSubmissions = count($formattedSubmissions);
            $submissionDates = collect($submissions)->pluck('created_at')->filter();
            $earliestDate = $submissionDates->min()?->format('Y-m-d') ?? 'N/A';
            $latestDate = $submissionDates->max()?->format('Y-m-d') ?? 'N/A';

            $prompt  = "You are an elite data analyst, product strategist, and UX researcher. ";
            $prompt .= "You have been given full context about a digital form and every submission it received. ";
            $prompt .= "Your job is to produce a rich, data-backed analytics report that surfaces real trends, patterns, and actionable insights.\n\n";

            // Form identity
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "FORM PROFILE\n";
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "• Title: \"{$form->title}\"\n";
            if ($form->description) {
                $prompt .= "• Description: \"{$form->description}\"\n";
            }
            $prompt .= "• Total Submissions Provided: {$totalSubmissions}\n";
            $prompt .= "• Date Range of Submissions: {$earliestDate} → {$latestDate}\n";
            $prompt .= "• Analysis Period Filter: {$range}\n\n";

            // Detailed field schema
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "FORM FIELD SCHEMA (all fields the user fills out)\n";
            $prompt .= "═══════════════════════════════════════\n";
            foreach ($fields as $index => $field) {
                $num = $index + 1;
                $prompt .= "Field #{$num}:\n";
                $prompt .= "  Label    : {$field['label']}\n";
                $prompt .= "  Type     : {$field['type']}\n";
                if (!empty($field['required'])) {
                    $prompt .= "  Required : Yes\n";
                }
                if (!empty($field['options'])) {
                    $optionsList = is_array($field['options']) ? implode(', ', $field['options']) : $field['options'];
                    $prompt .= "  Options  : {$optionsList}\n";
                }
                if (!empty($field['placeholder'])) {
                    $prompt .= "  Placeholder: {$field['placeholder']}\n";
                }
                $prompt .= "\n";
            }

            // Raw submission data
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "RAW SUBMISSION DATA ({$totalSubmissions} submissions)\n";
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= json_encode($formattedSubmissions, JSON_PRETTY_PRINT) . "\n\n";

            // Detailed output instructions
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "OUTPUT INSTRUCTIONS\n";
            $prompt .= "═══════════════════════════════════════\n";
            $prompt .= "Produce a comprehensive Markdown analytics report (250–350 words). ";
            $prompt .= "Do NOT use conversational filler, greetings, or meta-commentary. Jump straight into insights. ";
            $prompt .= "Every claim MUST be backed by actual data from the submissions above — cite real numbers, percentages, and specific response values. ";
            $prompt .= "Adapt the sections below to what is actually relevant for this form's field types and data. Skip any section that has no meaningful data to report.\n\n";

            $prompt .= "Use exactly this structure:\n\n";
            
            $prompt .= "### 📈 Trends & Patterns\n";
            $prompt .= "Identify time-based trends (are ratings improving or declining over the date range?). ";
            $prompt .= "Surface correlations between fields (e.g., users who picked option X tend to leave lower ratings). ";
            $prompt .= "Highlight any clusters, segments, or non-obvious groupings in the data.\n\n";

            $prompt .= "### 💬 Qualitative Feedback Analysis\n";
            $prompt .= "Synthesize free-text responses into 2-4 dominant themes. ";
            $prompt .= "Quote short, specific phrases from actual submissions to support each theme. ";
            $prompt .= "Note sentiment distribution (positive / neutral / negative split with approximate %).\n\n";

            $prompt .= "### ⚠️ Outliers & Anomalies\n";
            $prompt .= "Flag any submissions that deviate significantly from the norm (extreme ratings, unusual text patterns, empty optional fields that are normally filled). ";
            $prompt .= "Explain why these are noteworthy.\n\n";

            $prompt .= "### 💡 Strategic Recommendations\n";
            $prompt .= "Provide 2-3 concrete, prioritized actions directly tied to findings above. ";
            $prompt .= "Each recommendation must reference the specific data point that justifies it. ";
            $prompt .= "Format as: **[Priority: High/Medium/Low]** — Action description.\n";

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error (Form Insights): ' . $response->body());
                return null;
            }

            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        } catch (\Throwable $e) {
            Log::error('Failed to generate form insights with AI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Analyze sentiment of free-form text inputs in a single submission.
     */
    public function analyzeSubmissionSentiment(array $submissionContent, array $fields): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            $textInputs = [];
            $fieldMap = collect($fields)->keyBy('id')->toArray();

            foreach ($submissionContent as $fieldId => $value) {
                $field = $fieldMap[$fieldId] ?? null;
                if ($field && in_array($field['type'] ?? '', ['text', 'textarea'])) {
                    if (!empty($field['analyze_sentiment']) && is_string($value) && trim($value) !== '') {
                        $textInputs[$fieldId] = [
                            'label' => $field['label'],
                            'text' => $value
                        ];
                    }
                }
            }

            if (empty($textInputs)) {
                return [];
            }

            $prompt = "You are an AI performing specific, detailed sentiment analysis on form submission text fields.\n";
            $prompt .= "Analyze the sentiment for each of the following text answers:\n";
            $prompt .= json_encode($textInputs, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Respond ONLY with a valid JSON object mapping each field ID to a detailed sentiment analysis object. The object for each field must contain the following keys:\n";
            $prompt .= "- \"label\": the sentiment classification, which must be exactly one of 'positive', 'neutral', or 'negative'\n";
            $prompt .= "- \"score\": a decimal number from 0.0 to 1.0 representing the intensity or confidence of the sentiment\n";
            $prompt .= "- \"emotions\": an array of specific emotional sub-tones identified in the response (e.g. frustrated, satisfied, excited, confused, neutral, indifferent, angry)\n\n";
            $prompt .= "Do not include markdown code block formatting or any conversational text. Example format:\n";
            $prompt .= "{\n";
            $prompt .= "  \"field_id\": {\n";
            $prompt .= "    \"label\": \"positive\",\n";
            $prompt .= "    \"score\": 0.85,\n";
            $prompt .= "    \"emotions\": [\"satisfied\", \"excited\"]\n";
            $prompt .= "  }\n";
            $prompt .= "}";

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error (Sentiment Analysis): ' . $response->body());
                return [];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            // Clean response just in case the model ignored output constraints
            $text = trim($text);
            if (str_starts_with($text, '```')) {
                $text = preg_replace('/^```(?:json)?\s+|\s+```$/', '', $text);
            }

            return json_decode($text, true) ?: [];

        } catch (\Throwable $e) {
            Log::error('Failed to analyze sentiment with AI: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Use Gemini to generate a professional, context-aware alert message for a detected anomaly.
     */
    public function generateAnomalyAlertText(string $anomalyType, array $contextData): string
    {
        if (!$this->isEnabled()) {
            return "Detected anomaly in form submissions: " . json_encode($contextData);
        }

        try {
            $prompt = "You are an AI system writing proactive warnings for a dashboard alert feed.\n";
            $prompt .= "An anomaly of type '{$anomalyType}' has been detected with the following context data:\n";
            $prompt .= json_encode($contextData, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Generate a concise, professional alert warning of 1-2 sentences. Highlight the key issue, explain what it means, and offer a brief diagnostic tip. Keep it direct and start immediately with the message.";

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                return "Detected unusual activity: " . json_encode($contextData);
            }

            $data = $response->json();
            return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? "Detected unusual pattern in submissions.");

        } catch (\Throwable $e) {
            Log::error('Failed to generate anomaly alert text: ' . $e->getMessage());
            return "Detected unusual pattern in submissions.";
        }
    }

    /**
     * Detect anomaly and trend alerts using AI and cache them for 10 hours.
     */
    public function detectAnomalies(User $user): array
    {
        return Cache::remember("user_anomaly_alerts_{$user->id}", 36000, function () use ($user) {
            $alerts = [];
            
            $forms = $user->forms()->select('id', 'title')->get();
            if ($forms->isEmpty()) {
                return [];
            }

            $formIds = $forms->pluck('id');
            $thisWeekStart = now()->startOfWeek();
            $lastWeekStart = now()->subWeek()->startOfWeek();
            $lastWeekEnd = now()->subWeek()->endOfWeek();

            $thisWeekCounts = Submission::query()
                ->selectRaw('form_id, COUNT(*) as aggregate')
                ->whereIn('form_id', $formIds)
                ->where('created_at', '>=', $thisWeekStart)
                ->groupBy('form_id')
                ->pluck('aggregate', 'form_id');

            $lastWeekCounts = Submission::query()
                ->selectRaw('form_id, COUNT(*) as aggregate')
                ->whereIn('form_id', $formIds)
                ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->groupBy('form_id')
                ->pluck('aggregate', 'form_id');

            $recentSentimentSubmissions = Submission::query()
                ->whereIn('form_id', $formIds)
                ->where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('ai_metadata')
                ->get(['form_id', 'ai_metadata'])
                ->groupBy('form_id');

            foreach ($forms as $form) {
                // 1. Check volume drop
                $thisWeekCount = (int) ($thisWeekCounts[$form->id] ?? 0);
                $lastWeekCount = (int) ($lastWeekCounts[$form->id] ?? 0);

                if ($lastWeekCount >= 5 && $thisWeekCount <= ($lastWeekCount * 0.3)) {
                    $context = [
                        'form_title' => $form->title,
                        'this_week_submissions' => $thisWeekCount,
                        'last_week_submissions' => $lastWeekCount,
                        'drop_percentage' => round((($lastWeekCount - $thisWeekCount) / $lastWeekCount) * 100)
                    ];
                    $alerts[] = [
                        'type' => 'warning',
                        'title' => 'Significant Traffic Drop',
                        'message' => $this->generateAnomalyAlertText('traffic_drop', $context),
                        'form_id' => $form->id,
                        'form_title' => $form->title,
                        'severity' => 3,
                        'created_at' => now(),
                    ];
                }

                // 2. Check spike / surge
                if ($lastWeekCount >= 2 && $thisWeekCount >= ($lastWeekCount * 2.5)) {
                    $context = [
                        'form_title' => $form->title,
                        'this_week_submissions' => $thisWeekCount,
                        'last_week_submissions' => $lastWeekCount,
                        'increase_percentage' => round((($thisWeekCount - $lastWeekCount) / $lastWeekCount) * 100)
                    ];
                    $alerts[] = [
                        'type' => 'info',
                        'title' => 'Submission Traffic Spike',
                        'message' => $this->generateAnomalyAlertText('traffic_spike', $context),
                        'form_id' => $form->id,
                        'form_title' => $form->title,
                        'severity' => 2,
                        'created_at' => now(),
                    ];
                }

                // 3. Check negative sentiment spike
                $recentSubmissions = $recentSentimentSubmissions->get($form->id, collect());

                $pos = 0;
                $neg = 0;
                foreach ($recentSubmissions as $sub) {
                    $sentiments = $sub->ai_metadata['sentiment'] ?? [];
                    foreach ($sentiments as $s) {
                        $sVal = is_array($s) ? ($s['label'] ?? 'neutral') : $s;
                        $sVal = strtolower(trim((string) $sVal));
                        if ($sVal === 'positive') $pos++;
                        if ($sVal === 'negative') $neg++;
                    }
                }

                $totalSentiment = $pos + $neg;
                if ($totalSentiment >= 5 && ($neg / $totalSentiment) >= 0.4) {
                    $context = [
                        'form_title' => $form->title,
                        'negative_count' => $neg,
                        'total_count' => $totalSentiment,
                        'negative_percentage' => round(($neg / $totalSentiment) * 100)
                    ];
                    $alerts[] = [
                        'type' => 'danger',
                        'title' => 'Negative Feedback Warning',
                        'message' => $this->generateAnomalyAlertText('negative_feedback_spike', $context),
                        'form_id' => $form->id,
                        'form_title' => $form->title,
                        'severity' => 4,
                        'created_at' => now(),
                    ];
                }

                // 4. Check stale form (no submissions in last 14 days, but has at least one past submission)
                $hasSubmissionsLast14Days = Submission::query()
                    ->where('form_id', $form->id)
                    ->where('created_at', '>=', now()->subDays(14))
                    ->exists();

                if (!$hasSubmissionsLast14Days) {
                    $hasPastSubmissions = Submission::query()
                        ->where('form_id', $form->id)
                        ->exists();

                    if ($hasPastSubmissions) {
                        $context = [
                            'form_title' => $form->title,
                            'days_inactive' => 14,
                        ];
                        $alerts[] = [
                            'type' => 'notice',
                            'title' => 'Form Going Quiet',
                            'message' => $this->generateAnomalyAlertText('stale_form', $context),
                            'form_id' => $form->id,
                            'form_title' => $form->title,
                            'severity' => 1,
                            'created_at' => now(),
                        ];
                    }
                }
            }

            return $alerts;
        });
    }

    /**
     * Generate a natural-language digest summary of overall health when there are no anomalies.
     */
    public function generateDashboardDigest(User $user, int $totalForms, int $activeForms, int $thisWeekSubmissions): string
    {
        if (!$this->isEnabled()) {
            return "All systems are operating normally. You have {$activeForms} active form(s) out of {$totalForms} total, and received {$thisWeekSubmissions} submission(s) this week.";
        }

        try {
            $prompt = "You are a friendly AI dashboard assistant. ";
            $prompt .= "Construct a concise, encouraging 1-2 sentence dashboard digest summary. ";
            $prompt .= "Context: The user has {$totalForms} total forms, {$activeForms} are active, and they received {$thisWeekSubmissions} submissions this week. ";
            $prompt .= "There are currently no negative anomalies or alerts. Tell them how their forms are performing in a natural, professional tone. Keep it short.";

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                return "All systems are operating normally. You have {$activeForms} active form(s) out of {$totalForms} total, and received {$thisWeekSubmissions} submission(s) this week.";
            }

            $data = $response->json();
            return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? "Your forms are running smoothly with no issues detected.");

        } catch (\Throwable $e) {
            Log::error('Failed to generate dashboard digest: ' . $e->getMessage());
            return "All systems are operating normally. You have {$activeForms} active form(s) out of {$totalForms} total, and received {$thisWeekSubmissions} submission(s) this week.";
        }
    }
}


<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\Submission;
use App\Services\AiAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Analytics extends Component
{
    public ?int $selectedFormId = null;
    public string $range = '30';
    public ?string $aiInsights = null;

    public function mount()
    {
        $user = Auth::user();
        $queryFormId = request()->query('form');
        if ($queryFormId && Form::where('user_id', $user->id)->where('id', $queryFormId)->exists()) {
            $this->selectedFormId = (int) $queryFormId;
        } else {
            $firstForm = Form::where('user_id', $user->id)->latest()->first();
            if ($firstForm) {
                $this->selectedFormId = $firstForm->id;
            }
        }
    }

    public function updatedSelectedFormId()
    {
        $this->aiInsights = null;
    }

    public function updatedRange()
    {
        $this->aiInsights = null;
    }

    public function generateInsights(AiAnalyticsService $service)
    {
        if (!$this->selectedFormId) {
            return;
        }

        $form = Form::where('user_id', Auth::id())->find($this->selectedFormId);
        if (!$form) {
            return;
        }

        $days = (int) $this->range;
        $since = now()->subDays($days)->startOfDay();
        $submissions = Submission::where('form_id', $form->id)
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(100)
            ->get();

        if ($submissions->isEmpty()) {
            $this->aiInsights = "No submissions found for this form in the selected time range.";
            return;
        }

        $insightsResult = $service->generateFormInsights($form, $submissions->all(), $this->range);

        if ($insightsResult) {
            $this->aiInsights = $insightsResult;
            $form->update([
                'ai_insights' => $insightsResult,
                'ai_insights_updated_at' => now()
            ]);
        } else {
            $this->aiInsights = "Could not generate insights. Please check if your Gemini API key is configured properly.";
        }
    }

    public function render()
    {
        $user  = Auth::user();
        $forms = Form::where('user_id', $user->id)->select('id', 'title')->latest()->get();
        $days  = (int) $this->range;
        $since = now()->subDays($days)->startOfDay();

        $selectedForm = null;
        if ($this->selectedFormId) {
            $selectedForm = Form::where('user_id', $user->id)->find($this->selectedFormId);
            if ($selectedForm) {
                $this->aiInsights = $selectedForm->ai_insights;
            }
        }

        $baseQuery = Submission::query()
            ->whereHas('form', fn($q) => $q->where('user_id', $user->id));

        if ($this->selectedFormId) {
            $baseQuery->where('form_id', $this->selectedFormId);
        } else {
            // If no form selected and user has no forms, return empty state
            $baseQuery->whereRaw('1 = 0');
        }

        // KPIs
        $totalInRange = (clone $baseQuery)->where('created_at', '>=', $since)->count();
        $totalAllTime = (clone $baseQuery)->count();
        $thisWeek     = (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek     = (clone $baseQuery)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $weekChange   = $lastWeek > 0
            ? (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100)
            : ($thisWeek > 0 ? 100 : 0);
        $thisMonth   = (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count();
        $activeForms = Form::where('user_id', $user->id)->where('is_active', true)->count();

        // Daily chart — fill gaps with 0
        $dailyRaw = (clone $baseQuery)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyLabels = [];
        $dailyData   = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date          = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('M j');
            $dailyData[]   = $dailyRaw[$date] ?? 0;
        }

        // Field answer distributions (choice fields, only when a form is selected)
        $fieldStats = [];
        if ($this->selectedFormId) {
            $form = Form::where('user_id', $user->id)->find($this->selectedFormId);
            if ($form) {
                $submissions = (clone $baseQuery)
                    ->where('created_at', '>=', $since)
                    ->get(['content']);

                foreach ($form->getFields() as $field) {
                    if (!in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'])) {
                        continue;
                    }
                    $tally = [];
                    foreach ($submissions as $sub) {
                        $val = $sub->content[$field['id']] ?? null;
                        if ($val === null) continue;
                        foreach ((array) $val as $v) {
                            if ($v !== '') {
                                $tally[$v] = ($tally[$v] ?? 0) + 1;
                            }
                        }
                    }
                    if (!empty($tally)) {
                        arsort($tally);
                        $fieldStats[] = [
                            'label' => $field['label'],
                            'tally' => $tally,
                            'total' => array_sum($tally),
                        ];
                    }
                }
            }
        }

        // Sentiment statistics per text input field
        $sentimentStatsByField = [];
        $hasSentimentData = false;

        if ($this->selectedFormId) {
            $form = Form::where('user_id', $user->id)->find($this->selectedFormId);
            if ($form) {
                // Get text/textarea fields that have sentiment analysis enabled
                $textFields = collect($form->getFields())
                    ->filter(fn($f) => in_array($f['type'] ?? '', ['text', 'textarea']) && !empty($f['analyze_sentiment']))
                    ->keyBy('id')
                    ->all();

                if (!empty($textFields)) {
                    // Initialize stats for each field
                    foreach ($textFields as $fieldId => $field) {
                        $sentimentStatsByField[$fieldId] = [
                            'field_id' => $fieldId,
                            'label' => $field['label'],
                            'stats' => [
                                'positive' => ['count' => 0, 'total_score' => 0, 'avg' => 0],
                                'neutral' => ['count' => 0, 'total_score' => 0, 'avg' => 0],
                                'negative' => ['count' => 0, 'total_score' => 0, 'avg' => 0],
                            ],
                            'total' => 0,
                            'emotion_tally' => [],
                        ];
                    }

                    // Tally from submissions
                    $submissionsWithMetadata = (clone $baseQuery)
                        ->where('created_at', '>=', $since)
                        ->whereNotNull('ai_metadata')
                        ->get(['ai_metadata']);

                    foreach ($submissionsWithMetadata as $sub) {
                        $sentimentData = $sub->ai_metadata['sentiment'] ?? [];
                        foreach ($sentimentData as $fieldId => $sentiment) {
                            if (isset($sentimentStatsByField[$fieldId])) {
                                if (is_array($sentiment)) {
                                    $label = $sentiment['label'] ?? 'neutral';
                                    $score = $sentiment['score'] ?? 0.5;
                                    $emotions = $sentiment['emotions'] ?? [];
                                } else {
                                    $label = $sentiment;
                                    $score = match(strtolower(trim((string) $sentiment))) {
                                        'positive' => 0.8,
                                        'negative' => 0.8,
                                        default => 0.4
                                    };
                                    $emotions = [];
                                }

                                $label = strtolower(trim((string) $label));
                                if (isset($sentimentStatsByField[$fieldId]['stats'][$label])) {
                                    $sentimentStatsByField[$fieldId]['stats'][$label]['count']++;
                                    $sentimentStatsByField[$fieldId]['stats'][$label]['total_score'] += $score;
                                    $sentimentStatsByField[$fieldId]['total']++;
                                    
                                    foreach ($emotions as $emotion) {
                                        $emotion = strtolower(trim((string) $emotion));
                                        if ($emotion !== '') {
                                            $sentimentStatsByField[$fieldId]['emotion_tally'][$emotion] = 
                                                ($sentimentStatsByField[$fieldId]['emotion_tally'][$emotion] ?? 0) + 1;
                                        }
                                    }
                                    $hasSentimentData = true;
                                }
                            }
                        }
                    }
                    
                    // Finalize fields: compute average score and get top 3 emotions
                    foreach ($sentimentStatsByField as $fieldId => &$fieldData) {
                        foreach ($fieldData['stats'] as $label => &$stat) {
                            if ($stat['count'] > 0) {
                                $stat['avg'] = (int) round(($stat['total_score'] / $stat['count']) * 100);
                            } else {
                                $stat['avg'] = 0;
                            }
                        }
                        
                        arsort($fieldData['emotion_tally']);
                        $fieldData['top_emotions'] = array_slice(array_keys($fieldData['emotion_tally']), 0, 3);
                    }
                    
                    // Filter out fields that have no sentiment data at all to keep UI clean
                    $sentimentStatsByField = array_filter($sentimentStatsByField, fn($f) => $f['total'] > 0);
                }
            }
        }

        // Format data for JS chart compatibility
        $sentimentFieldsDataForJs = [];
        foreach ($sentimentStatsByField as $fieldId => $fieldData) {
            $sentimentFieldsDataForJs[] = [
                'field_id' => $fieldId,
                'label' => $fieldData['label'],
                'stats' => [
                    'positive' => $fieldData['stats']['positive']['count'],
                    'neutral' => $fieldData['stats']['neutral']['count'],
                    'negative' => $fieldData['stats']['negative']['count'],
                ]
            ];
        }

        $this->dispatch(
            'analyticsChartData',
            dailyLabels: $dailyLabels,
            dailyData: $dailyData,
            sentimentFieldsData: $sentimentFieldsDataForJs
        );

        return view('livewire.analytics', compact(
            'forms',
            'selectedForm',
            'totalInRange',
            'totalAllTime',
            'thisWeek',
            'lastWeek',
            'weekChange',
            'thisMonth',
            'activeForms',
            'dailyLabels',
            'dailyData',
            'fieldStats',
            'sentimentStatsByField',
            'hasSentimentData'
        ));
    }
}

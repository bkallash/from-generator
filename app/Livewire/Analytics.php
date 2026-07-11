<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Services\AiAnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Analytics extends Component
{
    public ?int $selectedFormId = null;

    public string $range = '30';

    public ?string $aiInsights = null;

    /**
     * True after the component has hydrated from a subsequent Livewire request.
     * Used so we only dispatch chart updates on filter changes — not first paint
     * (first paint builds charts from embedded @js data in the Blade script).
     */
    protected bool $shouldDispatchCharts = false;

    public function mount(): void
    {
        $user = Auth::user();
        $queryFormId = request()->query('form');

        if ($queryFormId && Form::where('user_id', $user->id)->where('id', $queryFormId)->exists()) {
            $this->selectedFormId = (int) $queryFormId;

            return;
        }

        $firstForm = Form::where('user_id', $user->id)->latest()->first();
        if ($firstForm) {
            $this->selectedFormId = $firstForm->id;
        }
    }

    public function hydrate(): void
    {
        $this->shouldDispatchCharts = true;
    }

    public function updatedSelectedFormId(): void
    {
        $this->aiInsights = null;
    }

    public function updatedRange(): void
    {
        $this->aiInsights = null;
    }

    public function generateInsights(AiAnalyticsService $service): void
    {
        if (! $this->selectedFormId) {
            return;
        }

        $form = Form::where('user_id', Auth::id())->find($this->selectedFormId);
        if (! $form) {
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
            $this->aiInsights = 'No submissions found for this form in the selected time range.';

            return;
        }

        $insightsResult = $service->generateFormInsights($form, $submissions->all(), $this->range);

        if ($insightsResult) {
            $this->aiInsights = $insightsResult;
            $form->update([
                'ai_insights' => $insightsResult,
                'ai_insights_updated_at' => now(),
            ]);
        } else {
            $this->aiInsights = 'Could not generate insights. Please check if your Gemini API key is configured properly.';
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $forms = Form::where('user_id', $user->id)->select('id', 'title')->latest()->get();
        $days = (int) $this->range;
        $since = now()->subDays($days)->startOfDay();

        $selectedForm = $this->resolveSelectedForm($user);
        // Only hydrate from DB when unset — preserves ephemeral messages from generateInsights()
        // (empty-range / API errors) that are not persisted on the form.
        if ($selectedForm && $this->aiInsights === null) {
            $this->aiInsights = $selectedForm->ai_insights;
        }

        $baseQuery = $this->submissionQuery($user);

        $kpis = $this->buildKpis($baseQuery, $user, $since);
        [$dailyLabels, $dailyData] = $this->buildDailySeries($baseQuery, $days, $since);
        $fieldStats = $this->buildFieldStats($baseQuery, $user, $since);
        [$sentimentStatsByField, $hasSentimentData] = $this->buildSentimentStats($baseQuery, $user, $since);
        $sentimentFieldsData = $this->formatSentimentForJs($sentimentStatsByField);

        $this->dispatchChartDataIfNeeded(
            $dailyLabels,
            $dailyData,
            $sentimentFieldsData
        );

        return view('livewire.analytics', [
            'forms' => $forms,
            'selectedForm' => $selectedForm,
            'totalInRange' => $kpis['totalInRange'],
            'totalAllTime' => $kpis['totalAllTime'],
            'thisWeek' => $kpis['thisWeek'],
            'lastWeek' => $kpis['lastWeek'],
            'weekChange' => $kpis['weekChange'],
            'thisMonth' => $kpis['thisMonth'],
            'activeForms' => $kpis['activeForms'],
            'dailyLabels' => $dailyLabels,
            'dailyData' => $dailyData,
            'fieldStats' => $fieldStats,
            'sentimentStatsByField' => $sentimentStatsByField,
            'sentimentFieldsData' => $sentimentFieldsData,
            'hasSentimentData' => $hasSentimentData,
        ]);
    }

    private function resolveSelectedForm(User $user): ?Form
    {
        if (! $this->selectedFormId) {
            return null;
        }

        return Form::where('user_id', $user->id)->find($this->selectedFormId);
    }

    /**
     * @return Builder<Submission>
     */
    private function submissionQuery(User $user): Builder
    {
        $query = Submission::query()
            ->whereHas('form', fn (Builder $q) => $q->where('user_id', $user->id));

        if ($this->selectedFormId) {
            return $query->where('form_id', $this->selectedFormId);
        }

        // No form selected — force empty result set
        return $query->whereRaw('1 = 0');
    }

    /**
     * @param  Builder<Submission>  $baseQuery
     * @return array{
     *     totalInRange: int,
     *     totalAllTime: int,
     *     thisWeek: int,
     *     lastWeek: int,
     *     weekChange: int,
     *     thisMonth: int,
     *     activeForms: int
     * }
     */
    private function buildKpis(Builder $baseQuery, User $user, Carbon $since): array
    {
        $totalInRange = (clone $baseQuery)->where('created_at', '>=', $since)->count();
        $totalAllTime = (clone $baseQuery)->count();
        $thisWeek = (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = (clone $baseQuery)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $weekChange = $lastWeek > 0
            ? (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100)
            : ($thisWeek > 0 ? 100 : 0);
        $thisMonth = (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count();
        $activeForms = Form::where('user_id', $user->id)->where('is_active', true)->count();

        return compact(
            'totalInRange',
            'totalAllTime',
            'thisWeek',
            'lastWeek',
            'weekChange',
            'thisMonth',
            'activeForms',
        );
    }

    /**
     * @param  Builder<Submission>  $baseQuery
     * @return array{0: list<string>, 1: list<int>}
     */
    private function buildDailySeries(Builder $baseQuery, int $days, Carbon $since): array
    {
        $dailyRaw = (clone $baseQuery)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyLabels = [];
        $dailyData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('M j');
            $dailyData[] = (int) ($dailyRaw[$date] ?? 0);
        }

        return [$dailyLabels, $dailyData];
    }

    /**
     * Choice-field (select / radio / checkbox) answer tallies.
     *
     * @param  Builder<Submission>  $baseQuery
     * @return list<array{label: string, tally: array<string, int>, total: int}>
     */
    private function buildFieldStats(Builder $baseQuery, User $user, Carbon $since): array
    {
        if (! $this->selectedFormId) {
            return [];
        }

        $form = Form::where('user_id', $user->id)->find($this->selectedFormId);
        if (! $form) {
            return [];
        }

        $submissions = (clone $baseQuery)
            ->where('created_at', '>=', $since)
            ->get(['content']);

        $fieldStats = [];

        foreach ($form->getFields() as $field) {
            if (! in_array($field['type'] ?? '', ['select', 'radio', 'checkbox'], true)) {
                continue;
            }

            $tally = [];
            foreach ($submissions as $sub) {
                $val = $sub->content[$field['id']] ?? null;
                if ($val === null) {
                    continue;
                }
                foreach ((array) $val as $v) {
                    if ($v !== '') {
                        $tally[$v] = ($tally[$v] ?? 0) + 1;
                    }
                }
            }

            if ($tally !== []) {
                arsort($tally);
                $fieldStats[] = [
                    'label' => $field['label'],
                    'tally' => $tally,
                    'total' => array_sum($tally),
                ];
            }
        }

        return $fieldStats;
    }

    /**
     * Per text-field sentiment aggregates from submission AI metadata.
     *
     * @param  Builder<Submission>  $baseQuery
     * @return array{0: array<string, array<string, mixed>>, 1: bool}
     */
    private function buildSentimentStats(Builder $baseQuery, User $user, Carbon $since): array
    {
        if (! $this->selectedFormId) {
            return [[], false];
        }

        $form = Form::where('user_id', $user->id)->find($this->selectedFormId);
        if (! $form) {
            return [[], false];
        }

        $textFields = collect($form->getFields())
            ->filter(fn (array $f): bool => in_array($f['type'] ?? '', ['text', 'textarea'], true) && ! empty($f['analyze_sentiment']))
            ->keyBy('id')
            ->all();

        if ($textFields === []) {
            return [[], false];
        }

        $sentimentStatsByField = [];
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

        $hasSentimentData = false;
        $submissionsWithMetadata = (clone $baseQuery)
            ->where('created_at', '>=', $since)
            ->whereNotNull('ai_metadata')
            ->get(['ai_metadata']);

        foreach ($submissionsWithMetadata as $sub) {
            $sentimentData = $sub->ai_metadata['sentiment'] ?? [];
            foreach ($sentimentData as $fieldId => $sentiment) {
                if (! isset($sentimentStatsByField[$fieldId])) {
                    continue;
                }

                if (is_array($sentiment)) {
                    $label = $sentiment['label'] ?? 'neutral';
                    $score = $sentiment['score'] ?? 0.5;
                    $emotions = $sentiment['emotions'] ?? [];
                } else {
                    $label = $sentiment;
                    $score = match (strtolower(trim((string) $sentiment))) {
                        'positive' => 0.8,
                        'negative' => 0.8,
                        default => 0.4,
                    };
                    $emotions = [];
                }

                $label = strtolower(trim((string) $label));
                if (! isset($sentimentStatsByField[$fieldId]['stats'][$label])) {
                    continue;
                }

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

        foreach ($sentimentStatsByField as $fieldId => &$fieldData) {
            foreach ($fieldData['stats'] as $statLabel => &$stat) {
                $stat['avg'] = $stat['count'] > 0
                    ? (int) round(($stat['total_score'] / $stat['count']) * 100)
                    : 0;
            }
            unset($stat);

            arsort($fieldData['emotion_tally']);
            $fieldData['top_emotions'] = array_slice(array_keys($fieldData['emotion_tally']), 0, 3);
        }
        unset($fieldData);

        // Drop fields with no data to keep the UI clean
        $sentimentStatsByField = array_filter(
            $sentimentStatsByField,
            fn (array $f): bool => $f['total'] > 0
        );

        return [$sentimentStatsByField, $hasSentimentData];
    }

    /**
     * @param  array<string, array<string, mixed>>  $sentimentStatsByField
     * @return list<array{field_id: string|int, label: mixed, stats: array{positive: int, neutral: int, negative: int}}>
     */
    private function formatSentimentForJs(array $sentimentStatsByField): array
    {
        $payload = [];

        foreach ($sentimentStatsByField as $fieldId => $fieldData) {
            $payload[] = [
                'field_id' => $fieldId,
                'label' => $fieldData['label'],
                'stats' => [
                    'positive' => $fieldData['stats']['positive']['count'],
                    'neutral' => $fieldData['stats']['neutral']['count'],
                    'negative' => $fieldData['stats']['negative']['count'],
                ],
            ];
        }

        return $payload;
    }

    /**
     * @param  list<string>  $dailyLabels
     * @param  list<int>  $dailyData
     * @param  list<array{field_id: string|int, label: mixed, stats: array{positive: int, neutral: int, negative: int}}>  $sentimentFieldsDataForJs
     */
    private function dispatchChartDataIfNeeded(
        array $dailyLabels,
        array $dailyData,
        array $sentimentFieldsDataForJs,
    ): void {
        // Only push chart events on subsequent Livewire updates (filter/range).
        // Initial page load builds charts once from Blade @js — double-build was
        // destroy→empty canvas→recreate, which flashed white on dark cards.
        if (! $this->shouldDispatchCharts) {
            return;
        }

        $this->dispatch(
            'analyticsChartData',
            dailyLabels: $dailyLabels,
            dailyData: $dailyData,
            sentimentFieldsData: $sentimentFieldsDataForJs
        );
    }
}

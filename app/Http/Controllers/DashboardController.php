<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Services\AiAnalyticsService;
use App\Jobs\GenerateIntelligenceDataJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const VIEWS = ['dashboard', 'forms', 'submissions', 'analytics', 'settings'];
    private const DASHBOARD_CACHE_TTL = 60;

    public function __construct(
        private readonly AiAnalyticsService $aiAnalyticsService,
    ) {}

    /**
     * Display the dashboard view and dispatch to specific helper methods based on the current active view.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $requestedView = (string) $request->query('view', 'dashboard');
        $view = in_array($requestedView, self::VIEWS, true) ? $requestedView : 'dashboard';

        $data = match ($view) {
            'forms'       => $this->getFormsData($user),
            'submissions' => $this->getSubmissionsData($user, $request),
            'dashboard'   => $this->getOverviewData($user),
            default       => [],
        };

        return view('dashboard', array_merge([
            'user' => $user,
            'view' => $view,
        ], $data));
    }

    /**
     * Get the JSON intelligence alerts and digest.
     */
    public function getIntelligenceData(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = Cache::get("user_intelligence_data_{$user->id}");

        if (!$data) {
            $data = [
                'status' => 'loading',
                'aiAlerts' => [],
                'aiDigest' => 'AI is generating intelligence in the background...',
                'alertCacheKey' => md5($user->id . '_' . floor(time() / 36000)),
            ];
            Cache::put("user_intelligence_data_{$user->id}", $data, 120);
            GenerateIntelligenceDataJob::dispatch($user);
        }

        return response()->json($data);
    }

    private function getOverviewData(User $user): array
    {
        // 1. Fetch background-cached intelligence data (or trigger background generation)
        $intelData = Cache::get("user_intelligence_data_{$user->id}");
        if (!$intelData) {
            $intelData = [
                'status' => 'loading',
                'aiAlerts' => [],
                'aiDigest' => 'AI is generating intelligence in the background...',
                'alertCacheKey' => md5($user->id . '_' . floor(time() / 36000)),
            ];
            Cache::put("user_intelligence_data_{$user->id}", $intelData, 120);
            GenerateIntelligenceDataJob::dispatch($user);
        }

        // 2. Load other dashboard overview metrics (cached for 60 seconds)
        $overviewMetrics = Cache::remember("dashboard_overview_metrics_{$user->id}", self::DASHBOARD_CACHE_TTL, function () use ($user): array {
            $formsBaseQuery = $user->forms();

            $formStats = (clone $formsBaseQuery)
                ->selectRaw('COUNT(*) as total_forms, SUM(CASE WHEN is_active = ? THEN 1 ELSE 0 END) as active_forms', [true])
                ->first();

            $totalForms = (int) ($formStats?->total_forms ?? 0);
            $activeForms = (int) ($formStats?->active_forms ?? 0);
            
            $formIds = $this->userFormIdsQuery($user)->pluck('id')->all();

            $totalSubmissions = Submission::query()
                ->whereIn('form_id', $formIds)
                ->count();

            $thisWeekSubmissions = Submission::query()
                ->whereIn('form_id', $formIds)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count();

            // Form Health Map (submissions in last 7 days and positive sentiment %)
            $healthForms = $user->forms()->select('id', 'title', 'slug', 'is_active')->get();
            $formHealthMap = [];
            
            $sevenDaysAgo = now()->subDays(7);
            
            $subsLast7Days = Submission::query()
                ->selectRaw('form_id, COUNT(*) as count')
                ->whereIn('form_id', $formIds)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->groupBy('form_id')
                ->pluck('count', 'form_id');

            $sentimentSubmissions = Submission::query()
                ->whereIn('form_id', $formIds)
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('ai_metadata')
                ->get(['form_id', 'ai_metadata'])
                ->groupBy('form_id');

            foreach ($healthForms as $form) {
                $formSubsCount7d = (int) ($subsLast7Days[$form->id] ?? 0);
                
                $formRecentSubs = $sentimentSubmissions->get($form->id, collect());
                $pos = 0;
                $neg = 0;
                $neu = 0;
                foreach ($formRecentSubs as $sub) {
                    $sentiments = $sub->ai_metadata['sentiment'] ?? [];
                    foreach ($sentiments as $s) {
                        $sVal = is_array($s) ? ($s['label'] ?? 'neutral') : $s;
                        $sVal = strtolower(trim((string) $sVal));
                        if ($sVal === 'positive') $pos++;
                        elseif ($sVal === 'negative') $neg++;
                        elseif ($sVal === 'neutral') $neu++;
                    }
                }
                $totalSentiment = $pos + $neg + $neu;
                $positivePct = $totalSentiment > 0 ? round(($pos / $totalSentiment) * 100) : null;
                
                $formHealthMap[] = [
                    'id' => $form->id,
                    'title' => $form->title,
                    'slug' => $form->slug,
                    'is_active' => $form->is_active,
                    'submissions_7d' => $formSubsCount7d,
                    'positive_sentiment_pct' => $positivePct,
                ];
            }

            return [
                'totalForms'          => $totalForms,
                'activeForms'         => $activeForms,
                'totalSubmissions'    => $totalSubmissions,
                'thisWeekSubmissions' => $thisWeekSubmissions,
                'formHealthMap'       => $formHealthMap,
            ];
        });

        // 3. Return combined payload
        return array_merge($overviewMetrics, [
            'alertsStatus'  => $intelData['status'] ?? 'ready',
            'aiAlerts'      => $intelData['aiAlerts'] ?? [],
            'aiDigest'      => $intelData['aiDigest'] ?? '',
            'alertCacheKey' => $intelData['alertCacheKey'] ?? md5($user->id . '_' . floor(time() / 36000)),
        ]);
    }

    /**
     * Get data for the forms dashboard view.
     */
    private function getFormsData(User $user): array
    {
        $forms = $user->forms()
            ->select('id', 'user_id', 'title', 'slug', 'is_active', 'created_at')
            ->withCount('submissions')
            ->latest()
            ->simplePaginate(10)
            ->appends(['view' => 'forms']);

        return [
            'forms' => $forms,
        ];
    }

    /**
     * Get data for the submissions dashboard view.
     */
    private function getSubmissionsData(User $user, Request $request): array
    {
        $userFormsSimple = $user->forms()->select('id', 'title')->latest()->get();
        $formFilter  = $request->query('sform');
        $searchQuery = $request->query('ssearch');
        $userFormIds = $userFormsSimple->pluck('id');

        if ($formFilter && ! $userFormIds->contains((int) $formFilter)) {
            $formFilter = null;
        }

        if (! $formFilter && $userFormsSimple->isNotEmpty()) {
            $formFilter = $userFormsSimple->first()->id;
            $request->merge(['sform' => $formFilter]);
        }

        $submissionsQuery = Submission::query()
            ->with('form:id,title,schema')
            ->orderBy('created_at', 'desc');

        if ($formFilter) {
            $submissionsQuery->where('form_id', $formFilter);
        } else {
            $submissionsQuery->whereIn('form_id', $this->userFormIdsQuery($user));
        }

        if ($searchQuery) {
            $submissionsQuery->where('content', 'like', "%{$searchQuery}%");
        }

        $submissions = $submissionsQuery
            ->simplePaginate(15, ['id', 'form_id', 'content', 'ip_address', 'ai_metadata', 'created_at'], 'spage')
            ->appends(array_filter([
                'view'    => 'submissions',
                'sform'   => $formFilter,
                'ssearch' => $searchQuery,
            ]));

        return [
            'submissions'     => $submissions,
            'submissionRows'  => $this->buildSubmissionRows($submissions->getCollection()),
            'userFormsSimple' => $userFormsSimple,
        ];
    }

    private function userFormIdsQuery(User $user): Builder
    {
        return $user->forms()->select('id')->getQuery();
    }

    private function buildSubmissionRows(Collection $submissions): Collection
    {
        return $submissions->map(fn(Submission $submission): array => $this->buildSubmissionRow($submission));
    }

    private function buildSubmissionRow(Submission $submission): array
    {
        $form = $submission->form;
        $fields = $form->getFields();
        $content = $submission->content ?? [];
        $previewItems = [];
        $fileUrls = [];
        $fileImageFlags = [];
        $fileInlineImages = [];
        $fileNames = [];
        $firstImageUrl = null;
        $firstImageName = null;
        $firstImageFieldId = null;

        foreach ($fields as $field) {
            $fieldId = $field['id'];
            $value = $content[$fieldId] ?? null;

            if (in_array($field['type'] ?? null, ['file', 'image'], true) && is_string($value) && $value !== '') {
                $fileNames[$fieldId] = basename($value);
                $fileUrls[$fieldId] = route('submissions.files.download', [
                    'submission' => $submission->id,
                    'fieldId' => $fieldId,
                ], false);

                $isImage = (($field['type'] ?? null) === 'image');

                $fileImageFlags[$fieldId] = $isImage;

                if ($isImage) {
                    $fileInlineImages[$fieldId] = route('submissions.files.thumbnail', [
                        'submission' => $submission->id,
                        'fieldId' => $fieldId,
                    ], false);
                }

                if ($isImage && $firstImageUrl === null) {
                    $firstImageUrl = $fileInlineImages[$fieldId] ?? $fileUrls[$fieldId];
                    $firstImageName = $fileNames[$fieldId] ?? 'image';
                    $firstImageFieldId = $fieldId;
                }
            }

            if (count($previewItems) >= 2) {
                continue;
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $displayValue = is_array($value) ? implode(', ', $value) : $value;

                if (in_array($field['type'] ?? null, ['file', 'image'], true)) {
                    $displayValue = ($fileImageFlags[$fieldId] ?? false) ? 'Image uploaded' : 'File uploaded';
                }

                $previewItems[] = [
                    'label' => $field['label'],
                    'value' => Str::limit((string) $displayValue, 60),
                ];
            }
        }

        $modalData = [
            'id' => $submission->id,
            'formTitle' => $form->title,
            'createdAt' => $submission->created_at->format('M d, Y \a\t g:i A'),
            'ipAddress' => $submission->ip_address,
            'pages' => collect($form->getPages())
                ->map(function (array $page, int $pageIndex): array {
                    $pageFields = collect($page['fields'] ?? [])
                        ->filter(fn(array $field): bool => in_array($field['type'] ?? null, ['text', 'email', 'number', 'phone', 'date', 'url', 'textarea', 'select', 'radio', 'checkbox', 'file', 'image'], true))
                        ->map(fn(array $field): array => [
                            'id' => $field['id'],
                            'label' => $field['label'],
                            'type' => $field['type'] ?? 'text',
                        ])
                        ->values()
                        ->all();

                    return [
                        'title' => ($page['title'] ?? '') ?: 'Page ' . ($pageIndex + 1),
                        'description' => $page['description'] ?? '',
                        'fields' => $pageFields,
                    ];
                })
                ->values()
                ->all(),
            'content' => $content,
            'fileUrls' => $fileUrls,
            'fileImageFlags' => $fileImageFlags,
            'fileInlineImages' => $fileInlineImages,
            'fileNames' => $fileNames,
            'aiMetadata' => $submission->ai_metadata,
            'deleteUrl' => route('submissions.destroy', $submission->id),
        ];

        return [
            'id' => $submission->id,
            'formTitle' => $form->title,
            'submittedAtTitle' => $submission->created_at->format('Y-m-d H:i:s'),
            'submittedAtHuman' => $submission->created_at->diffForHumans(),
            'ipAddress' => $submission->ip_address ?? '-',
            'previewItems' => $previewItems,
            'firstImageUrl' => $firstImageUrl,
            'firstImageName' => $firstImageName,
            'firstImageFieldId' => $firstImageFieldId,
            'sentiment' => $this->resolveSubmissionSentiment($submission->ai_metadata['sentiment'] ?? null),
            'modalData' => $modalData,
            'deleteUrl' => route('submissions.destroy', $submission->id),
        ];
    }

    private function resolveSubmissionSentiment(mixed $sentiments): ?array
    {
        if (! is_array($sentiments)) {
            return null;
        }

        $tally = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
        $hasSentiment = false;

        foreach ($sentiments as $sentiment) {
            $value = is_array($sentiment) ? ($sentiment['label'] ?? 'neutral') : $sentiment;
            $value = strtolower(trim((string) $value));

            if (isset($tally[$value])) {
                $tally[$value]++;
                $hasSentiment = true;
            }
        }

        if (! $hasSentiment) {
            return null;
        }

        $label = match (true) {
            $tally['positive'] > $tally['negative'] => 'positive',
            $tally['negative'] > $tally['positive'] => 'negative',
            default => 'neutral',
        };

        $class = match ($label) {
            'positive' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50',
            'negative' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border-rose-200 dark:border-rose-900/50',
            default => 'bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400 border-neutral-200 dark:border-neutral-800',
        };

        return [
            'label' => $label,
            'class' => $class,
        ];
    }
}

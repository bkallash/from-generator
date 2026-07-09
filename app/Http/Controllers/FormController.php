<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Models\FormDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

class FormController extends Controller
{
    // ── Builder (authenticated) ───────────────────────────────────────────

    public function create(): View
    {
        return view('form-builder');
    }

    public function edit(int $formId): View
    {
        return view('form-builder', ['formId' => $formId]);
    }

    public function activate(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->user_id === $request->user()->id, 403);

        $form->is_active = true;
        $form->save();

        return redirect()->route('dashboard', ['view' => 'forms', 'page' => $request->query('page', 1)]);
    }

    public function deactivate(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->user_id === $request->user()->id, 403);

        $form->is_active = false;
        $form->save();

        return redirect()->route('dashboard', ['view' => 'forms', 'page' => $request->query('page', 1)]);
    }

    public function destroy(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->user_id === $request->user()->id, 403);

        $form->delete();

        return redirect()->route('dashboard', ['view' => 'forms', 'page' => $request->query('page', 1)]);
    }

    // ── Public form ───────────────────────────────────────────────────────

    public function show(Request $request, string $slug): View
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Restore progress from DB draft if session is empty and cookie exists
        $progress = session()->get("form_progress.{$form->id}", []);
        if (empty($progress)) {
            $token = $request->cookie("form_draft_{$form->id}");
            if ($token) {
                $draft = FormDraft::where('form_id', $form->id)->where('token', $token)->first();
                if ($draft) {
                    $progress = $draft->data ?? [];
                    session()->put("form_progress.{$form->id}", $progress);
                }
            }
        }

        // Get all pages and fields
        $pages = $form->getPages();

        // Determine visible page indexes
        $visiblePages = $this->getVisiblePageIndexes($form, $progress);
        
        $pageNumber = (int) $request->query('page', 1);
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        $currentPageIdx = $pageNumber - 1;

        // If current page is not visible, default to the first visible page
        if (!empty($visiblePages) && !in_array($currentPageIdx, $visiblePages)) {
            $currentPageIdx = $visiblePages[0];
        }

        return view('forms.show', compact('form', 'pages', 'currentPageIdx', 'visiblePages', 'progress'));
    }

    private function processUploadedFiles(Form $form, array $validated): array
    {
        foreach ($validated as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $fields = $form->getFields();
                $field = collect($fields)->firstWhere('id', $key);
                $fieldType = $field['type'] ?? 'file';

                if ($fieldType === 'image') {
                    $service = new ImageUploadService();
                    $paths = $service->store(
                        $value,
                        (int) $form->user_id,
                        (int) $form->id,
                        $key
                    );
                    $validated[$key] = $paths->originalPath;
                } else {
                    $uuid = (string) Str::uuid();
                    $filename = $uuid . '.' . $value->getClientOriginalExtension();
                    $path = $value->storeAs(
                        "{$form->user_id}/{$form->id}/{$key}",
                        $filename,
                        'submissions'
                    );
                    $validated[$key] = $path;
                }
            }
        }

        return $validated;
    }

    public function savePage(Request $request, string $slug, int $page): RedirectResponse|JsonResponse
    {
        $form = $request->attributes->get('publicForm');
        if (! $form instanceof Form) {
            $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();
        }

        $pageIndex = $page - 1;
        $validated = (array) $request->attributes->get('validatedPublicSubmission', []);
        $validated = $this->processUploadedFiles($form, $validated);

        // Save progress to session
        $progress = session()->get("form_progress.{$form->id}", []);
        $progress[$pageIndex] = $validated;
        session()->put("form_progress.{$form->id}", $progress);

        // Get/Create draft token cookie
        $token = $request->cookie("form_draft_{$form->id}");
        if (! $token) {
            $token = (string) Str::uuid();
            Cookie::queue(Cookie::forever("form_draft_{$form->id}", $token));
        }

        // Save progress to database draft
        FormDraft::updateOrCreate(
            ['form_id' => $form->id, 'token' => $token],
            [
                'data' => $progress,
                'current_page' => $pageIndex,
                'ip_address' => $request->ip(),
            ]
        );

        // Determine next visible page index
        $visiblePages = $this->getVisiblePageIndexes($form, $progress);
        
        $nextPageIdx = null;
        foreach ($visiblePages as $vIdx) {
            if ($vIdx > $pageIndex) {
                $nextPageIdx = $vIdx;
                break;
            }
        }

        if ($nextPageIdx !== null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'next_page' => $nextPageIdx + 1,
                ]);
            }
            return redirect()->route('forms.show', ['slug' => $form->slug, 'page' => $nextPageIdx + 1]);
        }

        // No more visible pages! Execute final form submission.
        return $this->executeFormSubmission($form, $progress, $request);
    }

    public function submit(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $form = $request->attributes->get('publicForm');

        if (! $form instanceof Form) {
            $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();
        }

        $validated = (array) $request->attributes->get('validatedPublicSubmission', []);
        $validated = $this->processUploadedFiles($form, $validated);

        // Read previous progress
        $progress = session()->get("form_progress.{$form->id}", []);
        
        // Merge last page data
        $pageCount = $form->getPageCount();
        if ($pageCount > 1) {
            $progress[$pageCount - 1] = $validated;
        } else {
            $progress[0] = $validated;
        }

        return $this->executeFormSubmission($form, $progress, $request);
    }

    private function executeFormSubmission(Form $form, array $progress, Request $request): RedirectResponse|JsonResponse
    {
        // Flatten all progress fields
        $flatData = [];
        foreach ($progress as $pageData) {
            if (is_array($pageData)) {
                $flatData = array_merge($flatData, $pageData);
            }
        }

        $fields = $form->getFields();
        $inputTypes = ['text', 'email', 'number', 'phone', 'date', 'url', 'textarea', 'select', 'radio', 'checkbox', 'file', 'image'];

        $content = [];

        // Determine visible page indexes to filter out inactive pages/fields
        $visiblePages = $this->getVisiblePageIndexes($form, $progress);

        foreach ($fields as $field) {
            if (! in_array($field['type'], $inputTypes)) {
                continue;
            }

            // Find which page this field belongs to
            $fieldPageIndex = null;
            foreach ($form->getPages() as $pIdx => $p) {
                foreach ($p['fields'] ?? [] as $f) {
                    if ($f['id'] === $field['id']) {
                        $fieldPageIndex = $pIdx;
                        break 2;
                    }
                }
            }

            // Skip saving fields from inactive pages
            if ($fieldPageIndex !== null && ! in_array($fieldPageIndex, $visiblePages)) {
                continue;
            }

            // Skip fields whose conditional logic is not met
            if (isset($field['conditionalLogic']) && $field['conditionalLogic']) {
                $logic = $field['conditionalLogic'];
                $triggerFieldId = $logic['triggerFieldId'] ?? null;
                $triggerValue = $logic['triggerValue'] ?? null;
                $action = $logic['action'] ?? 'show';

                if ($triggerFieldId) {
                    $triggerVal = $flatData[$triggerFieldId] ?? null;
                    $conditionMet = false;
                    if (is_array($triggerVal)) {
                        $conditionMet = in_array($triggerValue, $triggerVal);
                    } else {
                        $conditionMet = (string) $triggerVal === (string) $triggerValue;
                    }

                    $shouldShow = ($action === 'show') ? $conditionMet : !$conditionMet;
                    if (! $shouldShow) {
                        continue;
                    }
                }
            }

            $id = $field['id'];

            if ($field['type'] === 'checkbox') {
                $content[$id] = (array) ($flatData[$id] ?? []);
            } else {
                $content[$id] = $flatData[$id] ?? null;
            }
        }

        Submission::create([
            'form_id'    => $form->id,
            'content'    => $content,
            'ip_address' => $request->ip(),
        ]);

        // Clean up draft and progress
        $token = $request->cookie("form_draft_{$form->id}");
        if ($token) {
            FormDraft::where('form_id', $form->id)->where('token', $token)->delete();
        }
        
        session()->forget("form_progress.{$form->id}");
        Cookie::queue(Cookie::forget("form_draft_{$form->id}"));

        $successMessage = $form->settings['success_message'] ?? 'Thank you! Your response has been recorded.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
            ]);
        }

        return redirect()
            ->route('forms.show', $form->slug)
            ->with('success', $successMessage);
    }

    private function getVisiblePageIndexes(Form $form, array $progressData): array
    {
        $visible = [];
        $pages = $form->getPages();

        $flatData = [];
        foreach ($progressData as $pageData) {
            if (is_array($pageData)) {
                $flatData = array_merge($flatData, $pageData);
            }
        }

        foreach ($pages as $i => $page) {
            if (! isset($page['conditionalLogic']) || !$page['conditionalLogic']) {
                $visible[] = $i;
                continue;
            }

            $logic = $page['conditionalLogic'];
            $triggerFieldId = $logic['triggerFieldId'] ?? null;
            $triggerValue = $logic['triggerValue'] ?? null;
            $action = $logic['action'] ?? 'show';

            if (! $triggerFieldId) {
                $visible[] = $i;
                continue;
            }

            $val = $flatData[$triggerFieldId] ?? null;
            
            $conditionMet = false;
            if (is_array($val)) {
                $conditionMet = in_array($triggerValue, $val);
            } else {
                $conditionMet = (string) $val === (string) $triggerValue;
            }

            $shouldShow = ($action === 'show') ? $conditionMet : !$conditionMet;

            if ($shouldShow) {
                $visible[] = $i;
            }
        }

        return $visible;
    }

    public function manifest(string $slug): JsonResponse
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json([
            'name'             => $form->title,
            'short_name'       => Str::limit($form->title, 12, ''),
            'description'      => $form->description ?? 'Fill out this form',
            'start_url'        => route('forms.show', $slug),
            'scope'            => '/f/' . $slug,
            'display'          => 'standalone',
            'theme_color'      => '#0a0a0a',
            'background_color' => '#ffffff',
            'icons'            => [
                [
                    'src'   => asset('favicon.svg'),
                    'sizes' => 'any',
                    'type'  => 'image/svg+xml',
                ],
            ],
        ]);
    }

    public function offlineSync(Request $request, string $slug): JsonResponse
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $payload = array_merge(
            $request->input('fields', []),
            $request->file('fields', [])
        );

        $fields = $form->getFields();
        $inputTypes = ['text', 'email', 'number', 'phone', 'date', 'url', 'textarea', 'select', 'radio', 'checkbox', 'file', 'image'];

        $rules = [];
        $attributes = [];

        // Determine visible page indexes based on payload to adjust required validation
        $visiblePages = [];
        $pages = $form->getPages();
        foreach ($pages as $i => $page) {
            if (! isset($page['conditionalLogic']) || !$page['conditionalLogic']) {
                $visiblePages[] = $i;
                continue;
            }

            $logic = $page['conditionalLogic'];
            $triggerFieldId = $logic['triggerFieldId'] ?? null;
            $triggerValue = $logic['triggerValue'] ?? null;
            $action = $logic['action'] ?? 'show';

            if (! $triggerFieldId) {
                $visiblePages[] = $i;
                continue;
            }

            $val = $payload[$triggerFieldId] ?? null;
            
            $conditionMet = false;
            if (is_array($val)) {
                $conditionMet = in_array($triggerValue, $val);
            } else {
                $conditionMet = (string) $val === (string) $triggerValue;
            }

            $shouldShow = ($action === 'show') ? $conditionMet : !$conditionMet;

            if ($shouldShow) {
                $visiblePages[] = $i;
            }
        }

        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');
            if (! in_array($type, $inputTypes, true)) {
                continue;
            }

            $id = (string) ($field['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);

            // Find which page this field belongs to
            $fieldPageIndex = null;
            foreach ($pages as $pIdx => $p) {
                foreach ($p['fields'] ?? [] as $f) {
                    if ($f['id'] === $field['id']) {
                        $fieldPageIndex = $pIdx;
                        break 2;
                    }
                }
            }

            // If the field belongs to an inactive page, downgrade required
            if ($fieldPageIndex !== null && ! in_array($fieldPageIndex, $visiblePages, true)) {
                $required = false;
            }

            // Field-level conditional logic check
            if ($required && isset($field['conditionalLogic']) && $field['conditionalLogic']) {
                $logic = $field['conditionalLogic'];
                $triggerFieldId = $logic['triggerFieldId'] ?? null;
                $triggerValue = $logic['triggerValue'] ?? null;
                $action = $logic['action'] ?? 'show';

                if ($triggerFieldId) {
                    $triggerVal = $payload[$triggerFieldId] ?? null;
                    
                    $conditionMet = false;
                    if (is_array($triggerVal)) {
                        $conditionMet = in_array($triggerValue, $triggerVal);
                    } else {
                        $conditionMet = (string) $triggerVal === (string) $triggerValue;
                    }

                    $shouldShow = ($action === 'show') ? $conditionMet : !$conditionMet;
                    if (! $shouldShow) {
                        $required = false;
                    }
                }
            }

            $label = (string) ($field['label'] ?? $id);
            $attributes[$id] = $label;

            if ($type === 'checkbox') {
                $rules[$id] = [$required ? 'required' : 'nullable', 'array', 'max:50'];
                $rules[$id . '.*'] = ['string', 'max:255'];
                continue;
            }

            if ($type === 'image') {
                $rules[$id] = [
                    $required ? 'required' : 'nullable',
                    'image',
                    'max:6144',
                ];
                continue;
            }

            if ($type === 'file') {
                $rules[$id] = [
                    $required ? 'required' : 'nullable',
                    'file',
                    'max:10240',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if (! $value instanceof \Illuminate\Http\UploadedFile) {
                            return;
                        }

                        $blockedMimeTypes = [
                            'text/html',
                            'application/xhtml+xml',
                            'image/svg+xml',
                        ];

                        $blockedExtensions = ['html', 'htm', 'xhtml', 'svg', 'svgz'];

                        $detectedMime = strtolower((string) $value->getMimeType());
                        $extension = strtolower((string) $value->getClientOriginalExtension());

                        if (in_array($detectedMime, $blockedMimeTypes, true) || in_array($extension, $blockedExtensions, true)) {
                            $fail('This file type is not allowed.');
                        }
                    },
                ];
                continue;
            }

            $fieldRules = [$required ? 'required' : 'nullable'];

            match ($type) {
                'email'  => $fieldRules[] = 'email',
                'number' => $fieldRules[] = 'numeric',
                'url'    => $fieldRules[] = 'url',
                'date'   => $fieldRules[] = 'date',
                'phone'  => $fieldRules[] = 'regex:/^[0-9+\-\s().]{7,25}$/',
                default  => null,
            };

            $fieldRules[] = 'string';
            $fieldRules[] = $type === 'phone' ? 'max:25' : 'max:65535';

            $rules[$id] = $fieldRules;
        }

        $validator = \Illuminate\Support\Facades\Validator::make($payload, $rules, [], $attributes);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated = $this->processUploadedFiles($form, $validated);

        Submission::create([
            'form_id'    => $form->id,
            'content'    => $validated,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}

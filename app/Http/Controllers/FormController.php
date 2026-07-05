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

    public function show(Request $request, string $slug): View|RedirectResponse
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

        // Determine visible page indexes
        $visiblePages = $this->getVisiblePageIndexes($form, $progress);
        
        $pageNumber = (int) $request->query('page', 1);
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }

        $currentPageIdx = $pageNumber - 1;

        // If current page is not visible, redirect to nearest visible page
        if (!empty($visiblePages) && !in_array($currentPageIdx, $visiblePages)) {
            $closestIdx = $visiblePages[0];
            foreach ($visiblePages as $vIdx) {
                if ($vIdx <= $currentPageIdx) {
                    $closestIdx = $vIdx;
                } else {
                    break;
                }
            }
            return redirect()->route('forms.show', ['slug' => $form->slug, 'page' => $closestIdx + 1]);
        }

        // Get fields of the active page
        $fields = $form->getPageFields($currentPageIdx);

        return view('forms.show', compact('form', 'fields', 'currentPageIdx', 'visiblePages', 'progress'));
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

    public function savePage(Request $request, string $slug, int $page): RedirectResponse
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
            return redirect()->route('forms.show', ['slug' => $form->slug, 'page' => $nextPageIdx + 1]);
        }

        // No more visible pages! Execute final form submission.
        return $this->executeFormSubmission($form, $progress, $request);
    }

    public function submit(Request $request, string $slug): RedirectResponse
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

    private function executeFormSubmission(Form $form, array $progress, Request $request): RedirectResponse
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
}

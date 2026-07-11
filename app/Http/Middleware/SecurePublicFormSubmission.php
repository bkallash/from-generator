<?php

namespace App\Http\Middleware;

use App\Models\Form;
use App\Models\FormDraft;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SecurePublicFormSubmission
{
    private const INPUT_TYPES = [
        'text',
        'email',
        'number',
        'phone',
        'date',
        'url',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'file',
        'image',
    ];

    public function handle(Request $request, Closure $next): Response|\Illuminate\Http\JsonResponse
    {
        $slug = (string) $request->route('slug', '');

        if ($slug === '') {
            return $this->denySubmission($request);
        }

        $form = Form::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $form) {
            return $this->denySubmission($request);
        }

        if (! $this->passesHoneypot($request)) {
            return $this->denySubmission($request);
        }

        // Read progress from DB draft via cookie token, header, or body (flat format).
        // Header/body cover the race where draft was just saved but Set-Cookie is not
        // yet available on the immediately following final-submit request.
        $draftData = [];
        $token = $this->resolveDraftToken($request, $form);
        if ($token) {
            $draft = FormDraft::where('form_id', $form->id)->where('token', $token)->first();
            if ($draft) {
                $draftData = $draft->data ?? [];
            }
        }

        // Final submit validates only the last visible page's fields
        $visiblePages = $form->getVisiblePageIndexes($draftData);
        $lastPageIdx = ! empty($visiblePages) ? end($visiblePages) : 0;
        $fieldsToValidate = $form->getPageFields($lastPageIdx);

        // Verify no unexpected inputs for the current page
        if ($this->hasUnexpectedInputs($request, $fieldsToValidate)) {
            return $this->denySubmission($request);
        }

        // Merge draft data with current request inputs for conditional logic evaluation
        $flatData = array_merge($draftData, $request->all());

        [$rules, $attributes] = $this->buildValidationRules($fieldsToValidate, $flatData);

        $validator = Validator::make($request->all(), $rules, [], $attributes);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed: ' . implode(' ', $validator->errors()->all()),
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $request->attributes->set('publicForm', $form);
        $request->attributes->set('validatedPublicSubmission', $validator->validated());
        $request->attributes->set('publicFormDraftToken', $token);

        return $next($request);
    }

    /**
     * Resolve draft token from cookie, header, or request body.
     */
    protected function resolveDraftToken(Request $request, Form $form): ?string
    {
        $candidates = [
            $request->cookie("form_draft_{$form->id}"),
            $request->header('X-Form-Draft-Token'),
            $request->input('_draft_token'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && strlen($candidate) <= 100) {
                return $candidate;
            }
        }

        return null;
    }

    protected function passesHoneypot(Request $request): bool
    {
        if ($request->filled('_hp_website')) {
            return false;
        }

        $encryptedTimestamp = (string) $request->input('_hp_time', '');

        if ($encryptedTimestamp === '') {
            return false;
        }

        $renderedAt = $this->decryptTimestamp($encryptedTimestamp);

        if ($renderedAt === null) {
            return false;
        }

        $elapsedSeconds = now()->timestamp - $renderedAt;

        // Treat too-fast submissions and stale forms as suspicious.
        return $elapsedSeconds >= 2 && $elapsedSeconds <= 7200;
    }

    protected function decryptTimestamp(string $encryptedTimestamp): ?int
    {
        try {
            $decrypted = Crypt::decryptString($encryptedTimestamp);

            if (is_numeric($decrypted)) {
                return (int) $decrypted;
            }
        } catch (Throwable) {
            // Fall back to legacy encrypted values.
        }

        try {
            $decrypted = Crypt::decrypt($encryptedTimestamp);

            if (is_numeric($decrypted)) {
                return (int) $decrypted;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $flatData
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function buildValidationRules(array $fields, array $flatData): array
    {
        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');

            if (! in_array($type, self::INPUT_TYPES, true)) {
                continue;
            }

            $id = (string) ($field['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $required = (bool) ($field['required'] ?? false);

            // Conditional Logic check: if condition is not met, downgrade required to nullable
            if (! $this->shouldDisplayByConditionalLogic($field['conditionalLogic'] ?? null, $flatData)) {
                $required = false; // Hidden field is not required
            }

            $label = (string) ($field['label'] ?? $id);
            $attributes[$id] = $label;

            if ($type === 'checkbox') {
                $rules[$id] = [$required ? 'required' : 'nullable', 'array', 'max:50'];
                $rules[$id . '.*'] = ['string', 'max:255'];

                $options = $this->extractOptions($field['options'] ?? []);
                if ($options !== []) {
                    $rules[$id . '.*'][] = Rule::in($options);
                }

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
                    function (string $attribute, mixed $value, Closure $fail): void {
                        if (! $value instanceof UploadedFile) {
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
                'email' => $fieldRules[] = 'email',
                'number' => $fieldRules[] = 'numeric',
                'url' => $fieldRules[] = 'url',
                'date' => $fieldRules[] = 'date',
                'phone' => $fieldRules[] = 'regex:/^[0-9+\-\s().]{7,25}$/',
                default => null,
            };

            $fieldRules[] = 'string';
            $fieldRules[] = $type === 'phone' ? 'max:25' : 'max:65535';

            if (in_array($type, ['select', 'radio'], true)) {
                $options = $this->extractOptions($field['options'] ?? []);

                if ($options !== []) {
                    $fieldRules[] = Rule::in($options);
                }
            }

            $rules[$id] = $fieldRules;
        }

        return [$rules, $attributes];
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    protected function hasUnexpectedInputs(Request $request, array $fields): bool
    {
        $allowedKeys = ['_token', '_hp_website', '_hp_time', '_draft_token'];

        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');
            $id = (string) ($field['id'] ?? '');

            if ($id !== '' && in_array($type, self::INPUT_TYPES, true)) {
                $allowedKeys[] = $id;
            }
        }

        $allowedMap = array_fill_keys($allowedKeys, true);

        foreach (array_keys($request->except(['_token'])) as $key) {
            if (! isset($allowedMap[$key])) {
                return true;
            }
        }

        foreach (array_keys($request->allFiles()) as $key) {
            if (! isset($allowedMap[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $rawOptions
     * @return array<int, string>
     */
    protected function extractOptions(mixed $rawOptions): array
    {
        if (is_array($rawOptions)) {
            $options = array_map(static fn(mixed $option): string => trim((string) $option), $rawOptions);
        } else {
            $options = array_map('trim', explode("\n", (string) $rawOptions));
        }

        return array_values(array_filter($options, static fn(string $option): bool => $option !== ''));
    }

    protected function denySubmission(?Request $request = null): Response|\Illuminate\Http\JsonResponse
    {
        if ($request && $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to validate your submission. Please refresh and try again.',
            ], 400);
        }

        return back()
            ->withInput()
            ->withErrors([
                'form' => 'Unable to validate your submission. Please refresh and try again.',
            ]);
    }

    /**
     * @param mixed $logic
     * @param array<string, mixed> $flatData
     */
    private function shouldDisplayByConditionalLogic(mixed $logic, array $flatData): bool
    {
        if (! is_array($logic) || $logic === []) {
            return true;
        }

        $triggerFieldId = $logic['triggerFieldId'] ?? null;
        $triggerValue = $logic['triggerValue'] ?? null;
        $action = $logic['action'] ?? 'show';

        if (! $triggerFieldId) {
            return true;
        }

        $value = $flatData[$triggerFieldId] ?? null;
        $conditionMet = is_array($value)
            ? in_array($triggerValue, $value)
            : (string) $value === (string) $triggerValue;

        return $action === 'show' ? $conditionMet : ! $conditionMet;
    }

}

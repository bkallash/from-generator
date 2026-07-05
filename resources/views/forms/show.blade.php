@extends('layouts.app')

@section('title', $form->title . ' - Form Generator')

@section('content')
    <main class="min-h-screen py-16 px-4">
        <div class="max-w-2xl mx-auto">

            {{-- Form header --}}
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-semibold tracking-tight mb-3 text-neutral-900 dark:text-neutral-100">{{ $form->title }}</h1>
                @if ($form->description && $form->getPageCount() <= 1)
                    <p class="text-neutral-600 dark:text-neutral-400 text-base leading-relaxed max-w-xl mx-auto">{{ $form->description }}</p>
                @endif
            </div>

            {{-- Success state --}}
            @if (session('success'))
                <div
                    class="rounded border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-950 px-6 py-5 text-green-800 dark:text-green-300 text-sm">
                    {{ session('success') }}
                </div>
            @else
                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="mb-6 rounded border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950 px-6 py-4">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Please fix the following errors:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li class="text-sm text-red-700 dark:text-red-400">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Step Progress Indicator (Multi-page forms only) --}}
                @if ($form->getPageCount() > 1)
                    <div class="mb-10">
                        <div class="flex items-center justify-between relative">
                            {{-- Line bar --}}
                            <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-neutral-200 dark:bg-neutral-800 -z-10"></div>
                            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-neutral-900 dark:bg-neutral-100 transition-all duration-300 -z-10"
                                style="width: {{ count($visiblePages) > 1 ? (array_search($currentPageIdx, $visiblePages) / (count($visiblePages) - 1)) * 100 : 0 }}%"></div>
                            
                            @foreach ($visiblePages as $stepIndex => $pageIdx)
                                @php
                                    $pageObj = $form->getPages()[$pageIdx];
                                    $isCompleted = array_search($currentPageIdx, $visiblePages) > $stepIndex;
                                    $isActive = $currentPageIdx === $pageIdx;
                                @endphp
                                <div class="flex flex-col items-center gap-1.5 bg-neutral-50 dark:bg-neutral-900 px-2 select-none">
                                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-semibold transition-all duration-300
                                        {{ $isActive
                                            ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900 shadow-sm scale-110'
                                            : ($isCompleted
                                                ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                                : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 text-neutral-400 dark:text-neutral-500') }}">
                                        @if ($isCompleted)
                                            <svg class="w-3.5 h-3.5 text-white dark:text-neutral-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            {{ $stepIndex + 1 }}
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300
                                        {{ $isActive ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-400 dark:text-neutral-500' }}">
                                        {{ Str::limit($pageObj['title'] ?: 'Page ' . ($pageIdx + 1), 12) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php
                    $isLastPage = true;
                    if ($form->getPageCount() > 1) {
                        $lastVisibleIdx = end($visiblePages);
                        $isLastPage = $currentPageIdx === $lastVisibleIdx;
                    }
                @endphp

                <form method="POST" 
                    action="{{ route('forms.save-page', ['slug' => $form->slug, 'page' => $currentPageIdx + 1]) }}" 
                    enctype="multipart/form-data"
                    class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 shadow-sm rounded-lg space-y-6" id="public-form">
                    @csrf
                    <input type="text" name="_hp_website" tabindex="-1" autocomplete="off" class="hidden"
                        aria-hidden="true">
                    <input type="hidden" name="_hp_time"
                        value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">

                    @php
                        $activePage = $form->getPages()[$currentPageIdx] ?? null;
                    @endphp
                    @if ($form->getPageCount() > 1 && $activePage)
                        <div class="border-b border-neutral-200 dark:border-neutral-800 pb-5 mb-6">
                            <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">{{ $activePage['title'] ?: 'Page ' . ($currentPageIdx + 1) }}</h2>
                            @if ($activePage['description'])
                                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400 font-light leading-relaxed">{{ $activePage['description'] }}</p>
                            @endif
                        </div>
                    @endif

                    @foreach ($fields as $field)
                        @php 
                            $fieldId = 'field_' . $field['id']; 
                            
                            $hasLogic = isset($field['conditionalLogic']) && $field['conditionalLogic'];
                            $logicAction = $hasLogic ? $field['conditionalLogic']['action'] ?? 'show' : '';
                            $logicTriggerField = $hasLogic ? $field['conditionalLogic']['triggerFieldId'] ?? '' : '';
                            $logicTriggerValue = $hasLogic ? $field['conditionalLogic']['triggerValue'] ?? '' : '';

                            // Calculate initial visibility server-side
                            $initiallyHidden = false;
                            if ($hasLogic && $logicTriggerField) {
                                $flatProgress = collect($progress)->collapse()->all();
                                $triggerVal = $flatProgress[$logicTriggerField] ?? null;
                                
                                $conditionMet = false;
                                if (is_array($triggerVal)) {
                                    $conditionMet = in_array($logicTriggerValue, $triggerVal);
                                } else {
                                    $conditionMet = (string) $triggerVal === (string) $logicTriggerValue;
                                }

                                $shouldShow = ($logicAction === 'show') ? $conditionMet : !$conditionMet;
                                $initiallyHidden = !$shouldShow;
                            }
                        @endphp

                        <div class="field-wrapper transition-all duration-300"
                            data-field-id="{{ $field['id'] }}"
                            @if ($initiallyHidden) style="display: none;" @endif
                            @if ($hasLogic)
                                data-logic-action="{{ $logicAction }}"
                                data-logic-trigger-field="{{ $logicTriggerField }}"
                                data-logic-trigger-value="{{ $logicTriggerValue }}"
                            @endif>

                            {{-- Layout elements --}}
                            @if ($field['type'] === 'heading')
                                <h2 class="text-xl font-semibold pt-2 text-neutral-900 dark:text-neutral-100">{{ $field['label'] }}</h2>
                            @elseif ($field['type'] === 'paragraph')
                                <p class="text-neutral-600 dark:text-neutral-400 text-sm leading-relaxed">{{ $field['label'] }}</p>
                            @elseif ($field['type'] === 'divider')
                                <hr class="border-neutral-200 dark:border-neutral-800">

                            {{-- Input fields --}}
                            @elseif (in_array($field['type'], ['text', 'email', 'number', 'date', 'url']))
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="{{ $field['type'] }}" id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        @if ($field['required'] && !$initiallyHidden) required @endif 
                                        value="{{ old($field['id'], collect($progress)->collapse()->get($field['id'], '')) }}"
                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 placeholder-neutral-400 dark:placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'phone')
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="tel" id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        @if ($field['required'] && !$initiallyHidden) required @endif 
                                        value="{{ old($field['id'], collect($progress)->collapse()->get($field['id'], '')) }}"
                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 placeholder-neutral-400 dark:placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'textarea')
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <textarea id="{{ $fieldId }}" name="{{ $field['id'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                                        @if ($field['required'] && !$initiallyHidden) required @endif rows="4"
                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 placeholder-neutral-400 dark:placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent resize-y transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">{{ old($field['id'], collect($progress)->collapse()->get($field['id'], '')) }}</textarea>
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'select')
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <select id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">
                                        <option value="">{{ $field['placeholder'] ?? 'Select an option' }}</option>
                                        @foreach (array_filter(array_map('trim', is_array($field['options'] ?? '') ? $field['options'] ?? [] : explode("\n", $field['options'] ?? ''))) as $option)
                                            <option value="{{ $option }}" @selected(old($field['id'], collect($progress)->collapse()->get($field['id'], '')) === $option)>
                                                {{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'radio')
                                <div>
                                    <fieldset>
                                        <legend class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-300">
                                            {{ $field['label'] }}
                                            @if ($field['required'])
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </legend>
                                        <div class="space-y-2">
                                            @foreach (array_filter(array_map('trim', is_array($field['options'] ?? '') ? $field['options'] ?? [] : explode("\n", $field['options'] ?? ''))) as $option)
                                                <label class="flex items-center gap-2.5 cursor-pointer text-neutral-600 dark:text-neutral-400">
                                                    <input type="radio" name="{{ $field['id'] }}"
                                                        value="{{ $option }}"
                                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                                        @checked(old($field['id'], collect($progress)->collapse()->get($field['id'], '')) === $option)
                                                        class="w-4 h-4 rounded-full border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-white focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:ring-offset-2 dark:focus:ring-offset-neutral-950 transition">
                                                    <span class="text-sm font-light">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'checkbox')
                                <div>
                                    <fieldset>
                                        <legend class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-300">
                                            {{ $field['label'] }}
                                            @if ($field['required'])
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </legend>
                                        <div class="space-y-2">
                                            @php
                                                $savedVal = collect($progress)->collapse()->get($field['id'], []);
                                                if (!is_array($savedVal)) {
                                                    $savedVal = [$savedVal];
                                                }
                                            @endphp
                                            @foreach (array_filter(array_map('trim', is_array($field['options'] ?? '') ? $field['options'] ?? [] : explode("\n", $field['options'] ?? ''))) as $option)
                                                <label class="flex items-center gap-2.5 cursor-pointer text-neutral-600 dark:text-neutral-400">
                                                    <input type="checkbox" name="{{ $field['id'] }}[]"
                                                        value="{{ $option }}" @checked(in_array($option, (array) old($field['id'], $savedVal)))
                                                        class="w-4 h-4 rounded border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-white focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:ring-offset-2 dark:focus:ring-offset-neutral-950 transition">
                                                    <span class="text-sm font-light">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'image')
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="file" id="{{ $fieldId }}" name="{{ $field['id'] }}" accept="image/*"
                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                        class="w-full text-sm text-neutral-600 dark:text-neutral-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border file:border-neutral-200 dark:file:border-neutral-700 file:text-sm file:font-medium file:bg-white dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-50 dark:hover:file:bg-neutral-800 file:transition @error($field['id']) border-red-400 @enderror">
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @elseif ($field['type'] === 'file')
                                <div>
                                    <label for="{{ $fieldId }}" class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="file" id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                        class="w-full text-sm text-neutral-600 dark:text-neutral-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border file:border-neutral-200 dark:file:border-neutral-700 file:text-sm file:font-medium file:bg-white dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-50 dark:hover:file:bg-neutral-800 file:transition @error($field['id']) border-red-400 @enderror">
                                    @error($field['id'])
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Navigation and Actions --}}
                    <div class="pt-6 flex items-center justify-between border-t border-neutral-200 dark:border-neutral-800">
                        @if ($form->getPageCount() > 1 && $currentPageIdx !== $visiblePages[0])
                            @php
                                $prevVisibleIdx = $visiblePages[array_search($currentPageIdx, $visiblePages) - 1];
                            @endphp
                            <a href="{{ route('forms.show', ['slug' => $form->slug, 'page' => $prevVisibleIdx + 1]) }}"
                                class="px-5 py-2.5 border border-neutral-300 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 text-sm font-medium transition duration-150 rounded">
                                &larr; Back
                            </a>
                        @else
                            <div></div>
                        @endif

                        <button type="submit" id="submit-button"
                            class="px-8 py-3 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 text-sm font-medium hover:opacity-90 transition-opacity rounded">
                            {{ $isLastPage ? 'Submit' : 'Next &rarr;' }}
                        </button>
                    </div>
                </form>

            @endif

        </div>
    </main>

    <script>
        // Inject previous form progress and pages array for frontend conditional check
        window.formProgress = @json(collect($progress)->collapse()->all());
        window.formPages = @json($form->getPages());
        window.currentPageIdx = {{ $currentPageIdx }};

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('public-form');
            if (!form) return;

            const wrappers = document.querySelectorAll('.field-wrapper[data-logic-trigger-field]');

            function getFieldValue(fieldId) {
                const inputs = form.querySelectorAll(`[name="${fieldId}"], [name="${fieldId}[]"]`);
                if (inputs.length === 0) {
                    return window.formProgress[fieldId] ?? null;
                }

                const first = inputs[0];
                if (first.type === 'radio') {
                    const checked = form.querySelector(`[name="${fieldId}"]:checked`);
                    return checked ? checked.value : null;
                } else if (first.type === 'checkbox') {
                    return Array.from(form.querySelectorAll(`[name="${fieldId}[]"]:checked`)).map(el => el.value);
                } else {
                    return first.value;
                }
            }

            function isNextPageVisible() {
                const totalPages = window.formPages.length;
                if (totalPages <= 1) return false;

                // Gather current form values to evaluate logic
                const flatData = {};
                Object.assign(flatData, window.formProgress);
                
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const id = input.name.replace('[]', '');
                    if (input.type === 'radio') {
                        if (input.checked) flatData[id] = input.value;
                    } else if (input.type === 'checkbox') {
                        if (!flatData[id]) flatData[id] = [];
                        if (input.checked) flatData[id].push(input.value);
                    } else {
                        flatData[id] = input.value;
                    }
                });

                // Find next visible page index after currentPageIdx
                for (let i = window.currentPageIdx + 1; i < totalPages; i++) {
                    const page = window.formPages[i];
                    if (!page.conditionalLogic) {
                        return true;
                    }

                    const logic = page.conditionalLogic;
                    const triggerFieldId = logic.triggerFieldId;
                    const triggerValue = logic.triggerValue;
                    const action = logic.action;

                    const val = flatData[triggerFieldId];
                    let conditionMet = false;
                    if (Array.isArray(val)) {
                        conditionMet = val.includes(triggerValue);
                    } else {
                        conditionMet = String(val) === String(triggerValue);
                    }

                    const shouldShow = (action === 'show') ? conditionMet : !conditionMet;
                    if (shouldShow) {
                        return true;
                    }
                }

                return false;
            }

            function updateSubmitButtonLabel() {
                const btn = document.getElementById('submit-button');
                if (!btn) return;
                
                if (isNextPageVisible()) {
                    btn.innerHTML = 'Next &rarr;';
                } else {
                    btn.innerHTML = 'Submit';
                }
            }

            function evaluateLogic() {
                wrappers.forEach(wrapper => {
                    const action = wrapper.dataset.logicAction;
                    const triggerField = wrapper.dataset.logicTriggerField;
                    const triggerValue = wrapper.dataset.logicTriggerValue;

                    const val = getFieldValue(triggerField);

                    let conditionMet = false;
                    if (Array.isArray(val)) {
                        conditionMet = val.includes(triggerValue);
                    } else {
                        conditionMet = String(val) === String(triggerValue);
                    }

                    const shouldShow = (action === 'show') ? conditionMet : !conditionMet;

                    if (shouldShow) {
                        wrapper.style.display = 'block';
                        wrapper.querySelectorAll('input, select, textarea').forEach(el => {
                            el.disabled = false;
                            if (el.hasAttribute('data-was-required')) {
                                el.required = true;
                            }
                        });
                    } else {
                        wrapper.style.display = 'none';
                        wrapper.querySelectorAll('input, select, textarea').forEach(el => {
                            // Remember if it was required before we disable it
                            if (el.required) {
                                el.setAttribute('data-was-required', 'true');
                                el.required = false;
                            }
                            el.disabled = true;
                        });
                    }
                });

                // Update next page logic in real time
                updateSubmitButtonLabel();
            }

            // Bind change listeners
            form.addEventListener('change', evaluateLogic);
            form.addEventListener('input', evaluateLogic);

            // Initial run
            evaluateLogic();
        });
    </script>
@endsection

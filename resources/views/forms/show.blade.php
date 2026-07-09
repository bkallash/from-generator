@extends('layouts.app')

@section('title', $form->title . ' - Form Generator')

@push('head')
    <!-- Per-form PWA manifest -->
    <link rel="manifest" href="{{ route('forms.manifest', $form->slug) }}">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $form->title }}">
@endpush

@section('content')
    <!-- Offline status banner -->
    <div id="offline-banner"
        class="hidden fixed top-0 inset-x-0 z-50 bg-amber-500 text-white text-xs font-semibold text-center py-2 px-4 shadow-md select-none transition-all duration-300">
        📶 You're offline — your submissions are saved to this device and will sync automatically when you reconnect.
    </div>

    <!-- Connection Restored banner -->
    <div id="restored-banner"
        class="hidden fixed top-0 inset-x-0 z-50 bg-green-600 text-white text-xs font-semibold text-center py-2 px-4 shadow-md select-none transition-all duration-300">
        🟢 Connection restored! Syncing saved submissions...
    </div>

    <!-- Sync Toast Notification -->
    <div id="sync-toast"
        class="hidden fixed bottom-6 right-6 z-9999 px-5 py-3 shadow-xl rounded text-sm text-white transition-all duration-300 transform translate-y-20 opacity-0 bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900">
        Sync progress message...
    </div>

    <div class="min-h-screen flex justify-center w-full">
        <!-- LEFT PANEL: Queue Panel (hidden by default, toggled via floating badge) -->
        <aside id="queue-panel"
            class="hidden fixed lg:sticky inset-y-0 lg:top-0 left-0 z-40 lg:z-auto w-72 lg:w-80 h-screen lg:shrink-0 border-r border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 flex-col overflow-hidden select-none transition-all duration-300">
            <!-- Panel Header -->
            <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        Offline Submissions
                    </h3>
                    <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-1">
                        Queued for sync (<span id="queue-count">0</span>)
                    </p>
                </div>
            </div>

            <!-- List of queued submissions -->
            <div id="queue-list" class="flex-1 overflow-y-auto p-4 space-y-3">
                <!-- Submission boxes are rendered by form-pwa.js -->
            </div>

            <!-- Sync status footer -->
            <div id="queue-footer"
                class="px-5 py-4 border-t border-neutral-200 dark:border-neutral-800 text-[11px] text-neutral-400 dark:text-neutral-500 flex items-center gap-2">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span id="sync-status">Offline — saved locally</span>
            </div>
        </aside>

        <!-- Main Form Area -->
        <main class="flex-1 py-16 px-4">
            <div class="max-w-2xl mx-auto">

                {{-- Form header --}}
                <div class="mb-10 text-center">
                    <h1 class="text-3xl font-semibold tracking-tight mb-3 text-neutral-900 dark:text-neutral-100">
                        {{ $form->title }}</h1>
                    @if ($form->description && $form->getPageCount() <= 1)
                        <p class="text-neutral-600 dark:text-neutral-400 text-base leading-relaxed max-w-xl mx-auto">
                            {{ $form->description }}</p>
                    @endif
                    <button id="pwa-install-btn"
                        class="hidden mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold uppercase tracking-wider border border-neutral-300 dark:border-neutral-700 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition duration-200">
                        <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Form
                    </button>
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
                        <div
                            class="mb-6 rounded border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950 px-6 py-4">
                            <p class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Please fix the following
                                errors:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li class="text-sm text-red-700 dark:text-red-400">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Step Progress Indicator (Multi-page forms only) --}}
                    @if ($form->getPageCount() > 1)
                        <div class="mb-10" id="step-progress-wrapper">
                            <div class="flex items-center justify-between relative" id="steps-container">
                                {{-- Line bar --}}
                                <div
                                    class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-neutral-200 dark:bg-neutral-800 -z-10">
                                </div>
                                <div id="progress-line" class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-neutral-900 dark:bg-neutral-100 transition-all duration-300 -z-10"
                                    style="width: {{ count($visiblePages) > 1 ? (array_search($currentPageIdx, $visiblePages) / (count($visiblePages) - 1)) * 100 : 0 }}%">
                                </div>

                                @foreach ($pages as $pageIdx => $pageObj)
                                    @php
                                        $stepIndex = array_search($pageIdx, $visiblePages);
                                        $isCompleted = $stepIndex !== false && array_search($currentPageIdx, $visiblePages) > $stepIndex;
                                        $isActive = $currentPageIdx === $pageIdx;
                                        $isVisible = $stepIndex !== false;
                                    @endphp
                                    <div class="step-dot-wrapper flex flex-col items-center gap-1.5 bg-neutral-50 dark:bg-neutral-900 px-2 select-none"
                                        data-step-index="{{ $pageIdx }}"
                                        style="display: {{ $isVisible ? 'flex' : 'none' }}">
                                        <div class="step-dot w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-semibold transition-all duration-300
                                        {{ $isActive
                                            ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900 shadow-sm scale-110'
                                            : ($isCompleted
                                                ? 'border-neutral-900 bg-neutral-900 text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                                : 'border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 text-neutral-400 dark:text-neutral-500') }}">
                                            @if ($isCompleted)
                                                <svg class="w-3.5 h-3.5 text-white dark:text-neutral-900" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            @else
                                                {{ ($stepIndex !== false ? $stepIndex + 1 : $pageIdx + 1) }}
                                            @endif
                                        </div>
                                        <span class="step-title text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300
                                        {{ $isActive ? 'text-neutral-900 dark:text-neutral-100' : 'text-neutral-400 dark:text-neutral-500' }}">
                                            {{ Str::limit($pageObj['title'] ?: 'Page ' . ($pageIdx + 1), 12) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST"
                        action="{{ route('forms.submit', ['slug' => $form->slug]) }}"
                        enctype="multipart/form-data"
                        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 shadow-sm rounded-lg space-y-6"
                        id="public-form">
                        @csrf
                        <input type="text" name="_hp_website" tabindex="-1" autocomplete="off" class="hidden"
                            aria-hidden="true">
                        <input type="hidden" name="_hp_time"
                            value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">

                        @foreach ($pages as $pageIdx => $page)
                            <div class="form-page-container transition-all duration-300" data-page-index="{{ $pageIdx }}" style="display: {{ $pageIdx === $currentPageIdx ? 'block' : 'none' }}">
                                @if ($form->getPageCount() > 1)
                                    <div class="border-b border-neutral-200 dark:border-neutral-800 pb-5 mb-6">
                                        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                                            {{ $page['title'] ?: 'Page ' . ($pageIdx + 1) }}</h2>
                                        @if (isset($page['description']) && $page['description'])
                                            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400 font-light leading-relaxed">
                                                {{ $page['description'] }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="space-y-6">
                                    @foreach ($page['fields'] ?? [] as $field)
                                        @php
                                            $fieldId = 'field_' . $field['id'];

                                            $hasLogic = isset($field['conditionalLogic']) && $field['conditionalLogic'];
                                            $logicAction = $hasLogic ? $field['conditionalLogic']['action'] ?? 'show' : '';
                                            $logicTriggerField = $hasLogic
                                                ? $field['conditionalLogic']['triggerFieldId'] ?? ''
                                                : '';
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

                                                $shouldShow = $logicAction === 'show' ? $conditionMet : !$conditionMet;
                                                $initiallyHidden = !$shouldShow;
                                            }
                                        @endphp

                                        <div class="field-wrapper transition-all duration-300" data-field-id="{{ $field['id'] }}"
                                            @if ($initiallyHidden) style="display: none;" @endif
                                            @if ($hasLogic) data-logic-action="{{ $logicAction }}"
                                            data-logic-trigger-field="{{ $logicTriggerField }}"
                                            data-logic-trigger-value="{{ $logicTriggerValue }}" @endif>

                                            {{-- Layout elements --}}
                                            @if ($field['type'] === 'heading')
                                                <h2 class="text-xl font-semibold pt-2 text-neutral-900 dark:text-neutral-100">
                                                    {{ $field['label'] }}</h2>
                                            @elseif ($field['type'] === 'paragraph')
                                                <p class="text-neutral-600 dark:text-neutral-400 text-sm leading-relaxed">
                                                    {{ $field['label'] }}</p>
                                            @elseif ($field['type'] === 'divider')
                                                <hr class="border-neutral-200 dark:border-neutral-800">

                                                {{-- Input fields --}}
                                            @elseif (in_array($field['type'], ['text', 'email', 'number', 'date', 'url']))
                                                <div>
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                                        {{ $field['label'] }}
                                                        @if ($field['required'])
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="{{ $field['type'] }}" id="{{ $fieldId }}"
                                                        name="{{ $field['id'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                                        value="{{ old($field['id'], collect($progress)->collapse()->get($field['id'], '')) }}"
                                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 placeholder-neutral-400 dark:placeholder-neutral-600 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">
                                                    @error($field['id'])
                                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @elseif ($field['type'] === 'phone')
                                                <div>
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
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
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
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
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                                        {{ $field['label'] }}
                                                        @if ($field['required'])
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </label>
                                                    <select id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                                        @if ($field['required'] && !$initiallyHidden) required @endif
                                                        class="w-full px-3.5 py-2.5 text-sm rounded border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition @error($field['id']) border-red-400 dark:border-red-600 @enderror">
                                                        <option value="">{{ $field['placeholder'] ?? 'Select an option' }}
                                                        </option>
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
                                                        <legend
                                                            class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-300">
                                                            {{ $field['label'] }}
                                                            @if ($field['required'])
                                                                <span class="text-red-500">*</span>
                                                            @endif
                                                        </legend>
                                                        <div class="space-y-2">
                                                            @foreach (array_filter(array_map('trim', is_array($field['options'] ?? '') ? $field['options'] ?? [] : explode("\n", $field['options'] ?? ''))) as $option)
                                                                <label
                                                                    class="flex items-center gap-2.5 cursor-pointer text-neutral-600 dark:text-neutral-400">
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
                                                        <legend
                                                            class="block text-sm font-medium mb-2 text-neutral-700 dark:text-neutral-300">
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
                                                                <label
                                                                    class="flex items-center gap-2.5 cursor-pointer text-neutral-600 dark:text-neutral-400">
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
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
                                                        {{ $field['label'] }}
                                                        @if ($field['required'])
                                                            <span class="text-red-500">*</span>
                                                        @endif
                                                    </label>
                                                    <input type="file" id="{{ $fieldId }}" name="{{ $field['id'] }}"
                                                        accept="image/*" @if ($field['required'] && !$initiallyHidden) required @endif
                                                        class="w-full text-sm text-neutral-600 dark:text-neutral-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border file:border-neutral-200 dark:file:border-neutral-700 file:text-sm file:font-medium file:bg-white dark:file:bg-neutral-900 file:text-neutral-700 dark:file:text-neutral-300 hover:file:bg-neutral-50 dark:hover:file:bg-neutral-800 file:transition @error($field['id']) border-red-400 @enderror">
                                                    @error($field['id'])
                                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @elseif ($field['type'] === 'file')
                                                <div>
                                                    <label for="{{ $fieldId }}"
                                                        class="block text-sm font-medium mb-1.5 text-neutral-700 dark:text-neutral-300">
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
                                </div>
                            </div>
                        @endforeach

                        {{-- Navigation and Actions --}}
                        <div class="pt-6 flex items-center justify-between border-t border-neutral-200 dark:border-neutral-800">
                            <button type="button" id="prev-button"
                                class="hidden px-5 py-2.5 border border-neutral-300 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 text-sm font-medium transition duration-150 rounded">
                                &larr; Back
                            </button>
                            <div id="nav-spacer" class="flex-1"></div>
                            <button type="submit" id="submit-button"
                                class="px-8 py-3 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 text-sm font-medium hover:opacity-90 transition-opacity rounded">
                                {{ $form->getPageCount() > 1 ? 'Next →' : 'Submit' }}
                            </button>
                        </div>
                    </form>

                @endif

            </div>
        </main>
    </div>

    <!-- Floating badge toggle (bottom-left) -->
    <button id="queue-badge"
        class="hidden fixed bottom-6 left-6 z-50 w-14 h-14 rounded-full bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 shadow-2xl flex items-center justify-center text-lg font-bold border border-neutral-200 dark:border-neutral-800 focus:outline-none cursor-pointer"
        title="View Offline Queue">
        <span id="badge-count">0</span>
    </button>

    <script>
        // Inject previous form progress and pages array for frontend conditional check
        window.formProgress = @json(collect($progress)->collapse()->all());
        window.formPages = @json($form->getPages());
        window.currentPageIdx = {{ $currentPageIdx }};
    </script>
@endsection

@push('scripts')
    <script>
        window.formSlug = @json($form->slug);

        // Immediate capture of PWA installation prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.installPrompt = e;
            const installBtn = document.getElementById('pwa-install-btn');
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
            if (installBtn && !isStandalone) {
                installBtn.classList.remove('hidden');
            }
        });

        // Click handler for installing PWA bound immediately on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            const installBtn = document.getElementById('pwa-install-btn');
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
            if (isStandalone && installBtn) {
                installBtn.classList.add('hidden');
                installBtn.style.display = 'none';
            }
            if (installBtn) {
                installBtn.addEventListener('click', () => {
                    const promptEvent = window.installPrompt;
                    if (promptEvent) {
                        promptEvent.prompt();
                        promptEvent.userChoice.then((choiceResult) => {
                            if (choiceResult.outcome === 'accepted') {
                                console.log('User accepted the PWA install prompt');
                            }
                            window.installPrompt = null;
                            installBtn.classList.add('hidden');
                        });
                    } else {
                        console.warn('Install prompt event is not available.');
                    }
                });
            }
        });
    </script>
    @vite(['resources/js/form-pwa.js'])
@endpush

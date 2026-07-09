<div class="flex h-full -m-6" x-data="{ showUnsavedModal: false }">

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- UNSAVED CHANGES MODAL                                             --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="showUnsavedModal" x-cloak class="fixed inset-0 z-9999 flex items-center justify-center">
            <div x-show="showUnsavedModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/40 dark:bg-black/60"
                @click="showUnsavedModal = false"></div>
            <div x-show="showUnsavedModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md mx-4 bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Unsaved Changes</h3>
                <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                    You have unsaved changes. Would you like to save before leaving?
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="showUnsavedModal = false"
                        class="px-4 py-2 text-sm font-medium text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors duration-150">
                        Cancel
                    </button>
                    <a href="{{ route('dashboard', ['view' => 'forms']) }}"
                        @click="window.formBuilderAllowUnload = true"
                        class="px-4 py-2 text-sm font-medium border border-neutral-300 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors duration-150">
                        Leave Without Saving
                    </a>
                    <button
                        @click="$wire.saveForm().then(() => { window.location.href = '{{ route('dashboard', ['view' => 'forms']) }}' })"
                        class="px-4 py-2 text-sm font-medium bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:opacity-90 transition-opacity duration-150">
                        Save & Leave
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- LEFT PANEL — Field Palette                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <aside
        class="w-80 shrink-0 bg-white dark:bg-neutral-950 border-r border-neutral-200 dark:border-neutral-800 flex flex-col overflow-hidden transition-colors duration-300">

        {{-- Panel Switcher --}}
        <div class="grid grid-cols-2 border-b border-neutral-200 dark:border-neutral-800 text-center text-xs font-semibold uppercase tracking-wider select-none shrink-0 bg-neutral-50/50 dark:bg-neutral-950/20">
            <button wire:click="$set('showAiChat', true)" 
                class="py-3.5 border-b-2 transition-all duration-150 {{ $showAiChat ? 'border-neutral-900 dark:border-neutral-100 text-neutral-900 dark:text-neutral-100 font-bold' : 'border-transparent text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300' }}">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                        <path d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                    </svg>
                    AI Builder
                </span>
            </button>
            <button wire:click="$set('showAiChat', false)" 
                class="py-3.5 border-b-2 transition-all duration-150 {{ !$showAiChat ? 'border-neutral-900 dark:border-neutral-100 text-neutral-900 dark:text-neutral-100 font-bold' : 'border-transparent text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300' }}">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    Fields
                </span>
            </button>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden min-h-0">
            @if ($showAiChat)
                <livewire:form-builder-chat 
                    :formId="$formId" 
                    :initialMessages="$aiChatHistory" 
                    :schema="['pages' => $pages]" 
                    wire:key="ai-form-chat-{{ $formId ?? 'new' }}"
                />
            @else
                {{-- Panel Header --}}
                <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        Field Types
                    </h3>
                    <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-1">Drag or click to add</p>
                </div>

                {{-- Field Type List --}}
                <div class="flex-1 overflow-y-auto px-3 py-3 space-y-4" id="field-palette">

                    {{-- Basic Fields --}}
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500 px-2 mb-2">
                            Input Fields
                        </p>
                        <div class="palette-group space-y-1">
                            @foreach ($this->fieldTypes as $ft)
                                @if ($ft['group'] === 'basic')
                                    <div class="palette-item cursor-grab active:cursor-grabbing flex items-center gap-3 px-3 py-2.5 rounded-md border border-transparent hover:border-neutral-200 dark:hover:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-900/60 transition-all duration-150 group select-none"
                                        data-type="{{ $ft['type'] }}" wire:click="addField('{{ $ft['type'] }}')">
                                        <div
                                            class="w-8 h-8 rounded flex items-center justify-center bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            @include('livewire.partials.field-icon', ['icon' => $ft['icon']])
                                        </div>
                                        <span
                                            class="text-sm font-medium text-neutral-600 dark:text-neutral-300 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            {{ $ft['label'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover:opacity-50 transition-opacity duration-150 text-neutral-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Choice Fields --}}
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500 px-2 mb-2">
                            Choice Fields
                        </p>
                        <div class="palette-group space-y-1">
                            @foreach ($this->fieldTypes as $ft)
                                @if ($ft['group'] === 'choice')
                                    <div class="palette-item cursor-grab active:cursor-grabbing flex items-center gap-3 px-3 py-2.5 rounded-md border border-transparent hover:border-neutral-200 dark:hover:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-900/60 transition-all duration-150 group select-none"
                                        data-type="{{ $ft['type'] }}" wire:click="addField('{{ $ft['type'] }}')">
                                        <div
                                            class="w-8 h-8 rounded flex items-center justify-center bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            @include('livewire.partials.field-icon', ['icon' => $ft['icon']])
                                        </div>
                                        <span
                                            class="text-sm font-medium text-neutral-600 dark:text-neutral-300 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            {{ $ft['label'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover:opacity-50 transition-opacity duration-150 text-neutral-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Advanced Fields --}}
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500 px-2 mb-2">
                            Advanced
                        </p>
                        <div class="palette-group space-y-1">
                            @foreach ($this->fieldTypes as $ft)
                                @if ($ft['group'] === 'advanced')
                                    <div class="palette-item cursor-grab active:cursor-grabbing flex items-center gap-3 px-3 py-2.5 rounded-md border border-transparent hover:border-neutral-200 dark:hover:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-900/60 transition-all duration-150 group select-none"
                                        data-type="{{ $ft['type'] }}" wire:click="addField('{{ $ft['type'] }}')">
                                        <div
                                            class="w-8 h-8 rounded flex items-center justify-center bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            @include('livewire.partials.field-icon', ['icon' => $ft['icon']])
                                        </div>
                                        <span
                                            class="text-sm font-medium text-neutral-600 dark:text-neutral-300 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            {{ $ft['label'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover:opacity-50 transition-opacity duration-150 text-neutral-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Layout Elements --}}
                    <div>
                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500 px-2 mb-2">
                            Layout
                        </p>
                        <div class="palette-group space-y-1">
                            @foreach ($this->fieldTypes as $ft)
                                @if ($ft['group'] === 'layout')
                                    <div class="palette-item cursor-grab active:cursor-grabbing flex items-center gap-3 px-3 py-2.5 rounded-md border border-transparent hover:border-neutral-200 dark:hover:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-900/60 transition-all duration-150 group select-none"
                                        data-type="{{ $ft['type'] }}" wire:click="addField('{{ $ft['type'] }}')">
                                        <div
                                            class="w-8 h-8 rounded flex items-center justify-center bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            @include('livewire.partials.field-icon', ['icon' => $ft['icon']])
                                        </div>
                                        <span
                                            class="text-sm font-medium text-neutral-600 dark:text-neutral-300 group-hover:text-neutral-900 dark:group-hover:text-neutral-100 transition-colors duration-150">
                                            {{ $ft['label'] }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover:opacity-50 transition-opacity duration-150 text-neutral-400"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                </div>
            @endif
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CENTER — Form Canvas                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-hidden bg-neutral-100 dark:bg-neutral-900/50"
        x-on:click.self="$wire.deselectField()">

        {{-- Top Bar --}}
        <div
            class="shrink-0 flex items-center justify-between px-6 py-3 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800">
            <div class="flex items-center gap-3">
                <button
                    @click="@this.isDirty ? showUnsavedModal = true : window.location.href = '{{ route('dashboard', ['view' => 'forms']) }}'"
                    class="p-2 -ml-2 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div class="h-5 w-px bg-neutral-200 dark:bg-neutral-700"></div>
                {{-- Form-level Title Input --}}
                <input type="text" wire:model.blur="title"
                    class="text-sm font-semibold text-neutral-950 dark:text-neutral-50 bg-transparent border-0 border-b border-transparent focus:border-neutral-300 dark:focus:border-neutral-700 focus:ring-0 px-1 py-0.5 max-w-xs transition-colors duration-150"
                    placeholder="Form Title">
            </div>
            <div class="flex items-center gap-2">
                {{-- Field count badge --}}
                <span class="text-xs text-neutral-400 dark:text-neutral-500 tabular-nums">
                    {{ count($fields) }} {{ Str::plural('field', count($fields)) }}
                </span>
                <div class="h-5 w-px bg-neutral-200 dark:bg-neutral-700"></div>
                {{-- Save Button --}}
                <button wire:click="saveForm" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-200 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveForm">Save Form</span>
                    <span wire:loading wire:target="saveForm">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                            </path>
                        </svg>
                    </span>
                </button>
            </div>
        </div>

        {{-- Page Tabs Bar --}}
        <div class="shrink-0 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800 px-6 py-2.5 flex items-center gap-2 overflow-x-auto select-none">
            @foreach ($pages as $index => $page)
                <div class="group relative flex items-center gap-1.5 px-3 py-1.5 border text-xs font-medium cursor-pointer transition-all duration-150
                    {{ $currentPageIndex === $index
                        ? 'bg-neutral-900 border-neutral-900 text-white dark:bg-neutral-100 dark:border-neutral-100 dark:text-neutral-900 shadow-sm'
                        : 'bg-neutral-50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800 text-neutral-600 dark:text-neutral-400 hover:border-neutral-300 dark:hover:border-neutral-700' }}"
                    wire:click="switchPage({{ $index }})">
                    
                    @if (isset($page['conditionalLogic']) && $page['conditionalLogic'])
                        {{-- Page conditional indicator (branch icon) --}}
                        <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="18" r="3" />
                            <circle cx="6" cy="6" r="3" />
                            <circle cx="6" cy="18" r="3" />
                            <path d="M18 15V9a4 4 0 0 0-4-4H9" />
                            <line x1="6" y1="9" x2="6" y2="15" />
                        </svg>
                    @endif
                    
                    <span>{{ $page['title'] ?: 'Page ' . ($index + 1) }}</span>
                    
                    @if (count($pages) > 1)
                        <button wire:click.stop="removePage({{ $index }})"
                            class="opacity-0 group-hover:opacity-100 ml-1 hover:text-red-500 dark:hover:text-red-400 transition-opacity duration-100 p-0.5"
                            title="Remove Page">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
            
            {{-- Add page tab --}}
            <button wire:click="addPage"
                class="flex items-center justify-center w-8 h-8 border border-dashed border-neutral-300 dark:border-neutral-700 hover:border-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/60 rounded-sm text-neutral-400 hover:text-neutral-600 transition-all duration-150"
                title="Add Page">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>

        {{-- Save notification --}}
        @if ($showSaveNotification)
            <div x-data="{ show: true }" x-init="setTimeout(() => {
                show = false;
                $wire.set('showSaveNotification', false)
            }, 3000)" x-show="show"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mx-6 mt-3 px-4 py-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-sm">
                Form saved successfully.
            </div>
        @endif

        {{-- Canvas Scroll Area --}}
        <div class="flex-1 overflow-y-auto p-6" x-on:click.self="$wire.deselectField()">
            <div class="max-w-2xl mx-auto">

                {{-- Page Header (Editable Title & Description) --}}
                <div
                    class="mb-6 bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300"
                    wire:key="canvas-page-header-{{ $currentPageIndex }}">
                    <input type="text" wire:model.blur="pageTitle" wire:key="canvas-page-title-input-{{ $currentPageIndex }}"
                        class="w-full text-2xl font-light tracking-tight bg-transparent border-0 border-b-2 border-transparent focus:border-neutral-300 dark:focus:border-neutral-600 focus:ring-0 px-0 pb-2 placeholder-neutral-300 dark:placeholder-neutral-600 transition-colors duration-200"
                        placeholder="Page Title">
                    <textarea wire:model.blur="pageDescription" rows="2" wire:key="canvas-page-desc-input-{{ $currentPageIndex }}"
                        class="w-full mt-3 text-sm font-light bg-transparent border-0 border-b-2 border-transparent focus:border-neutral-300 dark:focus:border-neutral-600 focus:ring-0 px-0 pb-2 placeholder-neutral-300 dark:placeholder-neutral-600 resize-none transition-colors duration-200 text-neutral-600 dark:text-neutral-400"
                        placeholder="Add a description for this page (optional)"></textarea>
                </div>

                {{-- Fields Drop Zone --}}
                <div id="form-canvas" class="space-y-3 min-h-50 relative">

                    @forelse ($fields as $index => $field)
                        <div class="canvas-field group relative" data-field-id="{{ $field['id'] }}"
                            wire:key="field-{{ $field['id'] }}">

                            {{-- Field Card --}}
                            <div class="relative bg-white dark:bg-neutral-950 border transition-all duration-200 cursor-pointer
                                {{ $activeFieldIndex === $index
                                    ? 'border-neutral-900 dark:border-neutral-100 shadow-[4px_4px_0_0_rgba(0,0,0,0.1)] dark:shadow-[4px_4px_0_0_rgba(255,255,255,0.05)]'
                                    : 'border-neutral-200 dark:border-neutral-800 hover:border-neutral-400 dark:hover:border-neutral-600' }}"
                                wire:click="selectField({{ $index }})">

                                {{-- Drag Handle --}}
                                <div
                                    class="drag-handle absolute left-0 top-0 bottom-0 w-8 flex items-center justify-center cursor-grab active:cursor-grabbing opacity-0 group-hover:opacity-100 transition-opacity duration-150 bg-neutral-50 dark:bg-neutral-900/50 border-r border-neutral-200 dark:border-neutral-800">
                                    <svg class="w-4 h-4 text-neutral-400" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="9" cy="6" r="1.5" />
                                        <circle cx="15" cy="6" r="1.5" />
                                        <circle cx="9" cy="12" r="1.5" />
                                        <circle cx="15" cy="12" r="1.5" />
                                        <circle cx="9" cy="18" r="1.5" />
                                        <circle cx="15" cy="18" r="1.5" />
                                    </svg>
                                </div>

                                {{-- Field Content Preview --}}
                                <div class="pl-8 pr-20 py-5 px-6">
                                    @include('livewire.partials.field-preview', ['field' => $field])

                                    @if (isset($field['conditionalLogic']) && $field['conditionalLogic'])
                                        <div class="mt-2.5 flex items-center gap-1.5 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="18" cy="18" r="3" />
                                                <circle cx="6" cy="6" r="3" />
                                                <circle cx="6" cy="18" r="3" />
                                                <path d="M18 15V9a4 4 0 0 0-4-4H9" />
                                                <line x1="6" y1="9" x2="6" y2="15" />
                                            </svg>
                                            <span>Conditional Logic Configured</span>
                                        </div>
                                    @endif

                                    @if (!empty($field['analyze_sentiment']))
                                        <div class="mt-2.5 flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                            <svg class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                                <path d="M8 10h.01" />
                                                <path d="M12 10h.01" />
                                                <path d="M16 10h.01" />
                                            </svg>
                                            <span>AI Sentiment Analysis Enabled</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Action Buttons --}}
                                <div
                                    class="absolute right-3 top-3 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    <button wire:click.stop="duplicateField({{ $index }})"
                                        class="p-1.5 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors duration-150"
                                        title="Duplicate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="removeField({{ $index }})"
                                        class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-950/30 text-neutral-400 hover:text-red-500 transition-colors duration-150"
                                        title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Active indicator --}}
                                @if ($activeFieldIndex === $index)
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-neutral-900 dark:bg-neutral-100">
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        {{-- Empty State --}}
                        <div class="empty-canvas-state flex flex-col items-center justify-center py-20 border-2 border-dashed border-neutral-300 dark:border-neutral-700 rounded-sm bg-white/50 dark:bg-neutral-950/30"
                            id="empty-canvas-drop">
                            <div
                                class="w-16 h-16 mb-4 flex items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <svg class="w-8 h-8 text-neutral-400 dark:text-neutral-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <p class="text-neutral-500 dark:text-neutral-400 font-medium mb-1">Drop fields here</p>
                            <p class="text-sm text-neutral-400 dark:text-neutral-500">
                                Drag from the left panel or click to add fields
                            </p>
                        </div>
                    @endforelse

                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- RIGHT PANEL — Properties (Field or Page settings)                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <aside
        class="w-80 shrink-0 bg-white dark:bg-neutral-950 border-l border-neutral-200 dark:border-neutral-800 flex flex-col overflow-hidden transition-all duration-300">

        @if ($activeFieldIndex !== null && isset($fields[$activeFieldIndex]))
            {{-- FIELD PROPERTIES --}}
            <div class="flex flex-col h-full" wire:key="field-properties-panel-{{ $fields[$activeFieldIndex]['id'] }}">
                <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        Field Properties
                    </h3>
                    <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-1 capitalize">
                        {{ $fields[$activeFieldIndex]['type'] ?? '' }} Field
                    </p>
                </div>
                <button wire:click="deselectField"
                    class="p-1.5 rounded hover:bg-neutral-100 dark:hover:bg-neutral-800 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                {{-- Label --}}
                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">
                        Label
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="editLabel"
                        class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 dark:focus:ring-neutral-100/10 focus:border-neutral-400 dark:focus:border-neutral-500 transition-all duration-150">
                </div>

                {{-- Placeholder (not for layout types) --}}
                @if (!in_array($fields[$activeFieldIndex]['type'], ['heading', 'paragraph', 'divider', 'radio', 'checkbox']))
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">
                            Placeholder
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="editPlaceholder"
                            class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 dark:focus:ring-neutral-100/10 focus:border-neutral-400 dark:focus:border-neutral-500 transition-all duration-150">
                    </div>
                @endif

                {{-- Options (for select, radio, checkbox) --}}
                @if (in_array($fields[$activeFieldIndex]['type'], ['select', 'radio', 'checkbox']))
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">
                            Options <span class="font-normal normal-case tracking-normal">(one per line)</span>
                        </label>
                        <textarea wire:model.live.debounce.500ms="editOptions" rows="5"
                            class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 dark:focus:ring-neutral-100/10 focus:border-neutral-400 dark:focus:border-neutral-500 transition-all duration-150 resize-none font-mono"
                            placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                @endif

                {{-- Required toggle (not for layout types) --}}
                @if (!in_array($fields[$activeFieldIndex]['type'], ['heading', 'paragraph', 'divider']))
                    <div
                        class="flex items-center justify-between py-3 border-t border-neutral-200 dark:border-neutral-800">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            Required
                        </label>
                        <button wire:click="$toggle('editRequired')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none
                                {{ $editRequired ? 'bg-neutral-900 dark:bg-neutral-100' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-neutral-900 transition-transform duration-200 shadow-sm
                                {{ $editRequired ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                @endif

                {{-- Analyze Sentiment toggle (only for text and textarea) --}}
                @if (in_array($fields[$activeFieldIndex]['type'], ['text', 'textarea']))
                    <div
                        class="flex items-center justify-between py-3 border-t border-neutral-200 dark:border-neutral-800">
                        <label
                            class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            Analyze Sentiment (AI)
                        </label>
                        <button wire:click="$toggle('editAnalyzeSentiment')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none
                                {{ $editAnalyzeSentiment ? 'bg-neutral-900 dark:bg-neutral-100' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-neutral-900 transition-transform duration-200 shadow-sm
                                {{ $editAnalyzeSentiment ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                @endif

                {{-- Field Conditional Logic --}}
                @if (!in_array($fields[$activeFieldIndex]['type'], ['heading', 'paragraph', 'divider']))
                    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                Show Conditionally
                            </label>
                            <button wire:click="$toggle('editConditionalEnabled')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none
                                    {{ $editConditionalEnabled ? 'bg-neutral-900 dark:bg-neutral-100' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-neutral-900 transition-transform duration-200 shadow-sm
                                    {{ $editConditionalEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        @if ($editConditionalEnabled)
                            <div class="space-y-4 pt-2">
                                {{-- Action --}}
                                <div>
                                    <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Action</label>
                                    <select wire:model.live="editConditionalAction"
                                        class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                        <option value="show">Show field when...</option>
                                        <option value="hide">Hide field when...</option>
                                    </select>
                                </div>

                                {{-- Trigger Field --}}
                                <div>
                                    <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Trigger Field</label>
                                    <select wire:model.live="editConditionalTriggerField"
                                        class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                        <option value="">Select a trigger field...</option>
                                        @foreach ($this->conditionalTriggerFields as $tf)
                                            <option value="{{ $tf['id'] }}">{{ $tf['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Trigger Value --}}
                                @if ($editConditionalTriggerField && count($this->conditionalTriggerOptions) > 0)
                                    <div>
                                        <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Trigger Value</label>
                                        <select wire:model.live="editConditionalTriggerValue"
                                            class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                            <option value="">Select option...</option>
                                            @foreach ($this->conditionalTriggerOptions as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Danger Zone --}}
                <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
                    <button wire:click="removeField({{ $activeFieldIndex }})"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-red-500 hover:text-white hover:bg-red-500 border border-red-200 dark:border-red-900/50 hover:border-red-500 rounded-sm transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Field
                    </button>
                </div>
            </div>
        @else
            {{-- PAGE PROPERTIES --}}
            <div class="flex flex-col h-full" wire:key="page-properties-panel-{{ $currentPageIndex }}">
                <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400">
                        Page Settings
                    </h3>
                    <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-1 capitalize">
                        {{ $pages[$currentPageIndex]['title'] ?? 'Page ' . ($currentPageIndex + 1) }}
                    </p>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                {{-- Page Title --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">
                        Page Title
                    </label>
                    <input type="text" wire:model.live.debounce.300ms="pageTitle"
                        class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 dark:focus:ring-neutral-100/10 focus:border-neutral-400 dark:focus:border-neutral-500 transition-all duration-150">
                </div>

                {{-- Page Description --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-1.5">
                        Page Description
                    </label>
                    <textarea wire:model.live.debounce.300ms="pageDescription" rows="3"
                        class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/10 dark:focus:ring-neutral-100/10 focus:border-neutral-400 dark:focus:border-neutral-500 transition-all duration-150 resize-none"></textarea>
                </div>

                {{-- Page Conditional Logic --}}
                @if ($currentPageIndex > 0)
                    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                Show Page Conditionally
                            </label>
                            <button wire:click="$toggle('editPageConditionalEnabled')"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none
                                    {{ $editPageConditionalEnabled ? 'bg-neutral-900 dark:bg-neutral-100' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-neutral-900 transition-transform duration-200 shadow-sm
                                    {{ $editPageConditionalEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        @if ($editPageConditionalEnabled)
                            <div class="space-y-4 pt-2">
                                {{-- Action --}}
                                <div>
                                    <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Action</label>
                                    <select wire:model.live="editPageConditionalAction"
                                        class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                        <option value="show">Show page when...</option>
                                        <option value="hide">Hide page when...</option>
                                    </select>
                                </div>

                                {{-- Trigger Field --}}
                                <div>
                                    <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Trigger Field</label>
                                    <select wire:model.live="editPageConditionalTriggerField"
                                        class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                        <option value="">Select a trigger field...</option>
                                        @foreach ($this->conditionalTriggerFields as $tf)
                                            <option value="{{ $tf['id'] }}">{{ $tf['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Trigger Value --}}
                                @if ($editPageConditionalTriggerField && count($this->pageConditionalTriggerOptions) > 0)
                                    <div>
                                        <label class="block text-[11px] text-neutral-400 dark:text-neutral-500 mb-1">Trigger Value</label>
                                        <select wire:model.live="editPageConditionalTriggerValue"
                                            class="w-full px-2.5 py-1.5 text-xs bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm focus:outline-none focus:border-neutral-400">
                                            <option value="">Select option...</option>
                                            @foreach ($this->pageConditionalTriggerOptions as $opt)
                                                <option value="{{ $opt }}">{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                @if (count($pages) > 1)
                    <div class="pt-4 border-t border-neutral-200 dark:border-neutral-800">
                        <button wire:click="removePage({{ $currentPageIndex }})"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-red-500 hover:text-white hover:bg-red-500 border border-red-200 dark:border-red-900/50 hover:border-red-500 rounded-sm transition-all duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Page
                        </button>
                    </div>
                @endif
            </div>
            </div>
        @endif
    </aside>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SORTABLE.JS — Drag & Drop Engine                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
        <style>
            .sortable-ghost {
                opacity: 0.2;
            }

            .sortable-drag {
                opacity: 1 !important;
                box-shadow: 0 12px 40px -8px rgba(0, 0, 0, 0.15);
            }
        </style>
    @endassets

    @script
        <script>
            const root = $wire.$el;
            let canvasSortable = null;
            let paletteInstances = [];

            function initPalette() {
                paletteInstances.forEach(s => {
                    try {
                        s.destroy();
                    } catch (e) {}
                });
                paletteInstances = [];

                root.querySelectorAll('.palette-group').forEach(group => {
                    const s = new Sortable(group, {
                        group: {
                            name: 'formfields',
                            pull: 'clone',
                            put: false
                        },
                        sort: false,
                        draggable: '.palette-item',
                        ghostClass: 'opacity-30',
                        animation: 150,
                    });
                    paletteInstances.push(s);
                });
            }

            function initCanvas() {
                const canvas = root.querySelector('#form-canvas');
                if (!canvas) return;

                if (canvasSortable) {
                    try {
                        canvasSortable.destroy();
                    } catch (e) {}
                    canvasSortable = null;
                }

                canvasSortable = new Sortable(canvas, {
                    group: {
                        name: 'formfields',
                        pull: false,
                        put: true
                    },
                    draggable: '.canvas-field',
                    handle: '.drag-handle',
                    filter: '.empty-canvas-state',
                    preventOnFilter: false,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    animation: 200,

                    onEnd(evt) {
                        // Reorder existing fields
                        if (evt.from === canvas && evt.to === canvas && !evt.item.classList.contains('palette-item')) {
                            const ids = [...canvas.querySelectorAll('.canvas-field')]
                                .map(el => el.dataset.fieldId).filter(Boolean);
                            if (ids.length) $wire.reorderFields(ids);
                        }
                    },

                    onAdd(evt) {
                        const type = evt.item.dataset?.type;
                        const pos = evt.newIndex;
                        evt.item.remove();
                        if (type) $wire.fieldDropped(type, pos);
                    }
                });
            }

            // Boot
            initPalette();
            initCanvas();

            // Re-init after Livewire DOM updates
            Livewire.hook('morph.updated', ({
                el
            }) => {
                if (el === root || root.contains(el)) {
                    queueMicrotask(() => {
                        initCanvas();
                        initPalette();
                    });
                }
            });

            // Warn on browser back / tab close when dirty
            window.formBuilderAllowUnload = false;
            const beforeUnloadHandler = (e) => {
                if ($wire.isDirty && !window.formBuilderAllowUnload) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', beforeUnloadHandler);

            // Clean up when component is destroyed
            document.addEventListener('livewire:navigating', () => {
                window.removeEventListener('beforeunload', beforeUnloadHandler);
            }, {
                once: true
            });
        </script>
    @endscript
</div>

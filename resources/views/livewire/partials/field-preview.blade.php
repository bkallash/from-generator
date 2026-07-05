@props(['field'])

@php $type = $field['type']; @endphp

<div>
    @switch ($type)
        @case('heading')
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ $field['label'] }}</h3>
        @break

        @case('paragraph')
            <p class="text-sm text-neutral-500 dark:text-neutral-400 font-light leading-relaxed">{{ $field['label'] }}</p>
        @break

        @case('divider')
            <div class="py-2">
                <hr class="border-neutral-200 dark:border-neutral-800">
            </div>
        @break

        @case('textarea')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="w-full h-20 px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm text-neutral-400 dark:text-neutral-500">
                    {{ $field['placeholder'] ?? '' }}
                </div>
            </div>
        @break

        @case('select')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="w-full flex items-center justify-between px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm text-neutral-400 dark:text-neutral-500">
                    <span>{{ $field['placeholder'] ?? 'Choose...' }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
                @if (!empty($field['options']))
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach (array_slice($field['options'], 0, 4) as $opt)
                            <span
                                class="text-[11px] px-2 py-0.5 bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 rounded-sm">{{ $opt }}</span>
                        @endforeach
                        @if (count($field['options']) > 4)
                            <span
                                class="text-[11px] px-2 py-0.5 text-neutral-400 dark:text-neutral-500">+{{ count($field['options']) - 4 }}
                                more</span>
                        @endif
                    </div>
                @endif
            </div>
        @break

        @case('radio')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div class="space-y-2">
                    @foreach ($field['options'] ?? ['Option 1', 'Option 2'] as $opt)
                        <label class="flex items-center gap-2.5 text-sm text-neutral-600 dark:text-neutral-400">
                            <div
                                class="w-4 h-4 rounded-full border-2 border-neutral-300 dark:border-neutral-600 flex items-center justify-center">
                                @if ($loop->first)
                                    <div class="w-2 h-2 rounded-full bg-neutral-400 dark:bg-neutral-500"></div>
                                @endif
                            </div>
                            {{ $opt }}
                        </label>
                    @endforeach
                </div>
            </div>
        @break

        @case('checkbox')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div class="space-y-2">
                    @foreach ($field['options'] ?? ['Option 1', 'Option 2'] as $opt)
                        <label class="flex items-center gap-2.5 text-sm text-neutral-600 dark:text-neutral-400">
                            <div
                                class="w-4 h-4 rounded-sm border-2 border-neutral-300 dark:border-neutral-600 flex items-center justify-center">
                                @if ($loop->first)
                                    <svg class="w-3 h-3 text-neutral-400 dark:text-neutral-500" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </div>
                            {{ $opt }}
                        </label>
                    @endforeach
                </div>
            </div>
        @break

        @case('file')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="flex items-center justify-center w-full h-20 border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-sm bg-neutral-50 dark:bg-neutral-900/50">
                    <div class="flex flex-col items-center gap-1">
                        <svg class="w-6 h-6 text-neutral-400 dark:text-neutral-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span
                            class="text-xs text-neutral-400 dark:text-neutral-500">{{ $field['placeholder'] ?? 'Choose file...' }}</span>
                    </div>
                </div>
            </div>
        @break

        @case('image')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="flex items-center justify-center w-full h-20 border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-sm bg-neutral-50 dark:bg-neutral-900/50">
                    <div class="flex flex-col items-center gap-1">
                        <svg class="w-6 h-6 text-neutral-400 dark:text-neutral-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span
                            class="text-xs text-neutral-400 dark:text-neutral-500">{{ $field['placeholder'] ?? 'Choose image...' }}</span>
                    </div>
                </div>
            </div>
        @break

        @case('date')
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="w-full flex items-center justify-between px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm text-neutral-400 dark:text-neutral-500">
                    <span>{{ $field['placeholder'] ?? 'Select a date' }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        @break

        @default
            {{-- text, email, number, phone, url --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)
                        <span class="text-red-400 ml-0.5">*</span>
                    @endif
                </label>
                <div
                    class="w-full px-3 py-2 text-sm bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-sm text-neutral-400 dark:text-neutral-500">
                    {{ $field['placeholder'] ?? '' }}
                </div>
            </div>
    @endswitch
</div>

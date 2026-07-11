{{-- Filter bar: form selector + date range --}}
<div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6">
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <h3 class="text-xl font-light tracking-tight">
            Analytics <strong class="font-semibold">Overview</strong>
        </h3>
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative">
                <select wire:model.live="selectedFormId"
                    class="appearance-none h-9 w-44 pl-3 pr-8 text-sm border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent">
                    @forelse ($forms as $form)
                        <option value="{{ $form->id }}">{{ $form->title }}</option>
                    @empty
                        <option value="">No Forms Available</option>
                    @endforelse
                </select>
                <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-neutral-400"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <div class="flex border border-neutral-200 dark:border-neutral-700">
                @foreach (['7' => '7d', '30' => '30d', '90' => '90d'] as $val => $label)
                    <button wire:click="$set('range', '{{ $val }}')" type="button"
                        class="px-3 py-1.5 text-xs font-medium {{ $range === $val ? 'bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

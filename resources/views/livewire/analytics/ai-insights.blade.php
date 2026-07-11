{{-- AI-generated form insights card --}}
@if ($selectedFormId)
    <div
        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-violet-500 via-purple-500 to-pink-500">
        </div>

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                    AI-Powered Insights
                    @if ($selectedForm && $selectedForm->ai_insights_updated_at)
                        <span class="ml-2 normal-case font-normal text-[10px] text-neutral-400">
                            (Saved {{ $selectedForm->ai_insights_updated_at->diffForHumans() }})
                        </span>
                    @endif
                </p>
            </div>
            @if ($aiInsights)
                <button wire:click="generateInsights" wire:loading.attr="disabled" type="button"
                    class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1">
                    <span wire:loading.remove wire:target="generateInsights">Regenerate</span>
                    <span wire:loading wire:target="generateInsights">Regenerating...</span>
                </button>
            @endif
        </div>

        @if ($aiInsights)
            <div
                class="prose prose-neutral dark:prose-invert max-w-none text-sm text-neutral-700 dark:text-neutral-300 leading-relaxed font-light space-y-4">
                {!! \Illuminate\Support\Str::markdown($aiInsights) !!}
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-neutral-500 dark:text-neutral-400 font-light mb-4 text-sm">
                    Get a detailed, natural language summary of your form submissions and trends.
                </p>
                <button wire:click="generateInsights" wire:loading.attr="disabled" type="button"
                    class="relative inline-flex items-center justify-center px-6 py-2.5 text-sm font-medium transition-all duration-300 group overflow-hidden border border-neutral-900 dark:border-neutral-100 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100 focus:outline-none">
                    <span wire:loading.remove wire:target="generateInsights" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Generate Insights
                    </span>
                    <span wire:loading.flex wire:target="generateInsights" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Analyzing responses...
                    </span>
                </button>
            </div>
        @endif
    </div>
@endif

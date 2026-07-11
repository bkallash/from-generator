{{-- Sentiment analysis grid (one card per text field with AI data) --}}
@if ($hasSentimentData && !empty($sentimentStatsByField))
    <div class="mb-6">
        <p
            class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            AI Sentiment Analysis (Per Text Input)
        </p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ($sentimentStatsByField as $fieldId => $fieldData)
                @include('livewire.analytics.sentiment-card', [
                    'fieldId' => $fieldId,
                    'fieldData' => $fieldData,
                    'selectedFormId' => $selectedFormId,
                    'range' => $range,
                ])
            @endforeach
        </div>
    </div>
@endif

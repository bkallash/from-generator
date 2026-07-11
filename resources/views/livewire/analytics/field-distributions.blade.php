{{-- Choice-field answer distributions (select / radio / checkbox) --}}
@if (!empty($fieldStats))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @foreach ($fieldStats as $stat)
            <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4">
                    {{ $stat['label'] }}
                </p>
                <div class="space-y-3">
                    @foreach ($stat['tally'] as $option => $count)
                        @php $pct = $stat['total'] > 0 ? round($count / $stat['total'] * 100) : 0; @endphp
                        <div>
                            <div class="flex justify-between items-baseline text-sm mb-1">
                                <span class="text-neutral-700 dark:text-neutral-300 truncate max-w-[70%]">
                                    {{ $option }}
                                </span>
                                <span class="text-neutral-500 dark:text-neutral-400 shrink-0 pl-2 tabular-nums">
                                    {{ $count }} ({{ $pct }}%)
                                </span>
                            </div>
                            <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                                <div class="h-1.5 bg-neutral-900 dark:bg-neutral-100" style="width: {{ $pct }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@elseif ($selectedFormId)
    <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6">
        <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
            Field Breakdown
        </p>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 font-light">
            No choice fields (dropdown, radio, checkbox) in this form.
        </p>
    </div>
@endif

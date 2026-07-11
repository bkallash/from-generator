{{--
    Single sentiment field card (doughnut + breakdown bars).
    Expected: $fieldId, $fieldData, $selectedFormId, $range
--}}
@php
    $total = $fieldData['total'];

    $posCount = $fieldData['stats']['positive']['count'];
    $posPct = $total > 0 ? round(($posCount / $total) * 100) : 0;
    $posAvg = $fieldData['stats']['positive']['avg'];

    $neuCount = $fieldData['stats']['neutral']['count'];
    $neuPct = $total > 0 ? round(($neuCount / $total) * 100) : 0;
    $neuAvg = $fieldData['stats']['neutral']['avg'];

    $negCount = $fieldData['stats']['negative']['count'];
    $negPct = $total > 0 ? round(($negCount / $total) * 100) : 0;
    $negAvg = $fieldData['stats']['negative']['avg'];
@endphp

<div wire:key="sentiment-card-{{ $selectedFormId }}-{{ $fieldId }}-{{ $range }}"
    class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 flex flex-col justify-between"
    data-sentiment-field="{{ $fieldId }}">
    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-800 dark:text-neutral-200 mb-4 pb-2 border-b border-neutral-100 dark:border-neutral-900 truncate"
            title="{{ $fieldData['label'] }}">
            {{ $fieldData['label'] }}
        </h4>
        <div class="flex flex-col sm:flex-row items-center justify-around gap-6">
            {{-- Ignore only the canvas shell so Chart.js owns it; labels/stats still morph --}}
            <div wire:ignore data-chart-surface
                class="relative h-[140px] w-[140px] shrink-0 bg-white dark:bg-neutral-950">
                <canvas id="chart-{{ $fieldId }}" class="block w-full h-full bg-transparent"></canvas>
            </div>

            <div class="space-y-3 w-full max-w-xs">
                <div>
                    <div class="flex justify-between items-baseline text-xs mb-1">
                        <span class="text-neutral-600 dark:text-neutral-400 font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Positive
                        </span>
                        <span class="text-neutral-500 dark:text-neutral-400 tabular-nums font-semibold">
                            {{ $posCount }} <span class="text-[10px] font-normal">({{ $posPct }}%)</span>
                            @if ($posCount > 0)
                                <span
                                    class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg:
                                    {{ $posAvg }}%</span>
                            @endif
                        </span>
                    </div>
                    <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-1.5 bg-emerald-500" style="width: {{ $posPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-baseline text-xs mb-1">
                        <span class="text-neutral-600 dark:text-neutral-400 font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-neutral-400 rounded-full"></span> Neutral
                        </span>
                        <span class="text-neutral-500 dark:text-neutral-400 tabular-nums font-semibold">
                            {{ $neuCount }} <span class="text-[10px] font-normal">({{ $neuPct }}%)</span>
                            @if ($neuCount > 0)
                                <span
                                    class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg:
                                    {{ $neuAvg }}%</span>
                            @endif
                        </span>
                    </div>
                    <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-1.5 bg-neutral-400 dark:bg-neutral-500" style="width: {{ $neuPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-baseline text-xs mb-1">
                        <span class="text-neutral-600 dark:text-neutral-400 font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-rose-500 rounded-full"></span> Negative
                        </span>
                        <span class="text-neutral-500 dark:text-neutral-400 tabular-nums font-semibold">
                            {{ $negCount }} <span class="text-[10px] font-normal">({{ $negPct }}%)</span>
                            @if ($negCount > 0)
                                <span
                                    class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg:
                                    {{ $negAvg }}%</span>
                            @endif
                        </span>
                    </div>
                    <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-1.5 bg-rose-500" style="width: {{ $negPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($fieldData['top_emotions']))
            <div
                class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-900 flex flex-wrap items-center gap-1.5 text-[11px] text-neutral-500 dark:text-neutral-400">
                <span>Top Emotions:</span>
                @foreach ($fieldData['top_emotions'] as $emo)
                    <span
                        class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100 dark:border-purple-900/30 capitalize">
                        {{ $emo }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</div>

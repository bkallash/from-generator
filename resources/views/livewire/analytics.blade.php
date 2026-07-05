<div>
    {{-- ── Filter Bar ── --}}
    <div
        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6 transition-colors duration-300">
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <h3 class="text-xl font-light tracking-tight">
                Analytics <strong class="font-semibold">Overview</strong>
            </h3>
            <div class="flex flex-wrap gap-3 items-center">
                {{-- Form selector --}}
                <div class="relative">
                    <select wire:model.live="selectedFormId"
                        class="appearance-none h-9 w-44 pl-3 pr-8 text-sm border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition-colors duration-200">
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

                {{-- Range selector --}}
                <div class="flex border border-neutral-200 dark:border-neutral-700">
                    @foreach (['7' => '7d', '30' => '30d', '90' => '90d'] as $val => $label)
                        <button wire:click="$set('range', '{{ $val }}')"
                            class="px-3 py-1.5 text-xs font-medium transition-colors duration-200 {{ $range === $val ? 'bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">In
                Period</p>
            <p class="text-3xl font-light tracking-tight">{{ $totalInRange }}</p>
            <p class="text-xs text-neutral-400 mt-1">last {{ $range }} days</p>
        </div>

        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">This
                Week</p>
            <p class="text-3xl font-light tracking-tight">{{ $thisWeek }}</p>
            @if ($weekChange > 0)
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">↑ {{ $weekChange }}% vs last week</p>
            @elseif ($weekChange < 0)
                <p class="text-xs text-red-600 dark:text-red-400 mt-1">↓ {{ abs($weekChange) }}% vs last week</p>
            @else
                <p class="text-xs text-neutral-400 mt-1">same as last week</p>
            @endif
        </div>

        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">This
                Month</p>
            <p class="text-3xl font-light tracking-tight">{{ $thisMonth }}</p>
            <p class="text-xs text-neutral-400 mt-1">{{ now()->format('F') }}</p>
        </div>

        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">All Time
            </p>
            <p class="text-3xl font-light tracking-tight">{{ $totalAllTime }}</p>
            <p class="text-xs text-neutral-400 mt-1">
                {{ $activeForms }} active {{ Str::plural('form', $activeForms) }}
            </p>
        </div>
    </div>

    {{-- ── AI Insights Card ── --}}
    @if ($selectedFormId)
        <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6 transition-colors duration-300 relative overflow-hidden">
            {{-- Top glow effect --}}
            <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-violet-500 via-purple-500 to-pink-500"></div>
            
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
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
                    <button wire:click="generateInsights" wire:loading.attr="disabled" class="text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1">
                        <span wire:loading.remove wire:target="generateInsights">Regenerate</span>
                        <span wire:loading wire:target="generateInsights">Regenerating...</span>
                    </button>
                @endif
            </div>

            @if ($aiInsights)
                <div class="prose prose-neutral dark:prose-invert max-w-none text-sm text-neutral-700 dark:text-neutral-300 leading-relaxed font-light space-y-4">
                    {!! \Illuminate\Support\Str::markdown($aiInsights) !!}
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-neutral-500 dark:text-neutral-400 font-light mb-4 text-sm">
                        Get a detailed, natural language summary of your form submissions and trends.
                    </p>
                    <button wire:click="generateInsights" wire:loading.attr="disabled" 
                        class="relative inline-flex items-center justify-center px-6 py-2.5 text-sm font-medium transition-all duration-300 group overflow-hidden border border-neutral-900 dark:border-neutral-100 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100 focus:outline-none">
                        <span wire:loading.remove wire:target="generateInsights" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Generate Insights
                        </span>
                        <span wire:loading.flex wire:target="generateInsights" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Analyzing responses...
                        </span>
                    </button>
                </div>
            @endif
        </div>
    @endif

    @if ($totalAllTime > 0)
        {{-- ── Submissions Over Time ── --}}
        <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6 transition-colors duration-300"
            wire:ignore>
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4">
                Submissions Over Time</p>
            <div style="height: 220px;">
                <canvas id="analyticsLineChart"></canvas>
            </div>
        </div>



        {{-- ── Sentiment Analysis ── --}}
        @if ($hasSentimentData && !empty($sentimentStatsByField))
            <div class="mb-6">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    AI Sentiment Analysis (Per Text Input)
                </p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($sentimentStatsByField as $fieldId => $fieldData)
                        <div wire:key="sentiment-card-{{ $fieldId }}" class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300 flex flex-col justify-between">
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-800 dark:text-neutral-200 mb-4 pb-2 border-b border-neutral-100 dark:border-neutral-900 truncate" title="{{ $fieldData['label'] }}">
                                    {{ $fieldData['label'] }}
                                </h4>
                                <div class="flex flex-col sm:flex-row items-center justify-around gap-6">
                                    <div style="height: 140px; width: 140px;" class="relative shrink-0">
                                        <canvas id="chart-{{ $fieldId }}"></canvas>
                                    </div>
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
                                    <div class="space-y-3 w-full max-w-xs">
                                        <div>
                                            <div class="flex justify-between items-baseline text-xs mb-1">
                                                <span class="text-neutral-600 dark:text-neutral-400 font-medium flex items-center gap-1.5">
                                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Positive
                                                </span>
                                                <span class="text-neutral-500 dark:text-neutral-400 tabular-nums font-semibold">
                                                    {{ $posCount }} <span class="text-[10px] font-normal">({{ $posPct }}%)</span>
                                                    @if ($posCount > 0)
                                                        <span class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg: {{ $posAvg }}%</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                                                <div class="h-1.5 bg-emerald-500 transition-all duration-500" style="width: {{ $posPct }}%"></div>
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
                                                        <span class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg: {{ $neuAvg }}%</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                                                <div class="h-1.5 bg-neutral-400 dark:bg-neutral-500 transition-all duration-500" style="width: {{ $neuPct }}%"></div>
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
                                                        <span class="text-[10px] font-medium text-purple-600 dark:text-purple-400 ml-1.5">Avg: {{ $negAvg }}%</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="h-1.5 bg-neutral-100 dark:bg-neutral-800">
                                                <div class="h-1.5 bg-rose-500 transition-all duration-500" style="width: {{ $negPct }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Top Emotions Footer -->
                                @if (!empty($fieldData['top_emotions']))
                                    <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-900 flex flex-wrap items-center gap-1.5 text-[11px] text-neutral-500 dark:text-neutral-400">
                                        <span>Top Emotions:</span>
                                        @foreach ($fieldData['top_emotions'] as $emo)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100 dark:border-purple-900/30 capitalize">
                                                {{ $emo }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Field Answer Distributions ── --}}
        @if (!empty($fieldStats))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                @foreach ($fieldStats as $stat)
                    <div
                        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
                        <p
                            class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4">
                            {{ $stat['label'] }}</p>
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
                                        <div class="h-1.5 bg-neutral-900 dark:bg-neutral-100 transition-all duration-500"
                                            style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($selectedFormId)
            <div
                class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6 transition-colors duration-300">
                <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
                    Field Breakdown</p>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 font-light">
                    No choice fields (dropdown, radio, checkbox) in this form.
                </p>
            </div>
        @endif
    @else
        {{-- ── Empty state ── --}}
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-12 text-center transition-colors duration-300">
            <svg class="w-16 h-16 mx-auto mb-4 text-neutral-300 dark:text-neutral-700" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-neutral-600 dark:text-neutral-400 font-light">
                No submissions yet. Share your forms to start collecting data.
            </p>
        </div>
    @endif

    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endassets

    @script
        <script>
            let lineChart = null;
            let sentimentCharts = {};

            function chartPalette() {
                const dark = document.documentElement.classList.contains('dark');
                return {
                    line: dark ? '#f5f5f5' : '#171717',
                    fill: dark ? 'rgba(245,245,245,0.07)' : 'rgba(23,23,23,0.06)',
                    grid: dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
                    tick: dark ? '#737373' : '#a3a3a3',
                    sentiment: ['#10b981', '#a3a3a3', '#f43f5e'] // Positive (emerald), Neutral (neutral-400), Negative (rose-500)
                };
            }

            function buildCharts(dailyLabels, dailyData, sentimentFieldsData) {
                const p = chartPalette();

                // Line chart
                const lineCanvas = document.getElementById('analyticsLineChart');
                if (lineCanvas) {
                    if (lineChart) {
                        lineChart.destroy();
                        lineChart = null;
                    }
                    lineChart = new Chart(lineCanvas, {
                        type: 'line',
                        data: {
                            labels: dailyLabels,
                            datasets: [{
                                data: dailyData,
                                borderColor: p.line,
                                backgroundColor: p.fill,
                                borderWidth: 1.5,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                fill: true,
                                tension: 0.3,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: item => ` ${item.raw} submission${item.raw !== 1 ? 's' : ''}`,
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: p.grid
                                    },
                                    ticks: {
                                        color: p.tick,
                                        maxTicksLimit: 10
                                    }
                                },
                                y: {
                                    grid: {
                                        color: p.grid
                                    },
                                    ticks: {
                                        color: p.tick,
                                        stepSize: 1,
                                        beginAtZero: true
                                    }
                                },
                            },
                        },
                    });
                }

                // Destroy old sentiment charts
                for (const key in sentimentCharts) {
                    if (sentimentCharts[key]) {
                        sentimentCharts[key].destroy();
                    }
                }
                sentimentCharts = {};

                // Re-create sentiment charts for each field
                if (sentimentFieldsData) {
                    sentimentFieldsData.forEach(field => {
                        const sentimentCanvas = document.getElementById('chart-' + field.field_id);
                        if (sentimentCanvas) {
                            const posVal = typeof field.stats.positive === 'object' ? (field.stats.positive.count ?? 0) : field.stats.positive;
                            const neuVal = typeof field.stats.neutral === 'object' ? (field.stats.neutral.count ?? 0) : field.stats.neutral;
                            const negVal = typeof field.stats.negative === 'object' ? (field.stats.negative.count ?? 0) : field.stats.negative;

                            sentimentCharts[field.field_id] = new Chart(sentimentCanvas, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Positive', 'Neutral', 'Negative'],
                                    datasets: [{
                                        data: [posVal, neuVal, negVal],
                                        backgroundColor: p.sentiment,
                                        borderWidth: 0,
                                        hoverOffset: 4
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: item => ` ${item.label}: ${item.raw} answer${item.raw !== 1 ? 's' : ''}`,
                                            },
                                        },
                                    },
                                    cutout: '70%'
                                },
                            });
                        }
                    });
                }
            }

            // Initial render
            buildCharts(
                @js($dailyLabels),
                @js($dailyData),
                @js(array_values($sentimentStatsByField))
            );

            // Re-draw when Livewire fires updated chart data
            $wire.on('analyticsChartData', ({
                dailyLabels,
                dailyData,
                sentimentFieldsData
            }) => {
                buildCharts(dailyLabels, dailyData, sentimentFieldsData);
            });
        </script>
    @endscript
</div>

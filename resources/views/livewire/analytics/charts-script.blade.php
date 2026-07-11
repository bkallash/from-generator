{{-- Chart.js init (bundled as window.Chart in app.js). @script works in this include because Livewire binds $this for nested views during render. --}}
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
                // Card surface — drawn under the chart so canvas clear never flashes white
                surface: dark ? '#0a0a0a' : '#ffffff',
                sentiment: ['#10b981', '#a3a3a3', '#f43f5e'],
            };
        }

        /** Plugin: paint card-matching background before each chart frame */
        const surfacePlugin = {
            id: 'analyticsSurface',
            beforeDraw(chart) {
                const {
                    ctx,
                    width,
                    height
                } = chart;
                const p = chartPalette();
                ctx.save();
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = p.surface;
                ctx.fillRect(0, 0, width, height);
                ctx.restore();
            },
        };

        function destroyCharts() {
            if (lineChart) {
                lineChart.destroy();
                lineChart = null;
            }
            for (const key in sentimentCharts) {
                sentimentCharts[key]?.destroy();
            }
            sentimentCharts = {};
        }

        function buildCharts(dailyLabels, dailyData, sentimentFieldsData) {
            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js not loaded yet');
                return;
            }

            const Chart = window.Chart;
            const p = chartPalette();
            // Instant paint — animated draws look like a flash on dark cards
            const noAnim = {
                animation: false,
                animations: false
            };

            destroyCharts();

            const lineCanvas = document.getElementById('analyticsLineChart');
            if (lineCanvas && Array.isArray(dailyLabels) && Array.isArray(dailyData)) {
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
                        ...noAnim,
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
                                },
                            },
                            y: {
                                grid: {
                                    color: p.grid
                                },
                                ticks: {
                                    color: p.tick,
                                    stepSize: 1,
                                    beginAtZero: true
                                },
                            },
                        },
                    },
                    plugins: [surfacePlugin],
                });
            }

            if (sentimentFieldsData && Array.isArray(sentimentFieldsData)) {
                sentimentFieldsData.forEach(field => {
                    const fieldId = field.field_id;
                    const sentimentCanvas = document.getElementById('chart-' + fieldId);
                    if (!sentimentCanvas) return;

                    const posVal = typeof field.stats?.positive === 'object' ?
                        (field.stats.positive.count ?? 0) :
                        (field.stats?.positive ?? 0);
                    const neuVal = typeof field.stats?.neutral === 'object' ?
                        (field.stats.neutral.count ?? 0) :
                        (field.stats?.neutral ?? 0);
                    const negVal = typeof field.stats?.negative === 'object' ?
                        (field.stats.negative.count ?? 0) :
                        (field.stats?.negative ?? 0);

                    sentimentCharts[fieldId] = new Chart(sentimentCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Positive', 'Neutral', 'Negative'],
                            datasets: [{
                                data: [posVal, neuVal, negVal],
                                backgroundColor: p.sentiment,
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            ...noAnim,
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: item =>
                                            ` ${item.label}: ${item.raw} answer${item.raw !== 1 ? 's' : ''}`,
                                    },
                                },
                            },
                            cutout: '70%',
                        },
                        plugins: [surfacePlugin],
                    });
                });
            }
        }

        // Initial render only (no CDN wait — Chart is on window from app.js).
        // Use formatSentimentForJs payload (flat counts) so first paint matches live updates.
        buildCharts(
            @js($dailyLabels),
            @js($dailyData),
            @js($sentimentFieldsData)
        );

        // Filter / range changes — server dispatches only after hydrate (not first paint)
        $wire.on('analyticsChartData', ({
            dailyLabels,
            dailyData,
            sentimentFieldsData
        }) => {
            buildCharts(dailyLabels, dailyData, sentimentFieldsData);
        });

        // Theme toggle while staying on Analytics — rebuild only when dark flips
        let lastDark = document.documentElement.classList.contains('dark');
        const themeObserver = new MutationObserver(() => {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark === lastDark) return;
            lastDark = isDark;

            if (!lineChart && Object.keys(sentimentCharts).length === 0) return;

            const labels = lineChart?.data?.labels ?? @js($dailyLabels);
            const data = lineChart?.data?.datasets?.[0]?.data ?? @js($dailyData);
            const sentimentPayload = Object.keys(sentimentCharts).length ?
                Object.keys(sentimentCharts).map(fieldId => {
                    const c = sentimentCharts[fieldId];
                    const d = c?.data?.datasets?.[0]?.data ?? [0, 0, 0];
                    return {
                        field_id: fieldId,
                        stats: {
                            positive: d[0],
                            neutral: d[1],
                            negative: d[2]
                        },
                    };
                }) :
                @js($sentimentFieldsData);

            buildCharts(labels, data, sentimentPayload);
        });
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Clean up when navigating away from this component
        document.addEventListener('livewire:navigating', () => {
            themeObserver.disconnect();
            destroyCharts();
        }, {
            once: true
        });
    </script>
@endscript

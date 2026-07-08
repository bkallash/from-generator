<div>

    <!-- Welcome Card -->
    <div
        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 mb-6 transition-colors duration-300">
        <h2 class="text-3xl font-light tracking-tight mb-3">
            Welcome <strong class="font-semibold">back</strong>
        </h2>
        <p class="text-neutral-600 dark:text-neutral-400 font-light mb-8">
            You're all set. Start building beautiful forms and collect meaningful data.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Create Form Card -->
            <a href="{{ route('dashboard', ['view' => 'forms']) }}" wire:navigate class="block group focus:outline-none text-left w-full">
                <div
                    class="h-full p-6 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 transition-all duration-300 hover:border-neutral-900 dark:hover:border-neutral-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 border-2 border-neutral-900 dark:border-neutral-100 mb-4 transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 transition-colors duration-300">
                        Create Form
                    </h3>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 font-light">
                        Start building your first form with our intuitive builder
                    </p>
                </div>
            </a>

            <!-- Analytics Card -->
            <a href="{{ route('dashboard', ['view' => 'analytics']) }}" wire:navigate class="block group focus:outline-none text-left w-full">
                <div
                    class="h-full p-6 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 transition-all duration-300 hover:border-neutral-900 dark:hover:border-neutral-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 border-2 border-neutral-900 dark:border-neutral-100 mb-4 transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 transition-colors duration-300">
                        Analytics
                    </h3>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 font-light">
                        Track form submissions and analyze your data
                    </p>
                </div>
            </a>

            <!-- Submissions Card -->
            <a href="{{ route('dashboard', ['view' => 'submissions']) }}" wire:navigate class="block group focus:outline-none text-left w-full">
                <div
                    class="h-full p-6 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 transition-all duration-300 hover:border-neutral-900 dark:hover:border-neutral-100">
                    <div
                        class="flex items-center justify-center w-12 h-12 border-2 border-neutral-900 dark:border-neutral-100 mb-4 transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 transition-colors duration-300">
                        Submissions
                    </h3>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400 font-light">
                        View and manage all form submissions
                    </p>
                </div>
            </a>
        </div>
    </div>



    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-2">Total Forms</p>
            <p class="text-3xl font-light tracking-tight">{{ $totalForms }} </p>
        </div>
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-2">Submissions</p>
            <p class="text-3xl font-light tracking-tight">{{ $totalSubmissions }} </p>
        </div>
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-2">This Week</p>
            <p class="text-3xl font-light tracking-tight">{{ $thisWeekSubmissions }}</p>
        </div>
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 transition-colors duration-300">
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-2">Active Forms</p>
            <p class="text-3xl font-light tracking-tight">{{ $activeForms }}</p>
        </div>
    </div>

    <!-- Intelligence Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6"
         x-data="{
             status: {{ json_encode($alertsStatus) }},
             aiAlerts: {{ json_encode($aiAlerts) }},
             aiDigest: {{ json_encode($aiDigest) }},
             cacheKey: {{ json_encode($alertCacheKey) }},
             dismissedAlerts: JSON.parse(localStorage.getItem('dismissed_ai_alerts_' + {{ json_encode($alertCacheKey) }}) || '[]'),
             dismissAlert(type, formId, title) {
                 const key = `${type}_${formId}_${title}`;
                 if (!this.dismissedAlerts.includes(key)) {
                     this.dismissedAlerts.push(key);
                     localStorage.setItem('dismissed_ai_alerts_' + this.cacheKey, JSON.stringify(this.dismissedAlerts));
                 }
             },
             isDismissed(type, formId, title) {
                 const key = `${type}_${formId}_${title}`;
                 return this.dismissedAlerts.includes(key);
             },
             get visibleAlertsCount() {
                 let count = 0;
                 this.aiAlerts.forEach(alert => {
                     if (!this.isDismissed(alert.type, alert.form_id, alert.title)) {
                         count++;
                     }
                 });
                 return count;
             },
             pollIntelligence() {
                 if (this.status !== 'loading') return;
                 const interval = setInterval(async () => {
                     try {
                         const response = await fetch('/dashboard/intelligence');
                         if (response.ok) {
                             const data = await response.json();
                             if (data.status === 'ready') {
                                 this.aiAlerts = data.aiAlerts;
                                 this.aiDigest = data.aiDigest;
                                 this.cacheKey = data.alertCacheKey;
                                 this.status = 'ready';
                                 clearInterval(interval);
                             }
                         }
                     } catch (e) {
                         console.error('Error polling intelligence:', e);
                     }
                 }, 3000);
             }
         }"
         x-init="pollIntelligence()">
        <!-- Left Column: AI Alerts Feed -->
        <div class="lg:col-span-2 bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-light tracking-tight">
                        Intelligence <strong class="font-semibold">Alerts</strong>
                    </h3>
                    <span class="text-xs font-mono text-neutral-500 uppercase">Proactive Feed</span>
                </div>

                {{-- Alert Cards --}}
                <div class="space-y-4">
                    {{-- Skeleton Loader for Background Job --}}
                    <template x-if="status === 'loading'">
                        <div class="space-y-4 animate-pulse">
                            <div class="p-5 border border-neutral-100 dark:border-neutral-900 bg-neutral-50 dark:bg-neutral-900/30 flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-3 w-1/4 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                    <div class="h-2 w-16 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                </div>
                                <div class="h-4 w-3/4 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                <div class="h-3 w-1/2 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                            </div>
                            <div class="p-5 border border-neutral-100 dark:border-neutral-900 bg-neutral-50 dark:bg-neutral-900/30 flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-3 w-1/3 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                    <div class="h-2 w-16 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                </div>
                                <div class="h-4 w-5/6 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                                <div class="h-3 w-2/3 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Dynamic AI Alert Cards --}}
                    <template x-if="status === 'ready'">
                        <div class="space-y-4">
                            <template x-for="alert in aiAlerts" :key="alert.type + '_' + alert.form_id + '_' + alert.title">
                                <div x-show="!isDismissed(alert.type, alert.form_id, alert.title)"
                                     x-transition:leave="transition ease-in duration-300 transform opacity-0 scale-95"
                                     :class="{
                                         'bg-rose-50/50 dark:bg-rose-950/10 border-rose-200 dark:border-rose-900/50 text-rose-900 dark:text-rose-100': alert.type === 'danger',
                                         'bg-amber-50/50 dark:bg-amber-950/10 border-amber-200 dark:border-amber-900/50 text-amber-950 dark:text-amber-100': alert.type === 'warning',
                                         'bg-violet-50/50 dark:bg-violet-950/10 border-violet-200 dark:border-violet-900/50 text-violet-950 dark:text-violet-100': alert.type === 'info',
                                         'bg-blue-50/50 dark:bg-blue-950/10 border-blue-200 dark:border-blue-900/50 text-blue-950 dark:text-blue-100': alert.type === 'notice'
                                     }"
                                     class="relative overflow-hidden border p-4 transition-all duration-300 flex items-start gap-3.5 group">
                                     {{-- Left accent stripe --}}
                                     <div :class="{
                                         'bg-rose-500': alert.type === 'danger',
                                         'bg-amber-500': alert.type === 'warning',
                                         'bg-violet-500': alert.type === 'info',
                                         'bg-blue-500': alert.type === 'notice'
                                     }" class="absolute top-0 left-0 w-[3px] h-full"></div>

                                     {{-- Icon --}}
                                     <div :class="{
                                         'text-rose-500': alert.type === 'danger',
                                         'text-amber-600 dark:text-amber-500': alert.type === 'warning',
                                         'text-violet-600 dark:text-violet-500': alert.type === 'info',
                                         'text-blue-600 dark:text-blue-500': alert.type === 'notice'
                                     }" class="shrink-0 mt-0.5">
                                         <template x-if="alert.type === 'danger' || alert.type === 'warning'">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                             </svg>
                                         </template>
                                         <template x-if="alert.type === 'info'">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                             </svg>
                                         </template>
                                         <template x-if="alert.type === 'notice'">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                             </svg>
                                         </template>
                                     </div>

                                     <div class="flex-1 min-w-0">
                                         <div class="flex items-center justify-between gap-4">
                                             <p class="text-xs font-semibold uppercase tracking-wider opacity-90" x-text="alert.title"></p>
                                             <span class="text-[10px] opacity-60 font-mono">AI Intelligence Alert</span>
                                         </div>
                                         <p class="text-sm mt-1 leading-relaxed font-light" x-text="alert.message"></p>
                                     </div>

                                     {{-- Dismiss button --}}
                                     <button @click="dismissAlert(alert.type, alert.form_id, alert.title)"
                                             class="shrink-0 text-neutral-400 dark:text-neutral-600 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors self-start p-1"
                                             aria-label="Dismiss alert">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                         </svg>
                                     </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Empty/Digest State --}}
                    <div x-show="status === 'ready' && visibleAlertsCount === 0" class="border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900/40 p-6 flex items-start gap-4 transition-all duration-300">
                        <div class="shrink-0 text-emerald-500 mt-0.5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">All Systems Clear</h4>
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1 font-light leading-relaxed" x-text="aiDigest">
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Form Health -->
        <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-light tracking-tight">
                    Form <strong class="font-semibold">Health</strong>
                </h3>
                <span class="text-xs font-mono text-neutral-500 uppercase">7-Day Pulse</span>
            </div>

            @if (empty($formHealthMap))
                <div class="text-center py-12 text-neutral-500 dark:text-neutral-500">
                    <p class="text-sm font-light">No forms registered yet.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($formHealthMap as $formHealth)
                        @php
                            $sentimentColorClass = 'text-neutral-500 dark:text-neutral-400';
                            $sentimentBgClass = 'bg-neutral-200 dark:bg-neutral-800';
                            $sentimentText = 'No sentiment data';

                            if ($formHealth['positive_sentiment_pct'] !== null) {
                                $pct = $formHealth['positive_sentiment_pct'];
                                $sentimentText = $pct . '% positive';

                                if ($pct >= 70) {
                                    $sentimentColorClass = 'text-emerald-600 dark:text-emerald-400';
                                    $sentimentBgClass = 'bg-emerald-500';
                                } elseif ($pct >= 40) {
                                    $sentimentColorClass = 'text-amber-600 dark:text-amber-400';
                                    $sentimentBgClass = 'bg-amber-500';
                                } else {
                                    $sentimentColorClass = 'text-rose-600 dark:text-rose-400';
                                    $sentimentBgClass = 'bg-rose-500';
                                }
                            }
                        @endphp
                        <a href="{{ route('dashboard', ['view' => 'analytics', 'form' => $formHealth['id']]) }}" wire:navigate class="block group focus:outline-none">
                            <div class="p-4 border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 transition-all duration-300 hover:border-neutral-900 dark:hover:border-neutral-100 flex flex-col justify-between">
                                <div class="flex items-start justify-between gap-4">
                                    <h4 class="text-sm font-semibold truncate text-neutral-900 dark:text-neutral-100 group-hover:underline">
                                        {{ $formHealth['title'] }}
                                    </h4>
                                    @if ($formHealth['is_active'])
                                        <span class="inline-flex w-2 h-2 rounded-full bg-emerald-500" title="Active"></span>
                                    @else
                                        <span class="inline-flex w-2 h-2 rounded-full bg-neutral-300 dark:bg-neutral-700" title="Inactive"></span>
                                    @endif
                                </div>
                                <div class="mt-4 flex items-center justify-between text-xs font-light text-neutral-500 dark:text-neutral-400">
                                    <span>{{ $formHealth['submissions_7d'] }} {{ Str::plural('submission', $formHealth['submissions_7d']) }} (7d)</span>
                                    
                                    <span class="flex items-center gap-1.5 {{ $sentimentColorClass }}">
                                        @if ($formHealth['positive_sentiment_pct'] !== null)
                                            <span class="w-1.5 h-1.5 rounded-full {{ $sentimentBgClass }}"></span>
                                        @endif
                                        {{ $sentimentText }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

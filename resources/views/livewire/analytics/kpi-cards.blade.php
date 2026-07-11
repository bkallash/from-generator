{{-- KPI summary cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6">
        <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
            In Period
        </p>
        <p class="text-3xl font-light tracking-tight">{{ $totalInRange }}</p>
        <p class="text-xs text-neutral-400 mt-1">last {{ $range }} days</p>
    </div>

    <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6">
        <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
            This Week
        </p>
        <p class="text-3xl font-light tracking-tight">{{ $thisWeek }}</p>
        @if ($weekChange > 0)
            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">↑ {{ $weekChange }}% vs last week</p>
        @elseif ($weekChange < 0)
            <p class="text-xs text-red-600 dark:text-red-400 mt-1">↓ {{ abs($weekChange) }}% vs last week</p>
        @else
            <p class="text-xs text-neutral-400 mt-1">same as last week</p>
        @endif
    </div>

    <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6">
        <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
            This Month
        </p>
        <p class="text-3xl font-light tracking-tight">{{ $thisMonth }}</p>
        <p class="text-xs text-neutral-400 mt-1">{{ now()->format('F') }}</p>
    </div>

    <div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6">
        <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-2">
            All Time
        </p>
        <p class="text-3xl font-light tracking-tight">{{ $totalAllTime }}</p>
        <p class="text-xs text-neutral-400 mt-1">
            {{ $activeForms }} active {{ Str::plural('form', $activeForms) }}
        </p>
    </div>
</div>

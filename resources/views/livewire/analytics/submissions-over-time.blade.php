{{-- Line chart: submissions over the selected range --}}
{{-- wire:ignore keeps Chart.js canvas out of Livewire morph (avoids destroy/flash) --}}
<div class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-6 mb-6"
    wire:ignore
    data-chart-surface>
    <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 mb-4">
        Submissions Over Time
    </p>
    <div class="relative h-[220px] bg-white dark:bg-neutral-950">
        <canvas id="analyticsLineChart" class="block w-full h-full bg-transparent"></canvas>
    </div>
</div>

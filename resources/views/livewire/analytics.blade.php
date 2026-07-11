<div>
    {{-- Extracted partials inherit Livewire public props + render() view data --}}
    @include('livewire.analytics.filter-bar')

    @include('livewire.analytics.kpi-cards')

    @include('livewire.analytics.ai-insights')

    @if ($totalAllTime > 0)
        @include('livewire.analytics.submissions-over-time')
        @include('livewire.analytics.sentiment-section')
        @include('livewire.analytics.field-distributions')
    @else
        @include('livewire.analytics.empty-state')
    @endif

    {{-- @script lives in this partial; Livewire binds $this for nested includes during render --}}
    @include('livewire.analytics.charts-script')
</div>

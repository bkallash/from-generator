@extends('layouts.dashboard')

@section('dashboard-content')
    <div class="transition-colors duration-300">
        @if ($view === 'dashboard')
            @include('dashboard.overview')
        @elseif ($view === 'forms')
            @include('dashboard.forms')
        @elseif ($view === 'submissions')
            @include('dashboard.submissions')
        @elseif ($view === 'analytics')
            <div>
                @livewire('analytics')
            </div>
        @elseif ($view === 'settings')
            <div style="min-height: 400px;">
                <livewire:profile-settings />
            </div>
        @endif
    </div>
@endsection

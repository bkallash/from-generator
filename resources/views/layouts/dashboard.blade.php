<!DOCTYPE html>
<html lang="en" wire:ignore.self>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard - Form Generator')</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    {{-- Apply dark mode immediately (before CSS paint) to prevent FOUC / flashbang --}}
    <script>
        (function() {
            var html = document.documentElement;
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) {
                html.classList.add('dark');
                html.style.colorScheme = 'dark';
            } else {
                html.style.colorScheme = 'light';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>

<body
    class="font-sans antialiased bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100">

    <div class="flex h-screen overflow-hidden">
        @unless (trim($__env->yieldContent('hide-chrome')))
            <!-- Left Sidebar -->
            <aside
                class="w-64 bg-white dark:bg-neutral-950 border-r border-neutral-200 dark:border-neutral-800 flex flex-col">
                <!-- Logo -->
                <div class="h-20 flex items-center px-6 border-b border-neutral-200 dark:border-neutral-800">
                    <a href="/" class="text-lg font-semibold tracking-tight">FORM / GENERATOR</a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard', ['view' => 'dashboard']) }}" wire:navigate
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 {{ request('view', 'dashboard') === 'dashboard' ? 'bg-neutral-100 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('dashboard', ['view' => 'forms']) }}" wire:navigate
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 {{ request('view') === 'forms' ? 'bg-neutral-100 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Forms</span>
                    </a>

                    <a href="{{ route('dashboard', ['view' => 'submissions']) }}" wire:navigate
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 {{ request('view') === 'submissions' ? 'bg-neutral-100 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Submissions</span>
                    </a>

                    <a href="{{ route('dashboard', ['view' => 'analytics']) }}" wire:navigate
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 {{ request('view') === 'analytics' ? 'bg-neutral-100 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Analytics</span>
                    </a>

                    {{-- Create Form shortcut --}}
                    <a href="{{ route('forms.create') }}"
                        class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 mt-3 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:opacity-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Form</span>
                    </a>

                    <div class="pt-6 border-t border-neutral-200 dark:border-neutral-800 mt-6">
                        <a href="{{ route('dashboard', ['view' => 'settings']) }}" wire:navigate
                            class="w-full flex items-center space-x-3 px-4 py-3 text-sm font-medium transition-colors duration-200 {{ request('view') === 'settings' ? 'bg-neutral-100 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Settings</span>
                        </a>
                    </div>
                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-neutral-200 dark:border-neutral-800">
                    <livewire:user-profile />
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-2 text-sm font-medium text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors duration-200 text-left">
                            Sign out
                        </button>
                    </form>
                </div>
            </aside>
        @endunless

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            @unless (trim($__env->yieldContent('hide-chrome')))
                <!-- Top Header -->
                <header
                    class="h-20 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800">
                    <div class="h-full px-6 flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-light tracking-tight">
                                {{ ucfirst(request('view', 'dashboard')) }}
                            </h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Dark Mode Toggle -->
                            <button id="themeToggle"
                                class="p-2 rounded border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-all duration-200"
                                aria-label="Toggle dark mode">
                                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                                    </path>
                                </svg>
                                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                                    </path>
                                </svg>
                            </button>
                            @yield('header-actions')
                        </div>
                    </div>
                </header>
            @endunless

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('dashboard-content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>

</html>

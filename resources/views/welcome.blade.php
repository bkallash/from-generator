@extends('layouts.app')

@section('title', 'Form Generator - Simple Forms, Powerful Insights')
@section('meta_description', 'Build professional forms without the bloat. Drag, drop, done. Track every submission with live analytics.')

@section('json_ld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebSite",
    "name": "Form Generator",
    "url": "{{ url('/') }}",
    "description": "Build professional forms without the bloat. Drag, drop, done. Track every submission with live analytics.",
    "potentialAction": {
        "@@type": "SearchAction",
        "target": "{{ url('/') }}/?q={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SoftwareApplication",
    "name": "Form Generator",
    "operatingSystem": "All",
    "applicationCategory": "BusinessApplication",
    "offers": {
        "@@type": "Offer",
        "price": "0.00",
        "priceCurrency": "USD"
    }
}
</script>
@endsection

@section('nav-actions')
    <a href="/login"
        class="px-6 py-2.5 text-sm font-medium border border-neutral-900 dark:border-neutral-100 rounded transition-all duration-200 hover:bg-neutral-900 dark:hover:bg-neutral-100 hover:text-white dark:hover:text-neutral-900">
        Sign In
    </a>
@endsection

@section('content')
    <!-- Hero Section -->
    <div class="max-w-7xl mx-auto px-6">
        <section class="py-32 text-center">
            <h1 class="text-6xl md:text-7xl font-light tracking-tighter leading-tight mb-6">
                Simple forms.<br>
                <strong class="font-semibold">Powerful insights.</strong>
            </h1>
            <p
                class="text-xl md:text-2xl text-neutral-600 dark:text-neutral-400 font-light max-w-2xl mx-auto mb-12 leading-relaxed">
                Build professional forms without the bloat. Get the data you need, presented the way you want.
            </p>
            <a href="/register"
                class="inline-block bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-10 py-4 rounded text-base font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                Get Started
            </a>
        </section>
    </div>

    <!-- Grid Section -->
    <div class="max-w-7xl mx-auto px-6">
        <section class="py-24">
            <div
                class="border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 transition-colors duration-300 grid grid-cols-1 md:grid-cols-3">
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">No Code Required</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        Drag, drop, done. Build forms that work without writing a single line of code.
                    </p>
                </div>
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">Live Analytics</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        Track every submission as it happens. Filter, sort, and export your data instantly.
                    </p>
                </div>
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">Share Anywhere</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        One link works everywhere. Embed on your site or share directly.
                    </p>
                </div>
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">Custom Branding</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        Your forms, your style. Add your logo, colors, and custom domain.
                    </p>
                </div>
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">Secure by Default</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        SSL encryption, automatic backups, and enterprise-grade security built in.
                    </p>
                </div>
                <div
                    class="p-12 border-r border-b border-neutral-200 dark:border-neutral-800 md:border-b-0 md:last:border-r-0">
                    <h3 class="text-lg font-semibold mb-4">Always Available</h3>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] leading-relaxed font-light">
                        99.9% uptime guarantee. Your forms work when you need them to.
                    </p>
                </div>
            </div>
        </section>
    </div>

    <!-- Process Section -->
    <section class="py-24 bg-white dark:bg-neutral-950 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-5xl font-light text-center mb-16">How it works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="text-center">
                    <div
                        class="w-15 h-15 border-2 border-neutral-900 dark:border-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6 transition-colors duration-300">
                        <span class="text-2xl font-semibold">1</span>
                    </div>
                    <h4 class="text-lg font-semibold mb-3">Design</h4>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] font-light">
                        Choose your fields and customize the look
                    </p>
                </div>
                <div class="text-center">
                    <div
                        class="w-15 h-15 border-2 border-neutral-900 dark:border-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6 transition-colors duration-300">
                        <span class="text-2xl font-semibold">2</span>
                    </div>
                    <h4 class="text-lg font-semibold mb-3">Share</h4>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] font-light">
                        Get your link and publish it anywhere
                    </p>
                </div>
                <div class="text-center">
                    <div
                        class="w-15 h-15 border-2 border-neutral-900 dark:border-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6 transition-colors duration-300">
                        <span class="text-2xl font-semibold">3</span>
                    </div>
                    <h4 class="text-lg font-semibold mb-3">Analyze</h4>
                    <p class="text-neutral-600 dark:text-neutral-400 text-[15px] font-light">
                        Watch data come in and make decisions
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <div class="max-w-7xl mx-auto px-6">
        <section class="py-24 text-center">
            <h2 class="text-5xl font-light mb-8">Ready to start?</h2>
            <a href="/register"
                class="inline-block bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-10 py-4 rounded text-base font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                Create Your First Form
            </a>
        </section>

        <footer class="pb-12 text-center text-sm text-neutral-600 dark:text-neutral-400">
            <a href="{{ route('terms') }}" class="hover:underline">Terms of Service</a>
            <span class="mx-2">|</span>
            <a href="{{ route('privacy') }}" class="hover:underline">Privacy Policy</a>
        </footer>
    </div>
@endsection

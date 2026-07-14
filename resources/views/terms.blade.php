@extends('layouts.app')

@section('title', 'Terms of Service - Form Generator')
@section('meta_description', 'Review the Terms of Service for Form Generator. Learn about the rules, guidelines, and terms for using our online form builder.')

@section('nav-actions')
    <a href="{{ route('home') }}"
        class="px-4 py-2 text-sm font-medium border border-neutral-900 dark:border-neutral-100 rounded transition-all duration-200 hover:bg-neutral-900 dark:hover:bg-neutral-100 hover:text-white dark:hover:text-neutral-900">
        Back to Home
    </a>
@endsection

@section('content')
    <main class="max-w-4xl mx-auto px-6 py-16">
        <header class="mb-12">
            <h1 class="text-4xl md:text-5xl font-light tracking-tight mb-4">Terms of Service</h1>
            <p class="text-neutral-600 dark:text-neutral-400">Effective date: March 27, 2026</p>
        </header>

        <section class="space-y-8 text-neutral-700 dark:text-neutral-300 leading-relaxed">
            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">1. Agreement</h2>
                <p>
                    By accessing or using Form Generator, you agree to these Terms of Service. If you do not agree,
                    you must not use the service.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">2. Eligibility and Accounts
                </h2>
                <p>
                    You are responsible for maintaining the confidentiality of your account credentials and for all
                    activity that occurs under your account.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">3. Acceptable Use</h2>
                <p>
                    You may use the service only for lawful purposes. You agree not to upload or submit content that
                    violates laws, infringes third-party rights, or contains malicious code.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">4. User Content</h2>
                <p>
                    You retain ownership of the form content and submission data you provide. You grant Form Generator
                    a limited license to host, process, and display that content solely to operate the service.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">5. Service Availability</h2>
                <p>
                    We may modify, suspend, or discontinue features at any time. We do not guarantee uninterrupted or
                    error-free operation.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">6. Termination</h2>
                <p>
                    We may suspend or terminate accounts that violate these terms or create security, legal, or
                    operational risk.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">7. Disclaimer</h2>
                <p>
                    The service is provided on an "as is" and "as available" basis, without warranties of any kind,
                    to the maximum extent permitted by law.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">8. Limitation of Liability
                </h2>
                <p>
                    To the maximum extent permitted by law, Form Generator is not liable for indirect, incidental,
                    special, consequential, or punitive damages.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">9. Changes to Terms</h2>
                <p>
                    We may update these terms from time to time. Continued use of the service after changes become
                    effective constitutes acceptance of the updated terms.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">10. Contact</h2>
                <p>
                    For questions about these terms, contact us at
                    <a href="mailto:support@formgenerator.me" class="font-medium underline">support@formgenerator.me</a>.
                </p>
            </div>
        </section>
    </main>
@endsection

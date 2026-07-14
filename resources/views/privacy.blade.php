@extends('layouts.app')

@section('title', 'Privacy Policy - Form Generator')
@section('meta_description', 'Read the privacy policy for Form Generator. Learn how we handle, store, and protect your personal data and form submissions.')

@section('nav-actions')
    <a href="{{ route('home') }}"
        class="px-4 py-2 text-sm font-medium border border-neutral-900 dark:border-neutral-100 rounded transition-all duration-200 hover:bg-neutral-900 dark:hover:bg-neutral-100 hover:text-white dark:hover:text-neutral-900">
        Back to Home
    </a>
@endsection

@section('content')
    <main class="max-w-4xl mx-auto px-6 py-16">
        <header class="mb-12">
            <h1 class="text-4xl md:text-5xl font-light tracking-tight mb-4">Privacy Policy</h1>
            <p class="text-neutral-600 dark:text-neutral-400">Effective date: March 27, 2026</p>
        </header>

        <section class="space-y-8 text-neutral-700 dark:text-neutral-300 leading-relaxed">
            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">1. Information We Collect</h2>
                <p>
                    We collect information you provide directly, including account details (such as name and email),
                    form configurations, and submission data entered through forms.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">2. Technical Data</h2>
                <p>
                    We may process technical metadata such as IP address, browser user agent, timestamps, and security
                    logs to operate, secure, and improve the service.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">3. How We Use Data</h2>
                <p>
                    We use personal data to create and manage accounts, deliver form functionality, support analytics,
                    send account-related notifications, and maintain platform security.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">4. Data Sharing</h2>
                <p>
                    We do not sell personal data. We may share data with infrastructure and service providers that help
                    us run the platform, subject to appropriate confidentiality and security protections.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">5. Data Retention</h2>
                <p>
                    We retain data as long as needed to provide the service, meet legal obligations, resolve disputes,
                    and enforce agreements. You can request account deletion subject to legal requirements.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">6. Security</h2>
                <p>
                    We implement administrative, technical, and organizational safeguards designed to protect personal
                    data. No method of transmission or storage is completely secure.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">7. Your Rights</h2>
                <p>
                    Depending on your jurisdiction, you may have rights to access, correct, delete, or restrict use of
                    your personal data. Contact us to submit a request.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">8. International Transfers
                </h2>
                <p>
                    Your information may be processed in countries other than your own. Where required, we use
                    safeguards for cross-border data transfers.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">9. Changes to This Policy</h2>
                <p>
                    We may update this policy periodically. We will post the revised version with an updated effective
                    date.
                </p>
            </div>

            <div>
                <h2 class="text-xl font-semibold mb-3 text-neutral-900 dark:text-neutral-100">10. Contact</h2>
                <p>
                    For privacy questions or requests, email
                    <a href="mailto:support@formgenerator.me" class="font-medium underline">support@formgenerator.me</a>.
                </p>
            </div>
        </section>
    </main>
@endsection

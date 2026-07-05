{{-- Forms View --}}
<div>
    <div
        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-light tracking-tight">
                Your <strong class="font-semibold">Forms</strong>
            </h3>
            <a href="{{ route('forms.create') }}"
                class="inline-block bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-4 py-2 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                Create New Form
            </a>
        </div>

        @if ($forms->isEmpty())
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-neutral-300 dark:text-neutral-700" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-neutral-600 dark:text-neutral-400 font-light mb-4">
                    No forms created yet
                </p>
                <a href="{{ route('forms.create') }}"
                    class="inline-block bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-6 py-3 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                    Create New Form
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border border-neutral-200 dark:border-neutral-800">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Title</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Submissions</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Created</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider w-10">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($forms as $form)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-200">
                                <td class="px-4 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    {{ $form->title }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 border text-xs font-medium {{ $form->is_active ? 'border-neutral-900 dark:border-neutral-100 text-neutral-900 dark:text-neutral-100' : 'border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-400' }}">
                                        {{ $form->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $form->submissions_count }}</td>
                                <td class="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $form->created_at->format('M d, Y') }}</td>

                                {{-- Actions --}}
                                <td class="px-4 py-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">

                                        {{-- Share button --}}
                                        <div x-data="{ copied: false }" class="relative">
                                            <button
                                                @click="
                                                    navigator.clipboard.writeText('{{ url('/f/' . $form->slug) }}');
                                                    copied = true;
                                                    setTimeout(() => copied = false, 2000);
                                                "
                                                class="inline-flex items-center justify-center w-8 h-8 border transition-colors duration-200"
                                                :class="copied
                                                    ?
                                                    'border-neutral-900 dark:border-neutral-100 text-neutral-900 dark:text-neutral-100 bg-neutral-50 dark:bg-neutral-900' :
                                                    'border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-neutral-100'"
                                                aria-label="Copy form link">
                                                <svg x-show="!copied" class="w-4 h-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                                </svg>
                                                <svg x-show="copied" x-cloak class="w-4 h-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>

                                            {{-- Copied tooltip --}}
                                            <div x-show="copied" x-cloak
                                                x-transition:enter="transition ease-out duration-150"
                                                x-transition:enter-start="opacity-0 -translate-y-1"
                                                x-transition:enter-end="opacity-100 translate-y-0"
                                                x-transition:leave="transition ease-in duration-100"
                                                x-transition:leave-start="opacity-100 translate-y-0"
                                                x-transition:leave-end="opacity-0 -translate-y-1"
                                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium whitespace-nowrap bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 pointer-events-none">
                                                Link copied!
                                                <span
                                                    class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-neutral-900 dark:border-t-neutral-100"></span>
                                            </div>
                                        </div>

                                        {{-- Three-dot menu --}}
                                        <div x-data="{ open: false, top: 0, right: 0 }" @click.outside="open = false">

                                            {{-- Trigger --}}
                                            <button
                                                @click="
                                                    const r = $el.getBoundingClientRect();
                                                    top   = r.bottom + 4;
                                                    right = window.innerWidth - r.right;
                                                    open  = !open;
                                                "
                                                class="inline-flex items-center justify-center w-8 h-8 border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors duration-200"
                                                aria-label="Form actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown panel — fixed so it escapes overflow containers --}}
                                            <div x-show="open"
                                                :style="`position:fixed; top:${top}px; right:${right}px;`"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="z-50 w-44 bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 shadow-lg origin-top-right"
                                                style="display: none;">

                                                <a href="{{ route('forms.edit', $form->id) }}"
                                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors duration-150">
                                                    <svg class="w-4 h-4 text-neutral-400 dark:text-neutral-500"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit form
                                                </a>

                                                @if ($form->is_active)
                                                    <form method="POST"
                                                        action="{{ route('forms.deactivate', $form) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="page"
                                                            value="{{ $forms->currentPage() }}">
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors duration-150">
                                                            <svg class="w-4 h-4 text-neutral-400 dark:text-neutral-500"
                                                                fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                            </svg>
                                                            Deactivate
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST"
                                                        action="{{ route('forms.activate', $form) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="page"
                                                            value="{{ $forms->currentPage() }}">
                                                        <button type="submit"
                                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors duration-150">
                                                            <svg class="w-4 h-4 text-neutral-400 dark:text-neutral-500"
                                                                fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Activate
                                                        </button>
                                                    </form>
                                                @endif

                                                <div class="border-t border-neutral-100 dark:border-neutral-800 my-1">
                                                </div>

                                                <form method="POST" action="{{ route('forms.destroy', $form) }}"
                                                    onsubmit="return confirm('Delete this form? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="page"
                                                        value="{{ $forms->currentPage() }}">
                                                    <button type="submit"
                                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors duration-150">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete form
                                                    </button>
                                                </form>
                                            </div>
                                        </div>{{-- end three-dot --}}
                                    </div>{{-- end inline-flex --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $forms->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Submissions View --}}
<div>
    <div x-data="submissionsView()" @keydown.escape.window="closeModal()">

        {{-- ── Header ── --}}
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <h3 class="text-xl font-light tracking-tight">
                    Form <strong class="font-semibold">Submissions</strong>
                </h3>

                @if ($submissions->count() > 0)
                    <a href="{{ route('submissions.export', array_filter(['sform' => request('sform'), 'ssearch' => request('ssearch')])) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-300 self-start sm:self-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV
                    </a>
                @endif
            </div>

            {{-- ── Filter bar ── --}}
            <form method="GET" action="{{ route('dashboard') }}"
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <input type="hidden" name="view" value="submissions">

                {{-- Form filter --}}
                <div class="relative flex-none">
                    <select name="sform" onchange="this.form.submit()"
                        class="appearance-none h-10 w-full sm:w-52 pl-3 pr-8 text-sm border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition">
                        @if ($userFormsSimple->isEmpty())
                            <option value="" disabled selected>No forms created</option>
                        @endif
                        @foreach ($userFormsSimple as $f)
                            <option value="{{ $f->id }}" @selected(request('sform') == $f->id)>{{ $f->title }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-neutral-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                {{-- Search --}}
                <div class="relative flex-1 min-w-0">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" name="ssearch" value="{{ request('ssearch') }}"
                        placeholder="Search submissions…"
                        class="h-10 w-full pl-9 pr-3 text-sm border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-transparent transition">
                </div>

                <button type="submit"
                    class="h-10 px-5 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-300 shrink-0">
                    Filter
                </button>

                @if (request('sform') || request('ssearch'))
                    <a href="{{ route('dashboard', ['view' => 'submissions']) }}"
                        class="inline-flex items-center justify-center h-10 px-4 text-sm border border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-300 shrink-0 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        {{-- ── No submissions ── --}}
        @if ($submissions->isEmpty())
            <div
                class="bg-white dark:bg-neutral-950 border border-t-0 border-neutral-200 dark:border-neutral-800 p-16 text-center transition-colors duration-300">
                <svg class="w-16 h-16 mx-auto mb-4 text-neutral-300 dark:text-neutral-700" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                @if (request('sform') || request('ssearch'))
                    <p class="text-neutral-600 dark:text-neutral-400 font-light mb-2">No submissions match your
                        filters.</p>
                    <a href="{{ route('dashboard', ['view' => 'submissions']) }}"
                        class="text-sm text-neutral-900 dark:text-neutral-100 underline underline-offset-4">Clear
                        filters</a>
                @else
                    <p class="text-neutral-600 dark:text-neutral-400 font-light">No submissions yet.</p>
                    <p class="text-sm text-neutral-500 dark:text-neutral-500 mt-1">Share your forms and submissions will
                        appear here.</p>
                @endif
            </div>

            {{-- ── Submissions table ── --}}
        @else
            {{-- Stats bar --}}
            <div
                class="flex items-center justify-between border-x border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 px-6 py-2.5 transition-colors duration-300">
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ $submissions->count() }}</span>
                    submission{{ $submissions->count() !== 1 ? 's' : '' }} on this page
                    @if (request('sform') || request('ssearch'))
                        &mdash; filtered
                    @endif
                </p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                    Page {{ $submissions->currentPage() }}
                </p>
            </div>

            {{-- Table --}}
            <div
                class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 overflow-x-auto transition-colors duration-300">
                <table class="min-w-full">
                    <thead
                        class="bg-neutral-50 dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Form</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Submitted</th>
                            <th
                                class="hidden md:table-cell px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                IP Address</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">
                                Preview</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-neutral-600 dark:text-neutral-400 uppercase tracking-wider w-20">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                        @foreach ($submissionRows as $row)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors duration-150 cursor-pointer group"
                                @click="openModal(@js($row['modalData']))">

                                {{-- Form name --}}
                                <td class="px-4 py-4 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                    <span class="line-clamp-1">{{ $row['formTitle'] }}</span>
                                </td>

                                {{-- Date & Sentiment --}}
                                <td class="px-4 py-4 text-sm text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <span title="{{ $row['submittedAtTitle'] }}">
                                            {{ $row['submittedAtHuman'] }}
                                        </span>
                                        @if ($row['sentiment'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium border {{ $row['sentiment']['class'] }}">
                                                {{ ucfirst($row['sentiment']['label']) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- IP --}}
                                <td
                                    class="hidden md:table-cell px-4 py-4 text-sm text-neutral-500 dark:text-neutral-500 font-mono">
                                    {{ $row['ipAddress'] }}
                                </td>

                                {{-- Preview --}}
                                <td class="px-4 py-4 max-w-xs">
                                    @if ($row['firstImageUrl'])
                                        <a href="{{ route('submissions.files.download', ['submission' => $row['id'], 'fieldId' => $row['firstImageFieldId']]) }}" download="{{ $row['firstImageName'] ?? 'image' }}"
                                            class="inline-block mb-2" title="Download image" @click.stop>
                                            <img src="{{ $row['firstImageUrl'] }}" alt="Submission image preview"
                                                class="w-12 h-12 rounded object-cover border border-neutral-200 dark:border-neutral-800" loading="lazy">
                                        </a>
                                    @endif
                                    @if (count($row['previewItems']))
                                        <div class="flex flex-col gap-0.5">
                                            @foreach ($row['previewItems'] as $item)
                                                <span class="text-xs text-neutral-500 dark:text-neutral-500 truncate">
                                                    <span
                                                        class="font-medium text-neutral-700 dark:text-neutral-300">{{ $item['label'] }}:</span>
                                                    {{ $item['value'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span
                                            class="text-xs text-neutral-400 dark:text-neutral-600 italic">Empty</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-4 text-right" @click.stop>
                                    <div class="inline-flex items-center gap-1.5">

                                        {{-- View --}}
                                        <button type="button" @click="openModal(@js($row['modalData']))"
                                            class="inline-flex items-center justify-center w-8 h-8 border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors duration-200"
                                            aria-label="View submission">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        {{-- Delete --}}
                                        <form method="POST"
                                            action="{{ $row['deleteUrl'] }}"
                                            @submit.prevent="
                                                if (confirm('Delete this submission? This cannot be undone.')) $el.submit();
                                            ">
                                            @csrf
                                            @method('DELETE')
                                            @if (request('sform'))
                                                <input type="hidden" name="sform" value="{{ request('sform') }}">
                                            @endif
                                            @if (request('ssearch'))
                                                <input type="hidden" name="ssearch"
                                                    value="{{ request('ssearch') }}">
                                            @endif
                                            <input type="hidden" name="spage"
                                                value="{{ $submissions->currentPage() }}">
                                            <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 hover:border-red-400 dark:hover:border-red-600 hover:text-red-500 dark:hover:text-red-400 transition-colors duration-200"
                                                aria-label="Delete submission">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($submissions->hasPages())
                <div
                    class="bg-white dark:bg-neutral-950 border border-t-0 border-neutral-200 dark:border-neutral-800 px-6 py-4 transition-colors duration-300">
                    {{ $submissions->links() }}
                </div>
            @endif

        @endif

        {{-- ════════════════════════════════════════════
             Modal – full submission detail
        ════════════════════════════════════════════ --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4 pb-8 overflow-y-auto"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="closeModal()"></div>

            {{-- Panel --}}
            <div class="relative bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 w-full max-w-2xl shadow-2xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95" @click.stop>

                {{-- Modal header --}}
                <div class="flex items-start justify-between p-6 border-b border-neutral-200 dark:border-neutral-800">
                    <div>
                        <p
                            class="text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1">
                            Submission Detail
                        </p>
                        <h4 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100"
                            x-text="selected.formTitle"></h4>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2">
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span x-text="selected.createdAt"></span>
                            </span>
                            <span x-show="selected.ipAddress"
                                class="text-xs text-neutral-500 dark:text-neutral-400 font-mono flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                                </svg>
                                <span x-text="selected.ipAddress"></span>
                            </span>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()"
                        class="shrink-0 ml-4 w-8 h-8 flex items-center justify-center border border-neutral-200 dark:border-neutral-700 text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 hover:border-neutral-900 dark:hover:border-neutral-100 transition-colors duration-200"
                        aria-label="Close">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal body – field values --}}
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <template x-if="selected.pages && selected.pages.length > 0">
                        <div class="space-y-6">
                            <template x-for="(page, pIdx) in selected.pages" :key="pIdx">
                                <div class="space-y-4">
                                    {{-- Page Header --}}
                                    <div class="border-b border-neutral-200 dark:border-neutral-800 pb-2">
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-800 dark:text-neutral-200" x-text="page.title"></h4>
                                        <template x-if="page.description">
                                            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-0.5" x-text="page.description"></p>
                                        </template>
                                    </div>
                                    
                                     {{-- Page Fields --}}
                                     <template x-if="page.fields && page.fields.length > 0">
                                         <div class="space-y-4 pl-2">
                                             <template x-for="field in page.fields" :key="field.id">
                                                 <div class="border-b border-neutral-100 dark:border-neutral-900 last:border-0 pb-4 last:pb-0">
                                                     <div class="flex items-center justify-between gap-4 mb-2">
                                                         <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider" x-text="field.label"></p>
                                                        <template x-if="selected.aiMetadata && selected.aiMetadata.sentiment && selected.aiMetadata.sentiment[field.id]">
                                                            <span :class="{
                                                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900/50': getSentimentLabel(selected.aiMetadata.sentiment[field.id]) === 'positive',
                                                                'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border-rose-200 dark:border-rose-900/50': getSentimentLabel(selected.aiMetadata.sentiment[field.id]) === 'negative',
                                                                'bg-neutral-50 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400 border-neutral-200 dark:border-neutral-800': getSentimentLabel(selected.aiMetadata.sentiment[field.id]) === 'neutral'
                                                            }" class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium border uppercase" x-text="getSentimentLabel(selected.aiMetadata.sentiment[field.id])"></span>
                                                        </template>
                                                    </div>
                                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                                        {{-- Array values (checkbox) --}}
                                                        <template x-if="Array.isArray(selected.content[field.id]) && selected.content[field.id].length > 0">
                                                            <div class="flex flex-wrap gap-1.5">
                                                                <template x-for="val in selected.content[field.id]" :key="val">
                                                                    <span class="inline-flex px-2.5 py-0.5 text-xs border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300" x-text="val"></span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        {{-- Textarea / long text --}}
                                                        <template x-if="!Array.isArray(selected.content[field.id]) && field.type === 'textarea' && selected.content[field.id]">
                                                            <p class="whitespace-pre-wrap leading-relaxed" x-text="selected.content[field.id]"></p>
                                                        </template>
                                                        {{-- Secure file download --}}
                                                        <template x-if="!Array.isArray(selected.content[field.id]) && (field.type === 'file' || field.type === 'image') && selected.content[field.id] && selected.fileImageFlags && selected.fileImageFlags[field.id]">
                                                            <a :href="selected.fileUrls && selected.fileUrls[field.id] ? selected.fileUrls[field.id] : ''" :download="selected.fileNames && selected.fileNames[field.id] ? selected.fileNames[field.id] : 'image'" class="inline-block" title="Download original image">
                                                                <img :src="selected.fileInlineImages && selected.fileInlineImages[field.id] ? selected.fileInlineImages[field.id] : (selected.fileUrls && selected.fileUrls[field.id] ? selected.fileUrls[field.id] : '')" alt="Uploaded image" class="w-28 h-28 object-cover rounded border border-neutral-200 dark:border-neutral-800" loading="lazy">
                                                            </a>
                                                        </template>
                                                        <template x-if="!Array.isArray(selected.content[field.id]) && (field.type === 'file' || field.type === 'image') && selected.content[field.id] && (!selected.fileImageFlags || !selected.fileImageFlags[field.id])">
                                                            <a :href="selected.fileUrls && selected.fileUrls[field.id] ? selected.fileUrls[field.id] : ''" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-neutral-900 dark:text-neutral-100 underline underline-offset-2 hover:opacity-80" x-show="selected.fileUrls && selected.fileUrls[field.id]">
                                                                Open file
                                                            </a>
                                                        </template>
                                                        {{-- Regular string --}}
                                                        <template x-if="!Array.isArray(selected.content[field.id]) && field.type !== 'textarea' && field.type !== 'file' && field.type !== 'image' && selected.content[field.id]">
                                                            <p x-text="selected.content[field.id]"></p>
                                                        </template>
                                                        {{-- Empty --}}
                                                        <template x-if="!selected.content[field.id] || (Array.isArray(selected.content[field.id]) && selected.content[field.id].length === 0)">
                                                            <p class="italic text-neutral-400 dark:text-neutral-600">No answer</p>
                                                        </template>

                                                        {{-- Detailed Sentiment Details --}}
                                                        <template x-if="selected.aiMetadata && selected.aiMetadata.sentiment && selected.aiMetadata.sentiment[field.id] && typeof selected.aiMetadata.sentiment[field.id] === 'object'">
                                                            <div class="mt-2.5 p-3 rounded bg-neutral-50/50 dark:bg-neutral-900/30 border border-neutral-100 dark:border-neutral-800/80 text-xs transition-all">
                                                                <div class="flex flex-wrap items-center gap-3 text-[11px] text-neutral-500 dark:text-neutral-400">
                                                                    <span class="font-medium text-neutral-700 dark:text-neutral-300">AI Sentiment Details:</span>
                                                                    
                                                                    <!-- Intensity / Confidence Score Meter -->
                                                                    <div class="flex items-center gap-1.5 bg-white dark:bg-neutral-950 px-2 py-0.5 rounded border border-neutral-200/60 dark:border-neutral-800">
                                                                        <span class="text-neutral-400">Intensity:</span>
                                                                        <span class="font-semibold text-neutral-800 dark:text-neutral-200" x-text="Math.round(selected.aiMetadata.sentiment[field.id].score * 100) + '%'"></span>
                                                                    </div>

                                                                    <!-- Identified Emotions -->
                                                                    <template x-if="selected.aiMetadata.sentiment[field.id].emotions && selected.aiMetadata.sentiment[field.id].emotions.length > 0">
                                                                        <div class="flex flex-wrap gap-1.5 items-center">
                                                                            <span class="text-neutral-400">Emotions:</span>
                                                                            <template x-for="emo in selected.aiMetadata.sentiment[field.id].emotions" :key="emo">
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100 dark:border-purple-900/30 capitalize" x-text="emo"></span>
                                                                            </template>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!page.fields || page.fields.length === 0">
                                        <p class="text-xs text-neutral-400 dark:text-neutral-600 italic pl-2">No fields on this page</p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!selected.pages || selected.pages.length === 0">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 italic text-center py-8">
                            No field data available.
                        </p>
                    </template>
                </div>

                {{-- Modal footer --}}
                <div
                    class="flex items-center justify-between p-6 border-t border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900">
                    <form method="POST" :action="selected.deleteUrl"
                        @submit.prevent="if(confirm('Delete this submission? This cannot be undone.')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-red-200 dark:border-red-900 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete submission
                        </button>
                    </form>
                    <button type="button" @click="closeModal()"
                        class="px-5 py-2 text-sm font-medium border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:border-neutral-900 dark:hover:border-neutral-100 hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-200">
                        Close
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const register = () => {
                Alpine.data('submissionsView', () => ({
                    modalOpen: false,
                    selected: {},

                    openModal(data) {
                        this.selected = data;
                        this.modalOpen = true;
                        document.body.classList.add('overflow-hidden');
                    },

                    closeModal() {
                        this.modalOpen = false;
                        document.body.classList.remove('overflow-hidden');
                    },

                    getSentimentLabel(sentiment) {
                        if (!sentiment) return '';
                        if (typeof sentiment === 'object') {
                            return sentiment.label || 'neutral';
                        }
                        return sentiment;
                    },
                }));
            };

            if (window.Alpine) {
                register();
            } else {
                document.addEventListener('alpine:init', register);
            }
        })();
    </script>
@endpush

@php
    $parseMarkdown = function($text) {
        $text = e($text);
        // Bold
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong class="font-semibold text-neutral-900 dark:text-neutral-50">$1</strong>', $text);
        // Lists
        $text = preg_replace('/^\* (.*?)(?=\n|$)/m', '<li class="ml-4 list-disc text-neutral-700 dark:text-neutral-300">$1</li>', $text);
        $text = preg_replace('/^- (.*?)(?=\n|$)/m', '<li class="ml-4 list-disc text-neutral-700 dark:text-neutral-300">$1</li>', $text);
        // Italic
        $text = preg_replace('/\*(.*?)\*/', '<em class="italic">$1</em>', $text);
        // Newlines
        $text = nl2br($text);
        return $text;
    };
@endphp

<div class="flex flex-col h-full min-h-0" 
     x-data="{ 
        init() {
            this.scrollToBottom();
            $wire.on('scroll-chat-to-bottom', () => {
                this.scrollToBottom();
            });
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatList;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
     }">
    
    {{-- Chat Header --}}
    <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-800 flex items-center justify-between">
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-widest text-neutral-500 dark:text-neutral-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    <path d="M18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                </svg>
                AI Form Builder
            </h3>
            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-1">Describe the form you want to build</p>
        </div>
        
        {{-- Clear history button --}}
        @if(count($messages) > 1)
            <button wire:click="clearChat" 
                    title="Reset Chat History"
                    class="p-1 rounded text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Chat Messages List --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4 flex flex-col min-h-0" x-ref="chatList" id="chat-messages-list">
        @foreach($messages as $msg)
            @if($msg['role'] === 'user')
                {{-- User Message --}}
                <div class="flex flex-col items-end self-end max-w-[85%]">
                    <div class="bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-950 px-4 py-2.5 rounded-2xl rounded-tr-none text-sm shadow-sm font-normal leading-relaxed break-words">
                        {{ $msg['content'] }}
                    </div>
                    <span class="text-[10px] text-neutral-400 dark:text-neutral-500 mt-1 mr-1 tabular-nums">{{ $msg['timestamp'] ?? '' }}</span>
                </div>
            @else
                {{-- AI Message --}}
                <div class="flex flex-col items-start self-start max-w-[85%]">
                    <div class="bg-neutral-50 dark:bg-neutral-900/60 border border-neutral-200/80 dark:border-neutral-800/80 text-neutral-850 dark:text-neutral-250 px-4 py-2.5 rounded-2xl rounded-tl-none text-sm shadow-sm font-normal leading-relaxed break-words">
                        {!! $parseMarkdown($msg['content']) !!}
                    </div>
                    <span class="text-[10px] text-neutral-400 dark:text-neutral-500 mt-1 ml-1 tabular-nums">{{ $msg['timestamp'] ?? '' }}</span>
                </div>
            @endif
        @endforeach

        {{-- AI Typing/Generating Placeholder --}}
        @if($isGenerating)
            <div class="flex flex-col items-start self-start max-w-[85%] animate-pulse">
                <div class="bg-neutral-50 dark:bg-neutral-900/60 border border-neutral-200/80 dark:border-neutral-800/80 px-4 py-3.5 rounded-2xl rounded-tl-none flex items-center gap-1.5 shadow-sm">
                    <div class="w-2.5 h-2.5 bg-neutral-400 dark:bg-neutral-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2.5 h-2.5 bg-neutral-400 dark:bg-neutral-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2.5 h-2.5 bg-neutral-400 dark:bg-neutral-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        @endif

        {{-- Error message --}}
        @if($error)
            <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/60 p-3 rounded-lg text-xs text-red-650 dark:text-red-300">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-red-500 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $error }}</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Chat Input Form --}}
    <div class="p-4 border-t border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-950/20">
        <form wire:submit.prevent="sendMessage" class="relative flex flex-col gap-2">
            <div class="relative">
                <textarea 
                    wire:model="prompt" 
                    placeholder="E.g., Create a customer feedback form..."
                    rows="3"
                    @disabled($isGenerating)
                    @keydown.enter.prevent="if($event.target.value.trim()) $el.closest('form').dispatchEvent(new Event('submit'))"
                    class="w-full pr-10 pl-3 py-2 text-sm bg-white dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-neutral-900 dark:focus:border-neutral-100 focus:ring-0 resize-none transition-colors duration-150 placeholder-neutral-400 disabled:opacity-50"
                ></textarea>
                
                <button type="submit" 
                        @disabled($isGenerating)
                        class="absolute right-2 bottom-3 p-1.5 rounded-full text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 disabled:opacity-30 disabled:hover:text-neutral-400 transition-colors duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
            
            <div class="flex justify-between items-center text-[10px] text-neutral-400 dark:text-neutral-500 px-1">
                <span>Press Enter to send</span>
                @if(mb_strlen($prompt) > 0)
                    <span class="tabular-nums">{{ mb_strlen($prompt) }} chars</span>
                @endif
            </div>
        </form>
    </div>
</div>

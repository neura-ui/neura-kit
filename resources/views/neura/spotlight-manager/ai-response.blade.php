{{-- Default AI Response View --}}
{{-- To customize: SpotlightConfig(aiView: 'your-view') --}}

{{-- Empty state --}}
<div x-show="!aiResponse && !isLoading && query.length === 0" class="py-12 text-center">
    <div class="mx-auto size-14 rounded-full bg-gradient-to-br from-primary-100 to-violet-100 dark:from-primary-900/30 dark:to-violet-900/30 flex items-center justify-center mb-4">
        <x-neura::icon name="sparkles" class="size-7 text-primary-500" />
    </div>
    <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ __('askAiAnything') }}</p>
    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{{ __('pressEnterToSend') }}</p>
</div>

{{-- Thinking --}}
<div x-show="isLoading && !aiResponse" class="p-6">
    <div class="flex items-start gap-3">
        <div class="shrink-0 size-8 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center">
            <x-neura::icon name="sparkles" variant="solid" class="size-4 text-white" />
        </div>
        <div class="flex-1 pt-1">
            <x-neura::spinner size="xs" variant="dots" color="primary" />
            <p class="text-xs text-neutral-500 mt-2">{{ __('aiThinking') }}</p>
        </div>
    </div>
</div>

{{-- Response --}}
<div x-show="aiResponse" x-cloak class="p-6">
    <div class="flex items-start gap-3">
        <div class="shrink-0 size-8 rounded-full bg-gradient-to-br from-primary-500 to-violet-500 flex items-center justify-center">
            <x-neura::icon name="sparkles" variant="solid" class="size-4 text-white" />
        </div>
        <div class="flex-1 min-w-0">
            @php
                $proseClasses = 'prose prose-sm dark:prose-invert max-w-none 
                    text-neutral-800 dark:text-neutral-200
                    prose-p:text-neutral-800 dark:prose-p:text-neutral-200 prose-p:leading-relaxed
                    prose-headings:text-neutral-900 dark:prose-headings:text-neutral-100 prose-headings:font-semibold
                    prose-strong:text-neutral-900 dark:prose-strong:text-neutral-100 prose-strong:font-semibold
                    prose-code:text-primary-700 dark:prose-code:text-primary-300 prose-code:bg-primary-50 dark:prose-code:bg-primary-950/50 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:font-mono prose-code:text-xs
                    prose-pre:bg-neutral-100 dark:prose-pre:bg-neutral-950 prose-pre:text-neutral-900 dark:prose-pre:text-neutral-100 prose-pre:border prose-pre:border-neutral-200 dark:prose-pre:border-neutral-800 prose-pre:shadow-sm
                    prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:no-underline hover:prose-a:underline
                    prose-ul:text-neutral-800 dark:prose-ul:text-neutral-200
                    prose-ol:text-neutral-800 dark:prose-ol:text-neutral-200
                    prose-li:text-neutral-800 dark:prose-li:text-neutral-200 prose-li:marker:text-neutral-500 dark:prose-li:marker:text-neutral-400
                    prose-blockquote:border-primary-300 dark:prose-blockquote:border-primary-700 prose-blockquote:text-neutral-700 dark:prose-blockquote:text-neutral-300';
            @endphp

            {{-- Real-time streaming (visible during loading, for async providers like OpenAI) --}}
            <div x-show="isLoading"
                wire:stream="spotlightAiStream"
                class="{{ $proseClasses }} whitespace-pre-wrap"
            ></div>

            {{-- Formatted result (visible after loading completes) --}}
            <div x-show="!isLoading"
                x-html="formatAiResponse(aiResponse)"
                class="{{ $proseClasses }}"
            ></div>

            {{-- Streaming cursor --}}
            <span x-show="isLoading" class="inline-block w-2 h-4 bg-primary-500 dark:bg-primary-400 animate-pulse ml-0.5 rounded-sm"></span>
        </div>
    </div>
</div>

<div
    x-data="neuraSpotlight({
        isOpen: @entangle('isOpen'),
        mode: @entangle('mode'),
        query: @entangle('query'),
        selectedIndex: @entangle('selectedIndex'),
        isLoading: @entangle('isLoading'),
    })"
    x-show="isOpen"
    x-on:keydown.escape.window="close()"
    x-on:keydown.cmd.k.window.prevent="toggle()"
    x-on:keydown.ctrl.k.window.prevent="toggle()"
    x-on:keydown.cmd.p.window.prevent="toggle({ mode: 'command' })"
    x-on:keydown.ctrl.p.window.prevent="toggle({ mode: 'command' })"
    x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    wire:ignore.self
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="close()"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Spotlight Panel --}}
    <div class="fixed inset-0 flex items-start justify-center pt-[15vh] px-4">
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            x-on:click.away="close()"
            x-trap.inert.noscroll="isOpen"
            class="relative w-full max-w-2xl bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl border border-neutral-200 dark:border-neutral-700 overflow-hidden"
        >
            {{-- Header / Search Input --}}
            <div class="flex items-center gap-3 px-4 border-b border-neutral-200 dark:border-neutral-700">
                {{-- Mode Icon --}}
                <div class="shrink-0 text-neutral-400">
                    <template x-if="mode === 'search'">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </template>
                    <template x-if="mode === 'command'">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </template>
                    <template x-if="mode === 'ai'">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                        </svg>
                    </template>
                </div>

                {{-- Input --}}
                <input
                    x-ref="input"
                    x-model="query"
                    x-on:keydown.down.prevent="moveDown()"
                    x-on:keydown.up.prevent="moveUp()"
                    x-on:keydown.enter.prevent="executeSelected()"
                    x-on:keydown.tab.prevent="nextMode()"
                    type="text"
                    class="flex-1 py-4 bg-transparent text-neutral-900 dark:text-white placeholder-neutral-400 outline-none text-lg"
                    :placeholder="getPlaceholder()"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                >

                {{-- Loading Spinner --}}
                <div x-show="isLoading" class="shrink-0">
                    <svg class="size-5 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                {{-- Mode Tabs --}}
                <div class="hidden sm:flex items-center gap-1 shrink-0">
                    <button
                        x-on:click="setMode('search')"
                        :class="mode === 'search' ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors"
                    >
                        Search
                    </button>
                    <button
                        x-on:click="setMode('command')"
                        :class="mode === 'command' ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors"
                    >
                        Commands
                    </button>
                    <button
                        x-on:click="setMode('ai')"
                        :class="mode === 'ai' ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-white' : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                        class="px-2 py-1 text-xs font-medium rounded-md transition-colors"
                    >
                        AI
                    </button>
                </div>

                {{-- Close Button --}}
                <button
                    x-on:click="close()"
                    class="shrink-0 p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 rounded-md transition-colors"
                >
                    <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium text-neutral-500 bg-neutral-100 dark:bg-neutral-800 rounded">ESC</kbd>
                    <svg class="sm:hidden size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Results --}}
            <div
                x-show="mode !== 'ai' && (results.length > 0 || query.length > 0)"
                class="max-h-[50vh] overflow-y-auto overscroll-contain"
            >
                {{-- No Results --}}
                <div
                    x-show="query.length > 0 && results.length === 0 && !isLoading"
                    class="py-12 text-center text-neutral-500"
                >
                    <svg class="size-12 mx-auto mb-3 text-neutral-300 dark:text-neutral-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p class="text-sm">No results found for "<span x-text="query" class="font-medium"></span>"</p>
                </div>

                {{-- Results List --}}
                <ul x-ref="resultsList" class="py-2" role="listbox">
                    <template x-for="(result, index) in results" :key="result.id">
                        <li
                            x-on:click="handleResult(result)"
                            x-on:mouseenter="selectResult(index)"
                            :class="selectedIndex === index ? 'bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-neutral-50 dark:hover:bg-neutral-800/50'"
                            class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors"
                            role="option"
                            :aria-selected="selectedIndex === index"
                        >
                            {{-- Icon --}}
                            <div
                                :class="selectedIndex === index ? 'text-primary-500' : 'text-neutral-400'"
                                class="shrink-0 size-8 flex items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800"
                            >
                                <template x-if="result.icon">
                                    <span x-html="getIconHtml(result.icon)"></span>
                                </template>
                                <template x-if="!result.icon">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </template>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div
                                    x-text="result.title"
                                    :class="selectedIndex === index ? 'text-primary-700 dark:text-primary-300' : 'text-neutral-900 dark:text-white'"
                                    class="text-sm font-medium truncate"
                                ></div>
                                <div
                                    x-show="result.description"
                                    x-text="result.description"
                                    class="text-xs text-neutral-500 truncate"
                                ></div>
                            </div>

                            {{-- Action Hint --}}
                            <div x-show="selectedIndex === index" class="shrink-0 flex items-center gap-1">
                                <kbd class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium text-neutral-500 bg-neutral-100 dark:bg-neutral-700 rounded">↵</kbd>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- AI Response Area --}}
            <div
                x-show="mode === 'ai'"
                class="max-h-[50vh] overflow-y-auto overscroll-contain"
            >
                {{-- AI Prompt Hint --}}
                <div
                    x-show="!aiResponse && !isLoading && query.length === 0"
                    class="py-12 text-center"
                >
                    <svg class="size-12 mx-auto mb-3 text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                    </svg>
                    <p class="text-sm text-neutral-500">Ask anything and press Enter</p>
                </div>

                {{-- AI Response --}}
                <div
                    x-show="aiResponse || isLoading"
                    class="p-4"
                >
                    <div
                        x-html="formatAiResponse(aiResponse)"
                        class="prose prose-sm dark:prose-invert max-w-none"
                    ></div>
                    
                    {{-- Streaming Cursor --}}
                    <span
                        x-show="isLoading"
                        class="inline-block w-2 h-4 bg-primary-500 animate-pulse ml-0.5"
                    ></span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-4 py-2 border-t border-neutral-200 dark:border-neutral-700 text-xs text-neutral-500">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 bg-neutral-100 dark:bg-neutral-800 rounded text-[10px]">↑↓</kbd>
                        Navigate
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 bg-neutral-100 dark:bg-neutral-800 rounded text-[10px]">↵</kbd>
                        Select
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 bg-neutral-100 dark:bg-neutral-800 rounded text-[10px]">Tab</kbd>
                        Switch mode
                    </span>
                </div>
                <div class="text-neutral-400">
                    NeuraKit Spotlight
                </div>
            </div>
        </div>
    </div>
</div>

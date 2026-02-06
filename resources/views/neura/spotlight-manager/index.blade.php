{{-- Spotlight Component - Single Source of Truth Architecture --}}
<div
    wire:key="neura-spotlight-manager"
    wire:ignore.self
    x-data="neuraSpotlight({
        isOpen: @entangle('isOpen').live,
        mode: @entangle('mode').live,
        query: @entangle('query').live,
        selectedIndex: @entangle('selectedIndex').live,
        isLoading: @entangle('isLoading').live,
        results: @entangle('results').live,
        aiResponse: @entangle('aiResponse').live,
        config: @js($this->configData),
    })"
    x-on:keydown.escape.window="if (isOpen) $wire.close()"
    x-on:keydown.cmd.k.window.prevent="if (isModeAvailable('search')) $wire.toggle({ mode: 'search' })"
    x-on:keydown.ctrl.k.window.prevent="if (isModeAvailable('search')) $wire.toggle({ mode: 'search' })"
    x-on:keydown.cmd.p.window.prevent="if (isModeAvailable('command')) $wire.toggle({ mode: 'command' })"
    x-on:keydown.ctrl.p.window.prevent="if (isModeAvailable('command')) $wire.toggle({ mode: 'command' })"
    x-on:keydown.cmd.i.window.prevent="if (isModeAvailable('ai')) $wire.toggle({ mode: 'ai' })"
    x-on:keydown.ctrl.i.window.prevent="if (isModeAvailable('ai')) $wire.toggle({ mode: 'ai' })"
    class="spotlight-container antialiased"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.close()"
        class="fixed inset-0 z-[9998] bg-neutral-900/70 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-8"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-8"
        x-trap.inert.noscroll="isOpen"
        class="fixed inset-x-0 top-[10vh] sm:top-[15vh] z-[9999] flex justify-center px-4"
        role="dialog"
        aria-modal="true"
        :aria-label="mode === 'ai' ? '{{ __('askAi') }}' : '{{ __('search') }}'"
    >
        <div
            @click.stop
            class="w-full max-w-2xl bg-white dark:bg-neutral-900 rounded-xl shadow-2xl shadow-neutral-900/20 dark:shadow-black/50 ring-1 ring-neutral-200 dark:ring-neutral-800 overflow-hidden"
        >
            {{-- Header --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-neutral-100 dark:border-neutral-800">
                {{-- Mode Icon --}}
                <div class="shrink-0 text-neutral-400 dark:text-neutral-500">
                    <template x-if="isModeAvailable('search') && mode === 'search'">
                        <div 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                        >
                            <x-neura::icon name="magnifying-glass" class="size-5" />
                        </div>
                    </template>
                    <template x-if="isModeAvailable('command') && mode === 'command'">
                        <div 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                        >
                            <x-neura::icon name="command-line" class="size-5" />
                        </div>
                    </template>
                    <template x-if="isModeAvailable('ai') && mode === 'ai'">
                        <div 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                        >
                            <x-neura::icon name="sparkles" class="size-5" />
                        </div>
                    </template>
                </div>

                {{-- Input --}}
                <input
                    x-ref="input"
                    x-model="query"
                    @keydown.down.prevent="moveDown()"
                    @keydown.up.prevent="moveUp()"
                    @keydown.enter.prevent="executeSelected()"
                    @keydown.tab.prevent="$wire.nextMode()"
                    type="text"
                    class="flex-1 py-1 bg-transparent text-neutral-900 dark:text-neutral-100 placeholder:text-neutral-400 dark:placeholder:text-neutral-500 outline-none text-sm"
                    :placeholder="getPlaceholder()"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                >

                {{-- Loading --}}
                <div x-show="isLoading" x-transition class="shrink-0">
                    <x-neura::spinner size="sm" color="primary" />
                </div>

                {{-- Mode Tabs (Desktop) - Only show if more than 1 mode available --}}
                <template x-if="getAvailableModes().length > 1">
                    <div class="hidden sm:flex items-center gap-1 shrink-0 p-1 bg-neutral-100 dark:bg-neutral-800 rounded-lg">
                        <template x-for="m in getAvailableModes()" :key="m.value">
                            <button
                                type="button"
                                @click.stop="$wire.setMode(m.value)"
                                :class="mode === m.value 
                                    ? 'bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm' 
                                    : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                                class="px-2.5 py-1.5 text-xs font-medium rounded-md transition-all duration-200 flex items-center gap-1"
                                :title="m.label + ' (' + m.shortcut + ')'"
                            >
                                <template x-if="m.value === 'ai'">
                                    <span x-html="getIconSvg('sparkles')" class="size-3"></span>
                                </template>
                                <span x-text="m.label"></span>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Close --}}
                <button
                    type="button"
                    @click.stop="$wire.close()"
                    class="shrink-0 p-1.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-lg transition-colors"
                    aria-label="{{ __('close') }}"
                >
                    <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800 rounded">ESC</kbd>
                    <x-neura::icon name="x-mark" class="sm:hidden size-5" />
                </button>
            </div>

            {{-- Results (Search & Command modes) --}}
            <div 
                x-show="mode !== 'ai'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="max-h-[50vh] overflow-y-auto overscroll-contain"
            >
                {{-- Empty State --}}
                <div
                    x-show="query.length > 0 && results.length === 0 && !isLoading"
                    x-transition:enter="transition ease-out duration-300 delay-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="py-12 text-center"
                >
                    <div class="mx-auto size-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-3">
                        <x-neura::icon name="magnifying-glass" class="size-5 text-neutral-400" />
                    </div>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('noResultsFor') }} "<span x-text="query" class="font-medium text-neutral-900 dark:text-neutral-100"></span>"
                    </p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">
                        {{ __('tryDifferentSearch') }}
                    </p>
                </div>

                {{-- Initial State (Command mode) --}}
                <div
                    x-show="mode === 'command' && query.length === 0 && results.length === 0 && !isLoading"
                    x-transition:enter="transition ease-out duration-300 delay-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="py-8 text-center"
                >
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('typeToSearchCommands') }}</p>
                </div>

                {{-- Results List --}}
                <ul 
                    x-ref="resultsList" 
                    x-show="results.length > 0"
                    x-transition:enter="transition ease-out duration-300 delay-100"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="py-2" 
                    role="listbox"
                >
                    <template x-for="(result, index) in results" :key="result.id">
                        <li
                            @click.stop="$wire.handleResult(result)"
                            @mouseenter="selectResult(index)"
                            :class="selectedIndex === index 
                                ? 'bg-primary-50 dark:bg-primary-500/10' 
                                : 'hover:bg-neutral-50 dark:hover:bg-neutral-800/50'"
                            class="flex items-center gap-3 mx-2 px-3 py-2.5 rounded-lg cursor-pointer transition-colors group"
                            role="option"
                            :aria-selected="selectedIndex === index"
                        >
                            {{-- Icon --}}
                            <div
                                :class="selectedIndex === index 
                                    ? 'bg-primary-100 dark:bg-primary-500/20 text-primary-600 dark:text-primary-400' 
                                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 group-hover:bg-neutral-200 dark:group-hover:bg-neutral-700'"
                                class="shrink-0 size-9 flex items-center justify-center rounded-lg transition-colors"
                            >
                                <template x-if="result.icon">
                                    <span x-html="getIconSvg(result.icon)" class="size-4"></span>
                                </template>
                                <template x-if="!result.icon">
                                    <x-neura::icon name="arrow-right" class="size-4" />
                                </template>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        x-text="result.title"
                                        :class="selectedIndex === index 
                                            ? 'text-primary-700 dark:text-primary-300' 
                                            : 'text-neutral-900 dark:text-neutral-100'"
                                        class="text-sm font-medium truncate"
                                    ></span>
                                    <span
                                        x-show="result.badge"
                                        x-text="result.badge"
                                        class="shrink-0 px-1.5 py-0.5 text-[10px] font-medium rounded bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300"
                                    ></span>
                                </div>
                                <p
                                    x-show="result.description"
                                    x-text="result.description"
                                    class="text-xs text-neutral-500 dark:text-neutral-400 truncate mt-0.5"
                                ></p>
                            </div>

                            {{-- Shortcut & Enter --}}
                            <div class="shrink-0 flex items-center gap-2">
                                <kbd 
                                    x-show="result.shortcut"
                                    x-text="result.shortcut"
                                    class="hidden sm:inline-flex px-1.5 py-0.5 text-[10px] font-medium text-neutral-400 dark:text-neutral-500 bg-neutral-100 dark:bg-neutral-800 rounded"
                                ></kbd>
                                <kbd 
                                    x-show="selectedIndex === index" 
                                    x-transition
                                    class="inline-flex px-1.5 py-0.5 text-[10px] font-medium text-primary-600 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 rounded"
                                >↵</kbd>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- AI Response Area --}}
            <div 
                x-show="isModeAvailable('ai') && mode === 'ai'"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="max-h-[50vh] overflow-y-auto overscroll-contain"
            >
                @if($this->aiViewName())
                    {{-- Custom AI View --}}
                    @include($this->aiViewName(), [
                        'aiResponse' => $aiResponse,
                        'isLoading' => $isLoading,
                        'query' => $query,
                    ])
                @else
                    {{-- Default AI View --}}
                    @include('neura-kit::neura.spotlight-manager.ai-response', [
                        'aiResponse' => $aiResponse,
                        'isLoading' => $isLoading,
                        'query' => $query,
                    ])
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-4 py-2 border-t border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/50">
                <div class="flex items-center gap-4 text-[11px] text-neutral-500 dark:text-neutral-400">
                    <span class="hidden sm:flex items-center gap-1.5">
                        <kbd class="inline-flex items-center justify-center size-5 bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded text-[10px] font-medium shadow-sm">↑</kbd>
                        <kbd class="inline-flex items-center justify-center size-5 bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded text-[10px] font-medium shadow-sm">↓</kbd>
                        <span class="ml-0.5">{{ __('navigate') }}</span>
                    </span>
                    <span class="hidden sm:flex items-center gap-1.5">
                        <kbd class="inline-flex items-center justify-center px-1.5 h-5 bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded text-[10px] font-medium shadow-sm">↵</kbd>
                        <span class="ml-0.5">{{ __('select') }}</span>
                    </span>
                    <template x-if="getAvailableModes().length > 1">
                        <span class="hidden sm:flex items-center gap-1.5">
                            <kbd class="inline-flex items-center justify-center px-1.5 h-5 bg-white dark:bg-neutral-700 border border-neutral-200 dark:border-neutral-600 rounded text-[10px] font-medium shadow-sm">Tab</kbd>
                            <span class="ml-0.5">{{ __('switchMode') }}</span>
                        </span>
                    </template>
                </div>
                
                {{-- Mobile Mode Switcher - Only show if more than 1 mode available --}}
                <template x-if="getAvailableModes().length > 1">
                    <div class="flex sm:hidden items-center gap-1">
                        <template x-for="m in getAvailableModes()" :key="m.value">
                            <button
                                type="button"
                                @click.stop="$wire.setMode(m.value)"
                                :class="mode === m.value ? 'bg-neutral-200 dark:bg-neutral-700' : ''"
                                class="p-2 rounded-lg transition-colors"
                            >
                                <span x-html="getIconSvg(m.icon)" class="size-4 text-neutral-600 dark:text-neutral-300"></span>
                            </button>
                        </template>
                    </div>
                </template>

                <div class="hidden sm:block text-[11px] text-neutral-400 dark:text-neutral-500 font-medium">
                    NeuraKit Spotlight
                </div>
            </div>
        </div>
    </div>
</div>

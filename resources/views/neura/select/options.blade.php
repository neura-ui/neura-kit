@aware([
    'searchable' => false,
    'multiple' => false,
    'searchPlaceholder' => neura_trans('search'),
])

@props([
    'searchPlaceholder' => neura_trans('search'),
    'panels' => null,
])

<neura::popup
    x-show="open"
    class="min-w-0! w-[var(--select-popup-w)]!"
    x-on:click.away="close()"
    x-on:keydown.escape.stop="activePanel ? backPanel() : close()"
    x-anchor.offset.3="$refs.selectAnchor"
>
    <div
        x-show="activePanel === null"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        data-slot="select-list"
        class="min-w-0"
    >
        @if ($searchable)
            <div class="px-2 mb-1 pb-2 border-b border-separator">
                <div class="relative [&_input[data-slot=control]]:pr-8">
                    <neura::input
                        x-model="search"
                        x-on:input.stop="isTyping = true"
                        x-on:keydown.down.prevent.stop="handleKeydown($event)"
                        x-on:keydown.up.prevent.stop="handleKeydown($event)"
                        x-on:keydown.enter.prevent.stop="handleKeydown($event)"
                        x-on:keydown.escape.stop="hasSearchValue ? clearSearch() : close()"
                        x-bind:aria-activedescendant="activeIndex !== null ? 'option-' + activeIndex : null"
                        x-ref='searchControl'
                        x-bind:placeholder="searchPlaceholder"
                        leftIcon="magnifying-glass"
                        class="border-0 shadow-none bg-transparent focus:ring-0 focus:border-0"
                        bindScopeToParent="true"
                    />
                    <button
                        type="button"
                        x-on:click.stop="clearSearch()"
                        x-show="hasSearchValue"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-75"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors size-5 z-20 pointer-events-auto"
                        x-bind:aria-label="window.t('clearSearch')"
                    >
                        <neura::icon
                            name="x-mark"
                            class="size-4"
                        />
                    </button>
                </div>
            </div>
        @endif

        <ul
            role="listbox"
            @if ($multiple) aria-multiselectable="true" @endif
            x-on:keydown.enter="if ($event.target.dataset?.value !== undefined) { $event.preventDefault(); $event.stopPropagation(); select($event.target.dataset.value); }"
            x-on:keydown.up.prevent.stop="focusOption(-1, $event)"
            x-on:keydown.down.prevent.stop="focusOption(1, $event)"
            class="grid grid-cols-[1fr_auto] gap-y-0.5 gap-x-1.5 overflow-y-auto max-h-60 overscroll-contain scroll-py-1"
        >
            {{ $slot }}
        </ul>

        <template x-if="isSearchable && isTyping && !hasFilteredResults">
            <div class="h-16 flex flex-col items-center justify-center gap-1 text-fg-muted">
                <neura::icon name="magnifying-glass" class="size-4 opacity-60" />
                <neura::text class="text-sm" x-text="window.t('noResultsFound')"></neura::text>
            </div>
        </template>
    </div>

    @if (filled($panels))
        {{ $panels }}
    @endif
</neura::popup>

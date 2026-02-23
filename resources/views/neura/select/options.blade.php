@aware([
    'searchable' => false,
    'searchPlaceholder' => neura_trans('search'),
])

@props([
    'searchPlaceholder' => neura_trans('search'),
])

<neura::popup
    x-show="open"
    class="w-full!"
    x-on:click.away="close()"
    x-on:keydown.escape="close()"
    x-anchor.offset.3="$refs.selectTrigger"
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
                    x-on:keydown.escape.stop="clearSearch()"
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
        x-on:keydown.enter.prevent.stop="select($focus.focused().dataset.value)"
        x-on:keydown.up.prevent.stop="$focus.wrap().prev()"
        x-on:keydown.down.prevent.stop="$focus.wrap().next()"
        class="grid grid-cols-[auto_auto_1fr] gap-y-1 gap-x-2 overflow-y-auto max-h-60"
    >
        {{ $slot }}
    </ul>
    <template x-if="isSearchable && isTyping && !hasFilteredResults">
        <neura::text class="h-14 flex items-center justify-center" x-text="window.t('noResultsFound')">
        </neura::text>
    </template>
</neura::popup>

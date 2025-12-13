<div>
    {{ $slot }}
</div>

<neura::popup
    x-show="open"
    class="w-full!"
    x-on:keydown.escape="open = false"
    x-anchor.offset.3="$refs.autocompleteInput"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
>
    <template x-if="filteredOptions.length > 0">
        <ul
            role="listbox"
            class="grid gap-y-0.5 overflow-y-auto max-h-64 scrollbar-thin scrollbar-thumb-neutral-300 dark:scrollbar-thumb-neutral-700 scrollbar-track-transparent"
        >
            <template x-for="(option, index) in filteredOptions" :key="index">
                <li
                    :data-option-index="index"
                    :data-value="option.value"
                    :data-label="option.label"
                    x-on:click="select(option)"
                    x-on:mouseenter="activeIndex = index"
                    :class="{
                        'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300': isFocused(index),
                        'bg-transparent hover:bg-neutral-50 dark:hover:bg-neutral-800/50': !isFocused(index)
                    }"
                    class="px-3.5 py-2.5 rounded-lg cursor-pointer transition-all duration-150 text-sm font-medium text-neutral-700 dark:text-neutral-200 group"
                    role="option"
                    :aria-selected="isFocused(index)"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span x-text="option.label" class="truncate flex-1"></span>
                        <neura::icon x-show="selectedValue === option.value" name="check" class="size-4 text-primary-600 dark:text-primary-400 shrink-0" />
                    </div>
                </li>
            </template>
        </ul>
    </template>

    <template x-if="open && search.length >= minChars && filteredOptions.length === 0">
        <div class="px-4 py-8 text-center">
            <svg class="mx-auto size-12 text-neutral-300 dark:text-neutral-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1" x-text="window.t('noResultsFound')">
            </p>
            <p class="text-xs text-neutral-500 dark:text-neutral-500" x-text="window.t('tryAdjustingSearchTerms')">
            </p>
        </div>
    </template>
</neura::popup>
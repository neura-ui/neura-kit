@aware([
    'icon' => '',
    'iconAfter' => 'chevron-up-down',
    'disabled' => false,
    'clearable' => false,
    'searchable' => false,
    'triggerClass' => '',
    'invalid' => false,
    'trigger' => null,
    'placeholder' => null,
    'size' => 'md',
    'multiple' => false,
])
@php
    use Neura\Kit\Support\PackResolver;
    $selectColors = PackResolver::inputColor('select');
    $roundedClass = PackResolver::rounded(neura_config('select', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('select', 'shadow'));
    $sizeClasses = PackResolver::inputSize($size ?? 'md');
    $ssrLabel = $placeholder ?? ucfirst(neura_trans('select'));
@endphp
<div
    x-ref="selectTrigger"
    data-slot="trigger"
    role="combobox"
    {{ $attributes->merge(['class' => 'relative grid place-items-center grid-cols-[40px_1fr_26px_35px] [&>[data-slot=icon]+[data-slot=select-control]]:pl-10 [&:has([data-slot=select-control]+[data-slot=icon])>[data-slot=select-control]]:pr-7 [&:has([data-slot=select-control]+[data-slot=icon]+[data-slot=select-clear])>[data-slot=select-control]]:pr-14' . ($disabled ? ' [&_[data-slot=icon]]:opacity-40 [&_[data-slot=icon]]:cursor-auto' : '')]) }}
>
    @if (filled($icon))
        <neura::icon
            :name="$icon"
            class="col-span-1 col-start-1 row-start-1 h-full w-full text-fg-muted flex items-center justify-center z-10 size-[1.10rem]!"
        />
    @endif

    <button
        x-on:click="toggle()"
        x-on:keydown.down.prevent.stop="open ? handleKeydown($event) : toggle()"
        x-on:keydown.up.prevent.stop="open ? handleKeydown($event) : toggle()"
        x-on:keydown.enter.prevent.stop="open ? (activeIndex !== null ? handleKeydown($event) : close()) : toggle()"
        x-bind:aria-expanded="open"
        x-bind:data-open="open ? true : null"
        type="button"
        aria-haspopup="listbox"
        data-slot="select-control"
        {{ $attributes->merge(['class' => 'border bg-surface truncate text-fg disabled:text-fg-muted disabled:bg-neutral-50 dark:disabled:bg-neutral-900/60 disabled:shadow-none text-start transition-colors duration-fast ease-standard col-span-4 col-start-1 row-start-1 justify-self-stretch disabled:opacity-50 disabled:cursor-not-allowed flex cursor-pointer overflow-hidden whitespace-nowrap focus:ring-offset-0 focus:outline-none ' . $sizeClasses . ' ' . $shadowClass . ' ' . $roundedClass . (!$invalid ? ' ' . $selectColors['border'] . ' ' . $selectColors['focus'] : ' ' . $selectColors['invalid']) . ' ' . $triggerClass]) }}

        x-bind:aria-activedescendant="!isSearchable && activeIndex !== null ? 'option-' + activeIndex : null"
        @disabled($disabled)
    >
        @if ($multiple)
            <span class="nk-select-faces">
                <span
                    class="nk-select-value text-fg-muted"
                    x-show="chipCount === 0"
                    x-bind:class="{ 'text-fg': hasSelection, 'text-fg-muted': !hasSelection }"
                >
                    <span class="sr-only" x-text="placeholder">{{ $ssrLabel }}</span>
                    <span class="nk-select-reel" aria-hidden="true">
                        <span class="nk-select-chip is-current">{{ $ssrLabel }}</span>
                    </span>
                </span>
                {{-- Built by the component: a Livewire morph must not put the server's empty list back. --}}
                <span class="nk-multi" x-ref="multiChips" x-show="chipCount > 0" aria-hidden="true" wire:ignore></span>
                <span class="sr-only" x-show="chipCount > 0" x-text="label"></span>
            </span>
        @else
            {{-- The object form removes the static muted colour once a value is chosen; a string would only add text-fg next to it. --}}
            <span
                class="nk-select-value truncate block w-full text-fg-muted"
                x-bind:class="{ 'text-fg': hasSelection, 'text-fg-muted': !hasSelection }"
            >
                <span class="sr-only" x-text="label">{{ $ssrLabel }}</span>
                {{-- The component writes the chosen label here: a Livewire morph must not put the server's placeholder back. --}}
                <span class="nk-select-reel" x-ref="valueReel" aria-hidden="true" wire:ignore>
                    <span class="nk-select-chip is-current">{{ $ssrLabel }}</span>
                </span>
            </span>
        @endif
    </button>

    @if (filled($iconAfter))
        <neura::icon
            :name="$iconAfter"
            class="col-span-1 row-start-1 text-fg-muted [&:has(+[data-slot=select-clear])]:col-start-3 [&:not(:has(+[data-slot=select-clear]))]:col-start-4 size-[1.15rem]!"
        />
    @endif

    @if ($clearable)
        <neura::icon
            name="trash"
            data-slot="select-clear"
            x-on:click="clear"
            class="col-span-1 row-start-1 text-fg-muted hover:text-neutral-700 dark:hover:text-neutral-300 size-[1.15rem]! col-start-4 cursor-pointer transition-colors"
            x-bind:class="!hasSelection && 'opacity-50'"
        />
    @endif
</div>

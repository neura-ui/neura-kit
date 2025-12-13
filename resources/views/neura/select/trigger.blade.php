@aware([
    'icon' => '',
    'iconAfter' => 'chevron-up-down',
    'disabled' => false,
    'clearable' => false,
    'searchable' => false,
    'triggerClass' => '',
    'invalid' => false,
    'trigger' => null,
])
@php
    use Neura\Kit\Support\PackResolver;
    $selectColors = PackResolver::inputColor('select');
@endphp
<div
    x-ref="selectTrigger"
    data-slot="trigger"
    role="combobox"
    {{ $attributes->class([
        'relative grid place-items-center grid-cols-[40px_1fr_26px_35px]',

        '[&>[data-slot=icon]+[data-slot=select-control]]:pl-10',

        '[&:has([data-slot=select-control]+[data-slot=icon])>[data-slot=select-control]]:pr-7',

        '[&:has([data-slot=select-control]+[data-slot=icon]+[data-slot=select-clear])>[data-slot=select-control]]:pr-14',

        '[&_[data-slot=icon]]:opacity-40 [&_[data-slot=icon]]:cursor-auto' => $disabled,
    ]) }}
>
    @if (filled($icon))
        <neura::icon
            :name="$icon"
            class="col-span-1 col-start-1 row-start-1 h-full w-full text-neutral-500 dark:text-neutral-400 flex items-center justify-center z-10 size-[1.10rem]!"
        />
    @endif

    <button
        x-on:click="toggle()"
        x-bind:aria-expanded="open"
        type="button"
        aria-haspopup="listbox"
        data-slot="select-control"
        {{ $attributes->class([
            'border bg-white truncate text-sm text-neutral-900 disabled:text-neutral-500 dark:text-neutral-100 dark:disabled:text-neutral-500',
            'dark:bg-neutral-950 disabled:bg-neutral-50 dark:disabled:bg-neutral-900',
            'shadow-sm disabled:shadow-none rounded-lg px-3 py-2 text-start',
            'transition-colors duration-150',
            'col-span-4 col-start-1 row-start-1 justify-self-stretch',
            'disabled:opacity-50 disabled:cursor-not-allowed flex cursor-pointer',
            'overflow-hidden whitespace-nowrap',
            'focus:ring-offset-0 focus:outline-none',
            $selectColors['border'] => !$invalid,
            $selectColors['focus'] => !$invalid,
            $selectColors['invalid'] => $invalid,
            $triggerClass,
        ]) }}

        x-bind:aria-activedescendant="!isSearchable && activeIndex !== null ? 'option-' + activeIndex : null"
        @disabled($disabled)
    >
        <span class="truncate block w-full">
            <span x-text="label"></span>
        </span>
    </button>

    @if (filled($iconAfter))
        <neura::icon
            :name="$iconAfter"
            class="col-span-1 row-start-1 text-neutral-500 dark:text-neutral-400 [&:has(+[data-slot=select-clear])]:col-start-3 [&:not(:has(+[data-slot=select-clear]))]:col-start-4 size-[1.15rem]!"
        />
    @endif

    @if ($clearable)
        <neura::icon
            name="trash"
            data-slot="select-clear"
            x-on:click="clear"
            class="col-span-1 row-start-1 text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 size-[1.15rem]! col-start-4 cursor-pointer transition-colors"
            x-bind:class="!hasSelection && 'opacity-50'"
        />
    @endif
</div>

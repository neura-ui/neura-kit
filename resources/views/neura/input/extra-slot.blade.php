<div
    {{ $attributes->class([
        'flex items-center px-3',
        'bg-white dark:bg-neutral-950',
        'border border-primary-200 dark:border-primary-700',
        'text-neutral-600 dark:text-neutral-400',
        'shadow-sm',
        'data-[slot=input-prefix]:rounded-l-box',
        'data-[slot=input-suffix]:rounded-r-box',
        'data-[slot=input-prefix]:border-r-0',
        'data-[slot=input-suffix]:border-l-0',
    ]) }}
>
    {{ $slot }}
</div>

@props([
    'autofocus' => false,
    'variant' => 'default', // tooltip | menu | compact | default
    'size' => 'md',         // xs | sm | md | lg
    'align' => 'left',      // left | right
])

@php
    $sizes = [
        'xs' => [
            'padding' => 'p-1',
            'text' => 'text-xs',
            'minWidth' => 'min-w-28',
            'maxHeight' => 'max-h-64',
        ],
        'sm' => [
            'padding' => 'p-1',
            'text' => 'text-sm',
            'minWidth' => 'min-w-44',
            'maxHeight' => 'max-h-72',
        ],
        'md' => [
            'padding' => 'p-1.5',
            'text' => 'text-sm',
            'minWidth' => 'min-w-56',
            'maxHeight' => 'max-h-80',
        ],
        'lg' => [
            'padding' => 'p-2',
            'text' => 'text-sm',
            'minWidth' => 'min-w-64',
            'maxHeight' => 'max-h-96',
        ],
    ];

    $variants = [
        'tooltip' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-md shadow-black/10 dark:shadow-black/30',
            'border' => 'border border-neutral-200/60 dark:border-neutral-700/60',
            'bg' => 'bg-white dark:bg-neutral-800',
        ],
        'menu' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-md shadow-black/8 dark:shadow-black/25',
            'border' => 'border border-neutral-200/70 dark:border-neutral-800',
            'bg' => 'bg-white dark:bg-neutral-950',
        ],
        'compact' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-lg shadow-black/8 dark:shadow-black/30',
            'border' => 'border border-neutral-200/70 dark:border-neutral-800',
            'bg' => 'bg-white dark:bg-neutral-950',
        ],
        'default' => [
            'radius' => 'rounded-xl',
            'shadow' => 'shadow-lg shadow-black/8 dark:shadow-black/30',
            'border' => 'border border-neutral-200/60 dark:border-neutral-800/80',
            'bg' => 'bg-white dark:bg-neutral-950',
        ],
    ];

    $v = $variants[$variant] ?? $variants['default'];
    $s = $sizes[$size] ?? $sizes['md'];

    $containerClass = Arr::toCssClasses([
        'absolute z-50',
        $align === 'right' ? 'right-0' : 'left-0',

        $s['padding'],
        $s['minWidth'],
        $s['maxHeight'],
        'overflow-y-auto overscroll-contain',

        $v['bg'],
        $v['radius'],
        $v['border'],
        $v['shadow'],

        $s['text'],
        'text-neutral-950 dark:text-neutral-50',

        // Ring for extra crispness
        'ring-1 ring-black/[0.03] dark:ring-white/[0.04]',

        // Scrollbar
        'scrollbar-thin scrollbar-thumb-neutral-200 dark:scrollbar-thumb-neutral-700',
        'scrollbar-track-transparent',
    ]);
@endphp

<div
    @if ($autofocus)
        x-data="{ shown: false }"
        x-modelable="shown"
        x-trap="shown"
        x-init="
            $nextTick(() => {
                let observer = new MutationObserver(() => {
                    shown = $el._x_isShown
                })
                observer.observe($el, { attributes: true, attributeFilter: ['style'] })
            })
        "
    @endif
    {{ $attributes->merge(['class' => $containerClass]) }}
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.97]"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.97]"
    style="display:none;"
    x-cloak
>
    {{ $slot }}
</div>

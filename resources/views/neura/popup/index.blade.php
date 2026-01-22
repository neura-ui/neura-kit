@props([
    'autofocus' => false,
    'variant' => 'default', // menu | compact | default
    'size' => 'md', // xs | sm | md | lg
    'align' => 'left', // left | right
])

@php
    // Size configurations (Notion/shadcn spacing)
    $sizes = [
        'xs' => [
            'padding' => 'p-1',
            'text' => 'text-xs',
            'minWidth' => 'min-w-32',
            'maxHeight' => 'max-h-64',
        ],
        'sm' => [
            'padding' => 'p-1.5',
            'text' => 'text-sm',
            'minWidth' => 'min-w-48',
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
            'text' => 'text-base',
            'minWidth' => 'min-w-64',
            'maxHeight' => 'max-h-96',
        ],
    ];

    // Variant configurations (shadcn design tokens)
    $variants = [
        'menu' => [
            'radius' => 'rounded-md',
            'shadow' => 'shadow-sm',
            'border' => 'border border-neutral-200/80 dark:border-neutral-800',
            'bg' => 'bg-white dark:bg-neutral-950',
            'offset' => '4',
        ],
        'compact' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-md',
            'border' => 'border border-neutral-200/80 dark:border-neutral-800',
            'bg' => 'bg-white dark:bg-neutral-950',
            'offset' => '6',
        ],
        'default' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-lg shadow-black/5',
            'border' => 'border border-neutral-200/80 dark:border-neutral-800',
            'bg' => 'bg-white dark:bg-neutral-950',
            'offset' => '8',
        ],
    ];

    $v = $variants[$variant] ?? $variants['default'];
    $s = $sizes[$size] ?? $sizes['md'];

    // Build class string with Notion/shadcn aesthetic
    $containerClass = Arr::toCssClasses([
        // Positioning
        'absolute z-50',
        'mt-2', // Default spacing from trigger
        $align === 'right' ? 'right-0' : 'left-0',

        // Spacing & Layout
        $s['padding'],
        $s['minWidth'],
        $s['maxHeight'],
        'overflow-y-auto',

        // Visual styling (Notion/shadcn tokens)
        $v['bg'],
        $v['radius'],
        $v['border'],
        $v['shadow'],

        // Typography
        $s['text'],
        'text-neutral-950 dark:text-neutral-50',

        // Scrollbar styling (Notion-like)
        'scrollbar-thin scrollbar-thumb-neutral-300 dark:scrollbar-thumb-neutral-700',
        'scrollbar-track-transparent',
    ]);
@endphp

<div @if ($autofocus) x-data="{ shown: false }"
        x-modelable="shown"
        x-trap="shown"
        x-init="
            $nextTick(() => {
                let observer = new MutationObserver(() => {
                    shown = $el._x_isShown
                })
                observer.observe($el, { attributes: true, attributeFilter: ['style'] })
            })
        " @endif
    {{ $attributes->merge(['class' => $containerClass]) }} x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-1 scale-95" style="display:none;" x-cloak>
    {{ $slot }}
</div>

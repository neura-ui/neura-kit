@props([
    'autofocus' => true,
    'variant' => 'default', // menu | compact | default
    'size' => 'xs',         // xs | sm | md | lg
])

@php
    // Size map (density only)
    $sizes = [
        'xs' => [
            'padding' => 'px-2 py-1',
            'text' => 'text-xs',
            'minWidth' => '',
        ],
        'sm' => [
            'padding' => 'px-3 py-1.5',
            'text' => 'text-sm',
            'minWidth' => '',
        ],
        'md' => [
            'padding' => 'p-(--popup-padding)',
            'text' => '',
            'minWidth' => 'min-w-64',
        ],
        'lg' => [
            'padding' => 'p-6',
            'text' => 'text-base',
            'minWidth' => 'min-w-80',
        ],
    ];

    // Variant map (visual treatment)
    $variants = [
        'menu' => [
            'radius' => 'rounded-md',
            'shadow' => 'shadow-sm',
            'border' => 'border border-neutral-200 dark:border-neutral-700',
            'bg' => 'bg-white dark:bg-neutral-800',
            'animation' => 'translate',
            'width' => 'w-auto',
        ],

        'compact' => [
            'radius' => 'rounded-lg',
            'shadow' => 'shadow-md',
            'border' => 'border border-neutral-200 dark:border-neutral-700',
            'bg' => 'bg-white dark:bg-neutral-800',
            'animation' => 'translate',
            'width' => 'w-auto',
        ],

        'default' => [
            'radius' => 'rounded-(--popup-round)',
            'shadow' => 'shadow-lg',
            'border' => 'border border-neutral-200 dark:border-neutral-700',
            'bg' => 'bg-white dark:bg-neutral-800',
            'animation' => 'scale',
            'width' => str($attributes->get('class'))->contains(['w-']) ? '' : 'w-fit',
        ],
    ];

    $v = $variants[$variant] ?? $variants['default'];
    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<div
    @if($autofocus)
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

    {{ $attributes->class([
        'absolute z-[9999] mt-1',

        // Variant
        $v['bg'],
        $v['radius'],
        $v['border'],
        $v['shadow'],
        $v['width'],

        // Size
        $s['padding'],
        $s['text'],
        $s['minWidth'],
    ]) }}

    {{-- Animations --}}
    @if($v['animation'] === 'scale')
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    @else
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
    @endif

    style="display:none;"
>
    {{ $slot }}
</div>

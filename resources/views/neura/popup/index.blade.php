@props([
    'autofocus' => false,
    'variant' => 'default', // tooltip | menu | compact | default
    'size' => 'md',         // xs | sm | md | lg
    'align' => 'left',      // left | right | start | end | center
    'side' => 'bottom',     // top | bottom | left | right
])

@php
    use Neura\Kit\Support\PackResolver;

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
            'border' => 'border border-edge',
            'bg' => 'bg-surface-raised backdrop-blur-xl',
        ],
        'menu' => [
            'border' => 'border border-edge',
            'bg' => 'bg-surface-raised backdrop-blur-xl',
        ],
        'compact' => [
            'border' => 'border border-edge',
            'bg' => 'bg-surface-raised backdrop-blur-xl',
        ],
        'default' => [
            'border' => 'border border-edge',
            'bg' => 'bg-surface-raised backdrop-blur-xl',
        ],
    ];

    $roundedClass = PackResolver::rounded(neura_config('popup', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('popup', 'shadow'));

    $menuSide = in_array($side, ['top', 'bottom', 'left', 'right'], true) ? $side : 'bottom';
    $menuAlign = match ($align) {
        'right', 'end' => 'end',
        'center' => 'center',
        default => 'start',
    };

    $v = $variants[$variant] ?? $variants['default'];
    $s = $sizes[$size] ?? $sizes['md'];

    $containerClass = Arr::toCssClasses([
        'nk-menu-motion absolute z-50',
        $variant === 'tooltip' ? 'nk-tooltip' : null,
        $align === 'right' ? 'right-0' : 'left-0',

        $s['padding'],
        $s['minWidth'],
        $s['maxHeight'],
        'overflow-y-auto overscroll-contain',

        $v['bg'],
        $roundedClass,
        $v['border'],
        $shadowClass,

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
    data-side="{{ $menuSide }}"
    data-align="{{ $menuAlign }}"
    @if ($variant === 'tooltip')
        role="tooltip"
        x-bind:id="$id('nk-tooltip')"
        x-bind:data-instant="instant ? '' : null"
    @endif
    x-transition:enter="nk-menu-motion"
    x-transition:enter-start="nk-menu-from"
    x-transition:enter-end="nk-menu-to"
    x-transition:leave="nk-menu-leaving"
    x-transition:leave-start="nk-menu-to"
    x-transition:leave-end="nk-menu-out"
    style="display:none;"
    x-cloak
>
    {{ $slot }}
</div>

@aware([
    'collapsible' => true,
    'size' => 'md'
])@props([
    'icon' => null,
    'badge' => null,
    'label' => null,
    'href' => '#',
    'active' => null,
    'size' => null,
    'variant' => 'default',
    'color' => 'neutral',
    'activePattern' => null,
])@php
    $size = $size ?? $attributes->get('size') ?? 'md';

    $textSize = match ($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        default => 'text-base',
    };

    $iconSize = match ($size) {
        'xs','sm' => 'size-4',
        'lg' => 'size-6',
        'xl' => 'size-7',
        default => 'size-5',
    };

    if ($active === null) {
        $currentUrl = url()->current();
        $linkUrl = url($href);

        $isExactMatch = $currentUrl === $linkUrl;
        $active = $isExactMatch;

        if ($activePattern) {
            $patternPath = url($activePattern);
            $patternBase = rtrim(str_replace('/*', '', $patternPath), '/');
            $isPatternMatch = str_starts_with($currentUrl, $patternBase . '/') || $currentUrl === $patternBase;
            $active = $active || $isPatternMatch;
        }
    }

    $colorClasses = match ($color) {
        'danger' => [
            'text-red-600 dark:text-red-400',
            'hover:bg-red-50 dark:hover:bg-red-500/10',
            'hover:text-red-700 dark:hover:text-red-300',
            '[&_[data-slot=icon]]:text-red-500 dark:[&_[data-slot=icon]]:text-red-400',
        ],
        'warning' => [
            'text-amber-600 dark:text-amber-400',
            'hover:bg-amber-50 dark:hover:bg-amber-500/10',
            'hover:text-amber-700 dark:hover:text-amber-300',
            '[&_[data-slot=icon]]:text-amber-500 dark:[&_[data-slot=icon]]:text-amber-400',
        ],
        'success' => [
            'text-emerald-600 dark:text-emerald-400',
            'hover:bg-emerald-50 dark:hover:bg-emerald-500/10',
            'hover:text-emerald-700 dark:hover:text-emerald-300',
            '[&_[data-slot=icon]]:text-emerald-500 dark:[&_[data-slot=icon]]:text-emerald-400',
        ],
        'primary' => [
            'text-primary-600 dark:text-primary-400',
            'hover:bg-primary-50 dark:hover:bg-primary-500/10',
            'hover:text-primary-700 dark:hover:text-primary-300',
            '[&_[data-slot=icon]]:text-primary-500 dark:[&_[data-slot=icon]]:text-primary-400',
        ],
        default => [],
    };

    $isColored = $color !== 'neutral';

    $variantClasses = match ($variant) {
        'ghost' => [
            'text-fg-secondary',
            '[&:not([data-active-link])]:hover:text-fg',
            'data-active-link:text-fg data-active-link:font-medium',
        ],
        default => [
            'text-fg-secondary',
            '[&:not([data-active-link])]:hover:bg-hover',
            '[&:not([data-active-link])]:hover:text-fg',
            'data-active-link:bg-active',
            'data-active-link:text-fg',
            'data-active-link:font-medium',
        ],
    };

    $itemClassParts = [
        'cursor-pointer',
        'relative isolate flex items-center gap-x-2',
        'w-full px-3 py-1.5',
        'rounded-md transition-colors duration-150',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50 focus-visible:ring-offset-1',
        (!$isColored ? '[&_[data-slot=icon]]:text-fg-muted data-active-link:[&_[data-slot=icon]]:text-fg' : ''),
        '[:has([data-collapsed]_&)_&]:justify-center',
        ...($isColored ? $colorClasses : $variantClasses),
    ];
    $itemClasses = implode(' ', array_filter($itemClassParts));
@endphp
<li>
    <a
        href="{{ $href }}"
        data-slot="navlist-item"
        aria-label="{{ $label }}{{ $badge ? ' (' . $badge . ')' : '' }}"
        @if ($active) data-active-link aria-current="page" @endif
        {{ $attributes->merge(['class' => $itemClasses]) }}
    >
        @if ($icon)
            <neura::navlist.has-tooltip :tooltip="$label" :condition="$collapsible">
                <neura::icon :name="$icon" aria-hidden="true" :attributes="(new Illuminate\View\ComponentAttributeBag())
                        ->class($iconSize)"/>
            </neura::navlist.has-tooltip>
        @endif
        <span aria-hidden="true" class="{{ $textSize }} flex-1 in-[:has([data-collapsed]_&)]:hidden">
            {{ $label }}
        </span>
        @if ($badge)
            <span aria-hidden="true">
                <neura::badge size="sm" class="ml-auto in-[:has([data-collapsed]_&)]:hidden">
                    {{ $badge }}
                </neura::badge>
            </span>
        @endif
    </a>
</li>

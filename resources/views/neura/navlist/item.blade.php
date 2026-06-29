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
    'variant' => null,
    'color' => null,
    'activePattern' => null,
])@php
    use Neura\Kit\Support\PackResolver;

    $size = $size ?? $attributes->get('size') ?? neura_config('navlist', 'size');
    $variant = $variant ?? neura_config('navlist', 'variant');
    $color = $color ?? neura_config('navlist', 'color');

    $sizeConfig = PackResolver::navlistSize($size);
    $textSize = $sizeConfig['text'];
    $iconSize = $sizeConfig['icon'];

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

    $colorClasses = PackResolver::navlistColor($color);
    $isColored = $color !== 'neutral';
    $variantClasses = PackResolver::navlistVariant($variant);

    $itemClassParts = [
        'cursor-pointer',
        'relative isolate flex items-center gap-x-2',
        'w-full px-3 py-1.5',
        PackResolver::rounded(neura_config('navlist', 'rounded')),
        'transition-colors duration-150',
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

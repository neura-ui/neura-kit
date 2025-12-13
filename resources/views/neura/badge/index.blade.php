@props([
    'iconVariant' => 'micro',
    'iconAfter' => null,
    'variant' => neura_config('badge', 'variant'),
    'rounded' => neura_config('badge', 'rounded'),
    'pill' => false,
    'color' => neura_config('badge', 'color'),
    'size' => neura_config('badge', 'size'),
    'icon' => null,
])
@php
    use Neura\Kit\Support\PackResolver;

    $colorClasses = PackResolver::badgeColor($color ?? 'secondary', $variant);
    $sizeClasses = PackResolver::badgeSize($size ?? 'sm', $pill);
    $roundedClass = $pill ? 'rounded-full' : PackResolver::rounded($rounded ?? 'md');

    $classes = [
        'inline-flex items-center font-medium whitespace-nowrap gap-x-0.5',
        $colorClasses,
        $sizeClasses,
        $roundedClass,
    ];

    $iconClasses = [
        'size-4' => $iconVariant === 'outline',
    ];

@endphp

<neura::button.abstract
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
    data-slot="badge"
>

    @if (is_string($icon) && $icon !== '')
        <neura::icon :name="$icon" :variant="$iconVariant" class="{{ Arr::toCssClasses($iconClasses) }}"
            data-slot="badge-icon" />
    @else
        {{ $icon }}
    @endif

    {{ $slot }}

    @if ($iconAfter)
        @if (is_string($iconAfter))
            <neura::icon :name="$iconAfter" :variant="$iconVariant" class="{{ Arr::toCssClasses($iconClasses) }}"
                data-slot="badge-icon:after" />
        @else
            {{ $iconAfter }}
        @endif
    @endif
</neura::button.abstract>

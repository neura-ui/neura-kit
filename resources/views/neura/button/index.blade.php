@aware(['icon', 'iconClasses', 'iconVariant', 'iconAfter'])

@props([
    'text' => null,
    'type' => 'button',
    'size' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'loading' => false,
    'loadingDisabled' => false,
    'loadingIndicator' => null,
    'orbState' => null,
    'orbVariant' => null,
    'orbSize' => null,
    'orbColor' => null,
    'variant' => null,
    'icon' => null,
    'iconAfter' => null,
    'iconVariant' => null,
    'as' => 'button',
    'iconClasses' => null,
    'squared' => false,
])

@php
    use Illuminate\View\ComponentAttributeBag;
    use Neura\Kit\Packs\Button\IconSize;
    use Neura\Kit\Support\PackResolver;

    $size = $size ?? neura_config('button', 'size') ?? 'sm';
    $rounded = $rounded ?? neura_config('button', 'rounded');
    $shadow = $shadow ?? neura_config('button', 'shadow');
    $variant = $variant ?? neura_config('button', 'variant') ?? 'primary';
    $loadingIndicator = $loadingIndicator ?? neura_config('button', 'loadingIndicator') ?? 'spinner';
    $useOrbLoading = $loadingIndicator === 'orb';
    $orbState = $orbState ?? neura_config('button', 'orbState') ?? 'working';
    $orbColor = $orbColor ?? neura_config('button', 'orbColor') ?? 'current';
    $orbSize = $orbSize ?? IconSize::orb()[$size] ?? 16;

    $semanticColors = ['primary', 'secondary', 'danger', 'success', 'warning', 'info'];
    $tailwindColors = [
        'slate', 'gray', 'zinc', 'neutral', 'stone',
        'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald',
        'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple',
        'fuchsia', 'pink', 'rose',
    ];
    $allColors = array_merge($semanticColors, $tailwindColors);
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];

    $defaultColorStyle = [
        'primary' => 'dark',
        'secondary' => 'dark',
    ];

    if (str_contains($variant, '-')) {
        $parts = explode('-', $variant, 2);
        $color = $color ?? $parts[0];
        $style = $parts[1];
    } elseif (in_array($variant, $allColors)) {
        $color = $color ?? $variant;
        $style = $defaultColorStyle[$color] ?? 'solid';
    } elseif (in_array($variant, $variantStyles)) {
        $color = $color ?? neura_config('button', 'color') ?? 'primary';
        $style = $variant;
    } else {
        $color = $color ?? 'primary';
        $style = $defaultColorStyle[$color] ?? 'solid';
    }

    $squared = $slot->isEmpty();

    $sizeConfig = PackResolver::buttonSize($size);
    $sizeClasses = $squared
        ? "{$sizeConfig['paddingSquared']} {$sizeConfig['base']}"
        : ($icon ? $sizeConfig['paddingWithIcon'] : ($iconAfter ? $sizeConfig['paddingWithIconAfter'] : $sizeConfig['padding'])) . ' ' . $sizeConfig['base'];

    $iconVariant ??= IconSize::variant()[$size] ?? 'mini';

    $colorConfig = PackResolver::buttonColor($color, $style);

    $stripShadow = static fn (?string $classes): string => trim((string) preg_replace('/\s*shadow-[^\s]+/', '', $classes ?? ''));

    $shadowClass = in_array($style, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);

    $colorClasses = [
        $stripShadow($colorConfig['base'] ?? ''),
        $colorConfig['hover'] ?? '',
    ];

    $roundedClass = PackResolver::rounded($rounded);

    $iconClasses = [
        $iconClasses,
        $sizeConfig['icon'] ?? 'size-5',
    ];

    $iconAttributes = (new ComponentAttributeBag())->class($iconClasses);

    $classes = [
        'relative inline-flex items-center font-medium justify-center gap-x-2 whitespace-nowrap transition-all duration-150',
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none cursor-pointer',
        $roundedClass,
        $shadowClass,
        '[&>[data-loading=true]:first-child]:flex',
        '[&>[data-loading=true]:first-child~*]:opacity-0',
        '[&_[data-slot=left-icon]]:shrink-0',
        '[&_[data-slot=right-icon]]:shrink-0',
        $sizeClasses,
        ...$colorClasses
    ];

    $wireTarget = $attributes->get('wire:target')
        ?? $attributes->whereStartsWith('wire:click')->first()
        ?? null;

    $loadingAttributes = new ComponentAttributeBag(
        $wireTarget ? [
            'wire:loading.attr' => 'data-loading',
            'wire:target' => $wireTarget,
        ] : []
    );

    if ($loading) {
        $loadingAttributes = $loadingAttributes->merge(['data-loading' => 'true']);
    }
@endphp

<neura::button.abstract
    :$as
    :$type
    :attributes="$attributes
        ->class($classes)
        ->merge([
            'role' => $as === 'a' && !$attributes->has('href') ? 'button' : null,
            'aria-busy' => $loading ? 'true' : 'false',
            'aria-disabled' => $attributes->has('disabled') ? 'true' : 'false',
            'aria-label' => $squared && blank($slot) ? Str::title($icon ?? $iconAfter ?? 'Button') : null,
        ])"
    data-slot="button"
>
    <div @class(['absolute inset-0 hidden items-center justify-center']) {{ $loadingAttributes }}>
        @if ($useOrbLoading)
            <neura::orb
                :state="$orbState"
                :variant="$orbVariant"
                :size="$orbSize"
                :color="$orbColor"
                data-slot="loading-indicator"
            />
        @else
            <neura::icon.loading :variant="$iconVariant" :attributes="$iconAttributes" data-slot="loading-indicator"/>
        @endif
    </div>

    @if($icon)
        <neura::icon :name="$icon" :variant="$iconVariant" :attributes="$iconAttributes" data-slot="left-icon"/>
    @endif

    @if($slot->isNotEmpty())
        <span>{{ $slot }}</span>
    @endif

    @if($iconAfter)
        <neura::icon :name="$iconAfter" :variant="$iconVariant" :attributes="$iconAttributes" data-slot="right-icon"/>
    @endif
</neura::button.abstract>

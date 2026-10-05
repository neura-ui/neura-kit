@props([
    'label' => 'Actions',
    'items' => [],
    'size' => null,
    'variant' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'defaultExpanded' => null,
    'confirmDuration' => 1400,
])

@php
    use Neura\Kit\Support\PackResolver;

    $items = array_values(is_array($items) ? $items : []);
    $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $size = $size ?? neura_config('expanding-button-group', 'size') ?? 'md';
    $variant = $variant ?? neura_config('expanding-button-group', 'variant') ?? 'soft';
    $color = $color ?? neura_config('expanding-button-group', 'color');
    $rounded = $rounded ?? neura_config('expanding-button-group', 'rounded');
    $shadow = $shadow ?? neura_config('expanding-button-group', 'shadow');

    if (str_contains((string) $variant, '-')) {
        [$maybeColor, $maybeVariant] = explode('-', (string) $variant, 2);
        if (in_array($maybeVariant, $variantStyles, true)) {
            $color = $color ?: $maybeColor;
            $variant = $maybeVariant;
        }
    }

    $size = in_array($size, $sizes, true) ? $size : 'md';
    $variant = in_array($variant, $variantStyles, true) ? $variant : 'soft';
    $color = strtolower((string) $color);
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : '';
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = PackResolver::shadow($shadow);
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-ebg-tone: var(--color-{$color}-600); --nk-ebg-tone-strong: var(--color-{$color}-900); --nk-ebg-tone-soft: var(--color-{$color}-100);"
        : '';

    $rest = $defaultExpanded;
    if (! filled($rest)) {
        foreach ($items as $item) {
            if (empty($item['disabled'])) {
                $rest = $item['id'] ?? null;
                break;
            }
        }
    }

    $config = [
        'defaultExpanded' => $rest,
        'confirmDuration' => (int) $confirmDuration,
    ];

    $root = [
        'role' => 'toolbar',
        'aria-label' => $label,
        'aria-orientation' => 'horizontal',
        'data-size' => $size,
        'data-variant' => $variant,
        'data-color' => $color !== '' ? $color : 'neutral',
        'x-data' => 'neuraExpandingGroup('.json_encode($config).')',
        'x-on:pointermove' => 'onPointerMove($event)',
        'x-on:pointerleave' => 'onPointerLeave($event)',
        'x-on:keydown' => 'onKeyDown($event)',
        'x-on:focusout' => 'onFocusOut($event)',
    ];

    if ($toneStyle !== '') {
        $root['style'] = $toneStyle;
    }

    $rootAttributes = $attributes->merge($root)->class(['nk-ebg', $roundedClass, $shadowClass]);
@endphp

<div {{ $rootAttributes }}>
    @foreach ($items as $item)
        @include('neura::expanding-button-group.button', [
            'id' => $item['id'] ?? $item['label'] ?? 'action',
            'label' => $item['label'] ?? '',
            'icon' => $item['icon'] ?? null,
            'method' => $item['method'] ?? null,
            'doneLabel' => $item['doneLabel'] ?? $item['done-label'] ?? null,
            'color' => $item['color'] ?? ($color !== '' && $color !== 'neutral' ? $color : null),
            'disabled' => (bool) ($item['disabled'] ?? false),
            'expanded' => ($item['id'] ?? null) === $rest,
        ])
    @endforeach

    {{ $slot }}

    <span class="sr-only" role="status" aria-live="polite" x-text="announcement"></span>
</div>

@props([
    'collapsable' => false,
    'variant' => 'with-sidebar-only',
    'theme' => 'default',
    'accentColor' => null,
])

@php
    $variantPath = match($variant) {
        'with-sidebar-header' => "neura::layout.variant.with-sidebar-header",
        'with-sidebar-only' => "neura::layout.variant.with-sidebar-only",
        'without-sidebar' => "neura::layout.variant.without-sidebar",
        'full' => "neura::layout.variant.with-sidebar-header",
        'sidebar' => "neura::layout.variant.with-sidebar-only",
        default => "neura::layout.variant.with-sidebar-only",
    };

    $themeClasses = match($theme) {
        'default' => '',
        'contrast' => 'nk-layout--contrast',
        'muted' => 'nk-layout--muted',
        'accent' => 'nk-layout--accent',
        default => '',
    };

    $accentColors = [
        'blue'   => ['light' => 'oklch(0.97 0.012 260)',  'dark' => 'oklch(0.16 0.025 260)',  'sep' => 'oklch(0.90 0.02 260)'],
        'indigo' => ['light' => 'oklch(0.97 0.012 275)',  'dark' => 'oklch(0.16 0.025 275)',  'sep' => 'oklch(0.90 0.02 275)'],
        'purple' => ['light' => 'oklch(0.97 0.012 295)',  'dark' => 'oklch(0.16 0.025 295)',  'sep' => 'oklch(0.90 0.02 295)'],
        'rose'   => ['light' => 'oklch(0.97 0.012 350)',  'dark' => 'oklch(0.16 0.025 350)',  'sep' => 'oklch(0.90 0.02 350)'],
        'red'    => ['light' => 'oklch(0.97 0.012 25)',   'dark' => 'oklch(0.16 0.025 25)',   'sep' => 'oklch(0.90 0.02 25)'],
        'orange' => ['light' => 'oklch(0.97 0.012 55)',   'dark' => 'oklch(0.16 0.025 55)',   'sep' => 'oklch(0.90 0.02 55)'],
        'green'  => ['light' => 'oklch(0.97 0.012 145)',  'dark' => 'oklch(0.16 0.025 145)',  'sep' => 'oklch(0.90 0.02 145)'],
        'teal'   => ['light' => 'oklch(0.97 0.012 175)',  'dark' => 'oklch(0.16 0.025 175)',  'sep' => 'oklch(0.90 0.02 175)'],
        'cyan'   => ['light' => 'oklch(0.97 0.012 210)',  'dark' => 'oklch(0.16 0.025 210)',  'sep' => 'oklch(0.90 0.02 210)'],
    ];
    $accent = $accentColor ? ($accentColors[$accentColor] ?? null) : null;
    $accentStyle = $accent
        ? "--nk-accent-surface: {$accent['light']}; --nk-accent-surface-dark: {$accent['dark']}; --nk-accent-separator: {$accent['sep']};"
        : '';
@endphp

<x-dynamic-component
    :component="$variantPath"
    :collapsable="$collapsable"
    :attributes="$attributes->merge(['class' => $themeClasses, 'style' => $accentStyle])"
>
    {{ $slot }}
</x-dynamic-component>

<neura::layout.runtime :$collapsable />
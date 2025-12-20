@props([
    'collapsable' => false,
    'variant' => 'with-sidebar-only',
])

@php
    $variantPath = match($variant) {
        // Nouveaux noms significatifs
        'with-sidebar-header' => "neura::layout.variant.with-sidebar-header",
        'with-sidebar-only' => "neura::layout.variant.with-sidebar-only",
        'without-sidebar' => "neura::layout.variant.without-sidebar",
        // Compatibilité avec les anciens noms
        'full' => "neura::layout.variant.with-sidebar-header",
        'sidebar' => "neura::layout.variant.with-sidebar-only",
        default => "neura::layout.variant.with-sidebar-only",
    };
@endphp

<x-dynamic-component
    :component="$variantPath"
    :collapsable="$collapsable"
>
    {{ $slot }}
</x-dynamic-component>

<neura::layout.runtime :$collapsable />
@props([
    'collapsable' => false,
    'variant' => 'sidebar',
])

@php
    $variantPath = match($variant) {
        'sidebar', 'full' => "neura::layout.variant.{$variant}",
        default => "neura::layout.variant.sidebar",
    };
@endphp

<x-dynamic-component
    :component="$variantPath"
    :collapsable="$collapsable"
>
    {{ $slot }}
</x-dynamic-component>

<neura::layout.runtime :$collapsable />
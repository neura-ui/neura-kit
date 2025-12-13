@props([
    'size' => 'default',
])

@php
    $classes = match($size) {
        'xs' => 'h-2',
        'sm' => 'h-4',
        'md' => 'h-6',
        'lg' => 'h-8',
        'xl' => 'h-12',
        '2xl' => 'h-16',
        default => 'h-6',
    };
@endphp

<div {{ $attributes->class($classes) }} data-slot="spacer"></div>

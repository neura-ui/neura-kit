@props([
    'size' => 'default',
    'centered' => true,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'max-w-2xl',
        'md' => 'max-w-4xl',
        'lg' => 'max-w-6xl',
        'xl' => 'max-w-7xl',
        'full' => 'max-w-full',
        default => 'max-w-5xl',
    };

    $classes = [
        'w-full px-4 sm:px-6 lg:px-8',
        $centered ? 'mx-auto' : '',
        $sizeClasses
    ];
@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }} data-slot="container">
    {{ $slot }}
</div>

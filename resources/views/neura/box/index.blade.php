@props([
    'padding' => 'default',
    'variant' => 'default',
])

@php
    $paddingClasses = match($padding) {
        'none' => 'p-0',
        'xs' => 'p-2',
        'sm' => 'p-3',
        'md' => 'p-4',
        'lg' => 'p-6',
        'xl' => 'p-8',
        default => 'p-4',
    };

    $variantClasses = match($variant) {
        'bordered' => 'border border-neutral-200 dark:border-neutral-800 rounded-lg',
        'muted' => 'bg-neutral-50 dark:bg-neutral-900/50 rounded-lg',
        'card' => 'bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-lg shadow-sm',
        default => '',
    };

    $classes = [
        $paddingClasses,
        $variantClasses
    ];
@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }} data-slot="box">
    {{ $slot }}
</div>

@props([
    'variant' => 'line',
])

@php
    $classes = match($variant) {
        'pills' => 'inline-flex h-10 items-center justify-center rounded-md bg-neutral-100 p-1 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
        'line' => 'inline-flex h-10 items-center justify-start border-b border-neutral-200 dark:border-neutral-800 w-full',
        default => 'inline-flex h-10 items-center justify-start border-b border-neutral-200 dark:border-neutral-800 w-full',
    };
@endphp

<div
    {{ $attributes->class($classes) }}
    role="tablist"
    data-slot="tab-list"
>
    {{ $slot }}
</div>

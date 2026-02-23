@props([
    'variant' => 'line',
])

@php
    $classes = match($variant) {
        'pills' => 'inline-flex h-10 items-center justify-center rounded-md bg-surface-inset p-1 text-fg-secondary',
        'line' => 'inline-flex h-10 items-center justify-start border-b border-edge w-full',
        default => 'inline-flex h-10 items-center justify-start border-b border-edge w-full',
    };
@endphp

<div
    {{ $attributes->class($classes) }}
    role="tablist"
    data-slot="tab-list"
>
    {{ $slot }}
</div>

@props([
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'text-[0.625rem] px-3 py-0.5',
        'lg' => 'text-sm px-3 py-2',
        default => 'text-xs px-3 py-1.5',
    };
@endphp

<li {{ $attributes->class([
    $sizeClasses,
    'font-medium uppercase tracking-wider',
    'text-fg-muted',
    'select-none',
]) }}>
    {{ $slot }}
</li>

@props([
    'variant' => 'default',
    'size' => 'md',
])

@php
    $classes = [
        'default' => 'bg-surface-inset border-edge text-fg-muted',
        'primary' => 'bg-primary/10 border-primary/20 text-primary',
    ];

    $sizes = [
        'xs' => 'px-1 py-0.5 text-[10px] min-h-[16px]',
        'sm' => 'px-1.5 py-0.5 text-xs min-h-[20px]',
        'md' => 'px-2 py-1 text-xs min-h-[24px]',
        'lg' => 'px-2.5 py-1.5 text-sm min-h-[28px]',
    ];
@endphp

<kbd {{ $attributes->merge(['class' => 'inline-flex items-center justify-center font-sans font-medium rounded border ' . ($classes[$variant] ?? $classes['default']) . ' ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $slot }}
</kbd>

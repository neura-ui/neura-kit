@props([
    'align' => null,
    'size' => 'md',
])

@php
    $alignClasses = match($align) {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        'justify' => 'text-justify',
        default => '',
    };

    $sizeClasses = match($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
        'xl' => 'text-xl',
        '2xl' => 'text-2xl',
        '3xl' => 'text-3xl',
        '4xl' => 'text-4xl',
        default => 'text-sm'
    };

    $classes = [
        'text-neutral-500 dark:text-neutral-400',
        '[:where(&)]:leading-relaxed',
        $sizeClasses,
        $alignClasses
    ];
@endphp

<div
    {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }}
    data-slot="description"
>
    {{ $slot }}
</div>

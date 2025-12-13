@props([
    'level' => 'h2',
    'size' => 'sm',
    'align' => null,
])

@php
    $tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $level : 'h2';

    $variantClasses = match ($size) {
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'text-xl',
        'xl' => 'text-2xl',
        '2xl' => 'text-3xl',
        '3xl' => 'text-4xl',
        '4xl' => 'text-5xl',
        '5xl' => 'text-6xl',
        '6xl' => 'text-7xl',
        '7xl' => 'text-8xl',
        '8xl' => 'text-9xl',
        default => 'text-base'
    };

    $alignClasses = match($align) {
        'left' => 'text-left',
        'center' => 'text-center',
        'right' => 'text-right',
        default => '',
    };

    $classes = [
        'font-semibold text-neutral-900 dark:text-white tracking-tight',
        $variantClasses,
        $alignClasses
    ];

@endphp

<{{ $tag }}
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
    data-slot="heading"
>
    {{ $slot }}
</{{ $tag }}>

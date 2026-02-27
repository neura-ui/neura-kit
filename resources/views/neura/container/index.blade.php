@props([
    'size' => 'default',
    'centered' => true,
    'direction' => 'none',
])

@php
    $sizeClasses = match($size) {
        'xs' => 'max-w-xl',
        'sm' => 'max-w-2xl',
        'md' => 'max-w-4xl',
        'lg' => 'max-w-6xl',
        'xl' => 'max-w-7xl',
        '2xl' => 'max-w-8xl',
        '3xl' => 'max-w-9xl',
        '4xl' => 'max-w-10xl',
        '5xl' => 'max-w-11xl',
        '6xl' => 'max-w-12xl',
        '7xl' => 'max-w-13xl',
        '8xl' => 'max-w-14xl',
        '9xl' => 'max-w-15xl',
        'full' => 'max-w-full',
        default => 'max-w-5xl',
    };

    $directionClasses = match($direction) {
        'horizontal' => 'flex flex-row',
        'vertical' => 'flex flex-col',
        'none' => '',
    };

    if ($size === 'full') {
        $classes = [
            $centered ? 'mx-auto' : '',
            $sizeClasses,
            $directionClasses,
        ];
    } else {
        $classes = [
            'w-full px-4 sm:px-6 lg:px-8',
            $centered ? 'mx-auto' : '',
            $sizeClasses,
            $directionClasses,
        ];
    }
@endphp

<div {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }} data-slot="container">
    {{ $slot }}
</div>

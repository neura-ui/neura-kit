@props([
    'direction' => 'vertical',
    'gap' => 'default',
    'align' => 'start',
    'justify' => 'start',
])

@php
    $directionClasses = match($direction) {
        'horizontal' => 'flex flex-row',
        'vertical' => 'flex flex-col',
        default => 'flex flex-col',
    };

    $gapClasses = match($gap) {
        'none' => 'gap-0',
        'xs' => 'gap-1',
        'sm' => 'gap-2',
        'md' => 'gap-4',
        'lg' => 'gap-6',
        'xl' => 'gap-8',
        default => 'gap-4',
    };

    $alignClasses = match($align) {
        'start' => 'items-start',
        'center' => 'items-center',
        'end' => 'items-end',
        'stretch' => 'items-stretch',
        default => 'items-start',
    };

    $justifyClasses = match($justify) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'end' => 'justify-end',
        'between' => 'justify-between',
        'around' => 'justify-around',
        default => 'justify-start',
    };

    $classes = [
        'w-full',
        $directionClasses,
        $gapClasses,
        $alignClasses,
        $justifyClasses
    ];
@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }} data-slot="stack">
    {{ $slot }}
</div>

@props([
    'cols' => '1',
    'gap' => 'default',
    'responsive' => true,
])

@php
    $colsClasses = match($cols) {
        '1' => 'grid-cols-1',
        '2' => $responsive ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-2',
        '3' => $responsive ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3' : 'grid-cols-3',
        '4' => $responsive ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4' : 'grid-cols-4',
        '6' => $responsive ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6' : 'grid-cols-6',
        default => 'grid-cols-1',
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

    $classes = [
        'grid',
        $colsClasses,
        $gapClasses
    ];
@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }} data-slot="grid">
    {{ $slot }}
</div>

@props([
    'spacing' => 'default',
    'variant' => 'default',
])

@php
    $spacingClasses = match($spacing) {
        'none' => '',
        'sm' => 'py-8',
        'md' => 'py-12',
        'lg' => 'py-16',
        'xl' => 'py-24',
        default => '',
    };

    $variantClasses = match($variant) {
        'muted' => 'bg-surface-inset',
        'bordered' => 'border-y border-edge',
        default => '',
    };

    $classes = [
        $spacingClasses,
        $variantClasses
    ];
@endphp

<section {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }} data-slot="section">
    {{ $slot }}
</section>

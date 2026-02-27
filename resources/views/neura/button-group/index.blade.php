@props([
    'size' => 'sm',
    'variant' => 'outline',
    'color' => null,
    'vertical' => false,
    'attached' => true,
])

@php
    $classes = [
        'inline-flex',
        'flex-col' => $vertical,
        'flex-row' => !$vertical,
        
        // Attached mode: no gap, shared borders
        '[&>*]:rounded-none' => $attached,
        '[&>*:first-child]:rounded-s-lg' => $attached && !$vertical,
        '[&>*:last-child]:rounded-e-lg' => $attached && !$vertical,
        '[&>*:first-child]:rounded-t-lg' => $attached && $vertical,
        '[&>*:last-child]:rounded-b-lg' => $attached && $vertical,
        
        // Remove double borders in attached mode
        '[&>*:not(:first-child)]:-ms-px' => $attached && !$vertical,
        '[&>*:not(:first-child)]:-mt-px' => $attached && $vertical,
        
        // Hover z-index for proper border display
        '[&>*:hover]:z-10 [&>*:focus]:z-10' => $attached,
        
        // Non-attached mode: gap between buttons
        'gap-1' => !$attached,
    ];
@endphp

<div
    {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }}
    role="group"
    data-slot="button-group"
>
    {{ $slot }}
</div>


@props([
    'required' => false,
    'disabled' => false,
])

@php
    $classes = [
        'min-w-0 w-full',

        '[&>[data-slot=label]]:mb-2 text-start',
        '[&>[data-slot=label]:has(+[data-slot=description])]:mb-1.5',

        '[&>[data-slot=label]+[data-slot=description]]:mt-0',
        '[&>[data-slot=label]+[data-slot=description]]:mb-2',
        '[&>*:not([data-slot=label])+[data-slot=description]]:mt-2',

        '[&>[data-slot=error]]:mt-1.5',

        $disabled ? '[&>[data-slot=label]]:opacity-50' : '',
        '[&:has([data-slot=control][disabled])>[data-slot=label]]:opacity-50',
    ];
@endphp

<div {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }} data-slot="field">
    {{ $slot }}
</div>
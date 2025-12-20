@props([
    'variant' => 'default',
])

@php
    $classes = match($variant) {
        'pills' => 'flex gap-2 flex-wrap',
        'cards' => 'flex flex-col gap-2',
        default => [
            '[&>[data-slot=checkbox-wrapper]:not(:first-child)]:mt-3',
            '[&>[data-slot=checkbox-wrapper]:has([data-slot=checkbox-description])+[data-slot=checkbox-wrapper]]:mt-4'
        ]
    };
@endphp

<div
    x-data="{
        state: @entangle($attributes->wire('model')).live
    }"
    {{ $attributes->class($classes) }}
    data-slot="checkbox-group"
>
    {{ $slot }}
</div>

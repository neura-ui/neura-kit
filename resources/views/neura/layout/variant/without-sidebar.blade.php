
@props([
    'collapsable' => false
])

@php
    $classes = [
        '[--header-height:4rem]',
        'grid h-screen overflow-hidden min-h-screen text-neutral-950 dark:text-neutral-50',
        'grid-cols-1 grid-rows-[var(--header-height)_1fr]',
        "[grid-template-areas:'header'_'main']",
        '[&_[data-slot=header]]:sticky [&_[data-slot=header]]:top-0 [&_[data-slot=header]]:z-50 [&_[data-slot=header]]:h-[var(--header-height)]',
    ];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="layout"
>
    {{ $slot }}
</div>






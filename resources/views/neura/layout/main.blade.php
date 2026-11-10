@php
    $classes = [
        '[grid-area:main]',
        // Positioned, so an absolute descendant (a screen-reader label) scrolls inside the pane
        // instead of lengthening the page behind the layout.
        'relative',
        'overflow-y-auto',
        'min-h-0',
        'bg-surface',
        '[&>:has([data-slot=header])]:p-0',
        '[&>:not(:has([data-slot=header]))]:p-2',
    ];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="main"
>
    {{ $slot }}
</div>

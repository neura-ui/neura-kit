@php
    $mainClasses = '[grid-area:main] overflow-y-auto min-h-0 max-h-screen bg-surface [&>:has([data-slot=header])]:p-0 [&>:not(:has([data-slot=header]))]:p-2';
@endphp

<div
    {{ $attributes->merge(['class' => $mainClasses]) }}
    data-slot="main"
>
    {{ $slot }}
</div>

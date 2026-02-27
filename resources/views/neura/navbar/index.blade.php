@php
$navbarClasses = 'flex items-center gap-x-2 py-1 px-2';
@endphp

<div
    {{ $attributes->merge(['class' => $navbarClasses]) }}
    data-slot="navbar"
>
    {{ $slot }}
</div>
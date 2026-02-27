@props([
    'size' => 'md',
    'variant' => 'default',
    'label' => null,
])

@php
    $navlistClasses = 'flex flex-col w-full [:has([data-collapsed]_&)_&]:items-center gap-y-0.5 py-1 px-2';
@endphp

<nav
    {{ $attributes->merge(['class' => $navlistClasses]) }}
    data-slot="navlist"
    aria-label="{{ $label ?? 'Navigation' }}"
>
    <ul role="list" class="flex flex-col w-full gap-y-0.5">
        {{ $slot }}
    </ul>
</nav>

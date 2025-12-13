@aware([
    'onHover' => false
])
@props([
    'onHover' => false,
])

<div
    x-ref="popoverTrigger"
    @if($onHover)
        x-on:mouseover="show()"
        x-on:mouseleave="hide()"
    @endif

    x-on:click="toggle()"
    {{ $attributes->merge(['class' => 'cursor-pointer']) }}
>
    {{ $slot }}
</div>
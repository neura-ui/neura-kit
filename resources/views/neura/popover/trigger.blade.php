@aware([
    'onHover' => false,
])

@props([
    'onHover' => false,
])

<div
    x-ref="popoverTrigger"
    @if($onHover)
        x-on:mouseenter="hoverShow()"
        x-on:mouseleave="hoverHide()"
    @endif
    x-on:click="toggle()"
    {{ $attributes->merge(['class' => 'inline-flex cursor-pointer']) }}
>
    {{ $slot }}
</div>

@aware([
    'onHover' => false,
    'variant' => 'default',
])

@props([
    'onHover' => false,
    'variant' => 'default',
])

<div
    x-ref="popoverTrigger"
    @if($onHover)
        x-on:mouseenter="hoverShow()"
        x-on:mouseleave="hoverHide()"
    @endif
    @if($variant === 'tooltip')
        x-on:focusin="focusShow()"
        x-on:focusout="hide()"
        x-bind:aria-describedby="open ? $id('nk-tooltip') : null"
    @endif
    x-on:click="toggle()"
    {{ $attributes->merge(['class' => 'inline-flex cursor-pointer']) }}
>
    {{ $slot }}
</div>

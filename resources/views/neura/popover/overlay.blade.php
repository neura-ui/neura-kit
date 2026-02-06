@aware([
    'position' => 'bottom',
    'offset' => 3,
    'onHover' => false,
    'variant' => 'default',
    'size' => 'md',
])

@props([
    'position' => 'bottom',
    'offset' => 3,
    'variant' => 'default',
    'size' => 'md',
    'matchWidth' => false,
])

@php
    $popupAttrs = [
        'x-anchor.' . $position . '.offset.' . $offset => '$refs.popoverTrigger',
        'x-show' => 'open',
        'x-on:click.away' => 'hide()',
        'x-on:keydown.escape' => 'hide()',
    ];

    if ($onHover) {
        $popupAttrs['x-on:mouseenter'] = 'hoverShow()';
        $popupAttrs['x-on:mouseleave'] = 'hoverHide()';
    }

    if ($matchWidth) {
        $popupAttrs['x-bind:style'] = "'min-width:' + $refs.popoverTrigger.offsetWidth + 'px'";
    }
@endphp

<neura::popup
    :variant="$variant"
    :size="$size"
    :attributes="$attributes->merge($popupAttrs)"
>
    {{ $slot }}
</neura::popup>

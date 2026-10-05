@aware([
    'position' => null,
    'offset' => null,
    'onHover' => false,
    'variant' => 'default',
    'size' => 'md',
])

@props([
    'position' => null,
    'offset' => null,
    'variant' => 'default',
    'size' => 'md',
    'matchWidth' => false,
])

@php
    $position = $position ?? ($variant === 'tooltip' ? 'top' : 'bottom');
    $offset = $offset ?? ($variant === 'tooltip' ? 8 : 3);

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

    [$popupSide, $popupAlign] = array_pad(explode('-', (string) $position, 2), 2, 'center');
@endphp

<neura::popup
    :variant="$variant"
    :size="$size"
    :side="$popupSide"
    :align="$popupAlign"
    :attributes="$attributes->merge($popupAttrs)"
>
    {{ $slot }}
</neura::popup>

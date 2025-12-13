@aware([
    'position' => 'bottom',
    'offset' => 3,
])

@props([
    'position' => 'bottom',
    'offset' => 3,
])

<neura::popup
    :attributes="$attributes->merge([
        'x-anchor.' . $position . '.offset.' . $offset => '$refs.popoverTrigger',
        'x-show' => 'open',
        'x-on:click.away' => 'hide()',
        'x-on:keydown.escape' => 'hide()'
    ])"
>
    {{ $slot }}
</neura::popup>

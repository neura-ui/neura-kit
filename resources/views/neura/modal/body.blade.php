@props([
    'padding' => true,
])

<div @class([
    'p-6' => $padding,
])>
    {{ $slot }}
</div>


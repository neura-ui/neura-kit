@props([
    'padding' => true,
])

<div @class([
    'px-6 py-2' => $padding,
])>
    {{ $slot }}
</div>


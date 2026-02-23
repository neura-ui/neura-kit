@props([
    'padding' => true,
])

<div {{ $attributes->class([
    'flex-1 overflow-auto',
    'px-5 py-5' => $padding,
]) }}>
    {{ $slot }}
</div>

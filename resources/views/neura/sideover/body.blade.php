@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'flex-1 overflow-auto' . ($padding ? ' px-5 py-5' : '')]) }}>
    {{ $slot }}
</div>

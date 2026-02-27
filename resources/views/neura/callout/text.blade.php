@props([])

<div {{ $attributes->merge(['class' => 'text-sm text-fg-secondary']) }}>
    {{ $slot }}
</div>



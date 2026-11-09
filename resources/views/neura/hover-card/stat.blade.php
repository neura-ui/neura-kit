@props([
    'label' => null,
    'value' => null,
])

<div {{ $attributes->class(['nk-hover-card-stat']) }}>
    <span class="nk-hover-card-stat-label">{{ $label }}</span>
    <span class="nk-hover-card-stat-value">{{ $value ?? $slot }}</span>
</div>

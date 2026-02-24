@props([
    'label',
    'icon' => null,
    'route' => null,
    'url' => null,
    'wireClick' => null,
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $href = $route ?? $url;
@endphp

@php
    $iconClasses = match($variant) {
        'primary' => '!text-white dark:!text-neutral-900',
        'outline' => '!text-neutral-600 dark:!text-neutral-400',
        default => null,
    };
@endphp

@if($wireClick)
    <neura::button
        wire:click="{{ $wireClick }}"
        variant="{{ $variant }}"
        size="{{ $size }}"
        icon="{{ $icon }}"
        iconClasses="{{ $iconClasses }}"
    >
        {{ $label }}
    </neura::button>
@elseif($href)
    <neura::button
        as="a"
        href="{{ $href }}"
        variant="{{ $variant }}"
        size="{{ $size }}"
        icon="{{ $icon }}"
        iconClasses="{{ $iconClasses }}"
    >
        {{ $label }}
    </neura::button>
@else
    <neura::button
        variant="{{ $variant }}"
        size="{{ $size }}"
        icon="{{ $icon }}"
        iconClasses="{{ $iconClasses }}"
    >
        {{ $label }}
    </neura::button>
@endif

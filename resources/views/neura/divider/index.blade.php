@props([
    'orientation' => 'horizontal',
    'label' => null,
])

@php
    $classes = match($orientation) {
        'vertical' => 'h-full w-px bg-surface-inset',
        'horizontal' => 'w-full h-px bg-surface-inset',
        default => 'w-full h-px bg-surface-inset',
    };
@endphp

@if($label)
    <div {{ $attributes->class('relative flex items-center') }}>
        <div class="flex-1 border-t border-edge"></div>
        <span class="px-3 text-xs font-medium text-fg-secondary bg-surface">
            {{ $label }}
        </span>
        <div class="flex-1 border-t border-edge"></div>
    </div>
@else
    <div {{ $attributes->class($classes) }} data-slot="divider" role="separator"></div>
@endif

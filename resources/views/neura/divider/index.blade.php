@props([
    'orientation' => 'horizontal',
    'label' => null,
])

@php
    $classes = match($orientation) {
        'vertical' => 'h-full w-px bg-neutral-200 dark:bg-neutral-800',
        'horizontal' => 'w-full h-px bg-neutral-200 dark:bg-neutral-800',
        default => 'w-full h-px bg-neutral-200 dark:bg-neutral-800',
    };
@endphp

@if($label)
    <div {{ $attributes->class('relative flex items-center') }}>
        <div class="flex-1 border-t border-neutral-200 dark:border-neutral-800"></div>
        <span class="px-3 text-xs font-medium text-neutral-600 dark:text-neutral-400 bg-white dark:bg-neutral-950">
            {{ $label }}
        </span>
        <div class="flex-1 border-t border-neutral-200 dark:border-neutral-800"></div>
    </div>
@else
    <div {{ $attributes->class($classes) }} data-slot="divider" role="separator"></div>
@endif

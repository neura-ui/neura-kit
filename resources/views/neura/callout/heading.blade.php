@aware(['variant' => 'primary'])

@props([
    'icon' => null,
])

@php
    $iconColorClass = match($variant) {
        'secondary' => 'text-secondary-600 dark:text-secondary-400',
        'success' => 'text-success-600 dark:text-success-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'danger' => 'text-danger-600 dark:text-danger-400',
        'info' => 'text-info-600 dark:text-info-400',
        default => 'text-primary-600 dark:text-primary-400',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @if($icon)
        <neura::icon :name="$icon" :class="'size-5 shrink-0 ' . $iconColorClass" />
    @endif
    
    <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
        {{ $slot }}
    </h3>
</div>



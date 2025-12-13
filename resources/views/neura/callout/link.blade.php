@aware(['variant' => 'primary'])

@props([
    'href' => '#',
])

@php
    $linkColorClass = match($variant) {
        'secondary' => 'text-secondary-700 hover:text-secondary-900 dark:text-secondary-300 dark:hover:text-secondary-100',
        'success' => 'text-success-700 hover:text-success-900 dark:text-success-300 dark:hover:text-success-100',
        'warning' => 'text-warning-700 hover:text-warning-900 dark:text-warning-300 dark:hover:text-warning-100',
        'danger' => 'text-danger-700 hover:text-danger-900 dark:text-danger-300 dark:hover:text-danger-100',
        'info' => 'text-info-700 hover:text-info-900 dark:text-info-300 dark:hover:text-info-100',
        default => 'text-primary-700 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-100',
    };
@endphp

<a 
    href="{{ $href }}"
    {{ $attributes->class([
        'hover:underline font-medium',
        $linkColorClass,
    ]) }}
>
    {{ $slot }}
</a>



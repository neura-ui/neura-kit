@aware(['required' => false])

@props([
    'text' => null
])

@php
    $classes = [
        'text-sm [:where(&)]:text-start font-base select-none',
        '[:where(&)]:text-neutral-900 [:where(&)]:dark:text-white',
    ];
@endphp

<div {{ $attributes->class($classes) }} data-slot="label">
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        {{ $text }}
    @endif

    @if(isset($required) && $required)
        <span class="text-red-600 dark:text-red-500 text-xs px-1 py-1" aria-hidden="true">
            *
        </span>
    @endif
</div>

@props([
    'align' => 'right',
])

@php
    $alignClasses = match($align) {
        'left' => 'justify-start',
        'center' => 'justify-center',
        'right' => 'justify-end',
        'between' => 'justify-between',
        default => 'justify-end',
    };
@endphp

<div class="flex items-center gap-3 {{ $alignClasses }} p-6 border-t border-neutral-200 dark:border-neutral-800">
    {{ $slot }}
</div>


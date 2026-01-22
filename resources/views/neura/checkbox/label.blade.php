@aware(['label', 'size'])

@php
    $classes = [
        // typography & color (match form label)
        'text-sm font-base select-none',
        '[:where(&)]:text-neutral-900 [:where(&)]:dark:text-white',
        '[:where(&)]:text-start',

        // behavior
        'whitespace-nowrap',

        // optional size override (kept for API compatibility)
        match ($size) {
            'xs' => 'text-xs',
            'sm' => 'text-sm',
            'md' => 'text-sm',
            'lg' => 'text-base',
            'xl' => 'text-lg',
            default => 'text-sm',
        },
    ];
@endphp

<label @class($classes) data-slot="checkbox-label">
    {{ $label }}
</label>

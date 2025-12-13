@aware(['label', 'size'])

@php
    $classes=[
        'font-medium [:where(&)]:text-neutral-900 font-semibold [:where(&)]:dark:text-white select-none',
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

<label
    @class($classes)
    data-slot="checkbox-label"
>
    {{ $label }}
</label>

@props(['size' => 'full'])

@php
    $variantClasses = match ($size) {
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' =>  'max-w-2xl',
        'full' => 'max-w-full',
    };

    $classes = [
        'bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-900',
        '[:where(&)]:p-6 [:where(&)]:rounded-lg shadow-sm',
        $variantClasses
    ];

@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }}>
    {{ $slot }}
</div>
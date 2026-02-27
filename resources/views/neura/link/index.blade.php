@props([
    'openInNewTab' => null,
    'primary' => true,
    'variant' => null,
])

@php
    $primary = filter_var($primary, FILTER_VALIDATE_BOOLEAN);

    $classes = [
        'inline w-fit whitespace-nowrap font-medium text-base text-start',
        'underline-offset-[6px] hover:decoration-current',
        match ($variant ?? '') {
            'ghost' => 'no-underline hover:underline',
            'soft' => 'no-underline',
            default => 'underline',
        },
        match ($variant ?? '') {
            'soft' => 'text-neutral-500 dark:text-white/70 hover:text-neutral-800 dark:hover:text-white',
            default => match ($primary) {
                true => 'text-primary-600 dark:text-primary-400 decoration-primary-600/20 dark:decoration-primary-400/20',
                false => 'text-neutral-800 dark:text-white decoration-neutral-800/20 dark:decoration-white/20',
            },
        },
    ];
@endphp

<a
    {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }}
    data-slot="link"
    @if ($openInNewTab) target="_blank" rel="noopener noreferrer" @endif
>
    {{ $slot }}
</a>

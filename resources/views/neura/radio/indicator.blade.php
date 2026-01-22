@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('radio');

    $indicatorClasses = [
        'relative shrink-0 size-5 rounded-full border transition-all duration-200',
        'bg-white dark:bg-neutral-900',
        $colors['border'] => true,
        // The dot inside
        "after:content-[''] after:absolute after:size-2 after:rounded-full after:top-1/2 after:left-1/2 after:-translate-x-1/2 after:-translate-y-1/2 after:opacity-0 after:scale-50 after:transition-all after:duration-200",
        $colors['dot'] => true,
    ];
@endphp

<div @class($indicatorClasses) data-slot="radio-item-indicator"></div>

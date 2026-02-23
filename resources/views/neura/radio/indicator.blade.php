@php
    use Neura\Kit\Support\PackResolver;

    $colors = PackResolver::inputColor('radio');

    $indicatorClasses = [
        'relative shrink-0 size-[18px] rounded-full border transition-all duration-200',
        'bg-transparent',
        $colors['border'] => true,
        "after:content-[''] after:absolute after:size-[7px] after:rounded-full after:top-1/2 after:left-1/2 after:-translate-x-1/2 after:-translate-y-1/2 after:opacity-0 after:scale-50 after:transition-all after:duration-200",
        $colors['dot'] => true,
    ];
@endphp

<div @class($indicatorClasses) data-slot="radio-item-indicator" aria-hidden="true"></div>

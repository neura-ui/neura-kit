@php
    $indicatorClasses = [
        'relative',
        'shrink-0 size-[1.125rem] rounded-full text-sm shadow-xs bg-white dark:bg-neutral-900',
        "after:content-[''] after:absolute after:size-[.5rem] after:rounded-full after:top-1/2 after:left-1/2 after:-translate-1/2 after:bg-primary-600 dark:after:bg-primary-400 after:hidden",
        'border border-primary-300/50 dark:border-primary-600/50',
        'transition-all duration-200',
    ];
@endphp

<div @class($indicatorClasses) data-slot="radio-item-indicator"></div>

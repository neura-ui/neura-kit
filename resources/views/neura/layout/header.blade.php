@props([
    'sticky' => true,
    'brand' => null,
    'actions' => null,

    // container concerns
    'size' => 'xl',
    'centered' => true,
    'direction' => 'horizontal',
])

@php
    /* --------------------------------------------
     | Header shell classes
     |-------------------------------------------- */
    $headerClasses = [
        '[grid-area:header]',
        'z-40 min-h-[var(--header-height)]',
        'border-b flex items-center',
        'dark:border-b-white/5 border-neutral-800/5',
        'sticky top-0 dark:bg-neutral-950 bg-white' => $sticky,
    ];

    /* --------------------------------------------
     | Container sizing
     |-------------------------------------------- */
    $sizeClasses = match ($size) {
        'xs' => 'max-w-xl',
        'sm' => 'max-w-2xl',
        'md' => 'max-w-4xl',
        'lg' => 'max-w-6xl',
        'xl' => 'max-w-7xl',
        '2xl' => 'max-w-8xl',
        '3xl' => 'max-w-9xl',
        '4xl' => 'max-w-10xl',
        '5xl' => 'max-w-11xl',
        '6xl' => 'max-w-12xl',
        '7xl' => 'max-w-13xl',
        '8xl' => 'max-w-14xl',
        '9xl' => 'max-w-15xl',
        'full' => 'max-w-full',
        default => 'max-w-5xl',
    };

    /* --------------------------------------------
     | Layout direction
     |-------------------------------------------- */
    $directionClasses = match ($direction) {
        'horizontal' => 'flex flex-row items-center',
        'vertical' => 'flex flex-col',
        default => '',
    };

    /* --------------------------------------------
     | Inner container classes
     |-------------------------------------------- */
    $containerClasses = Arr::toCssClasses([
        'w-full px-4 sm:px-6 lg:px-8',
        $centered ? 'mx-auto' : '',
        $sizeClasses,
        'flex items-center gap-4',
    ]);
@endphp

<div {{ $attributes->class($headerClasses) }} data-slot="header">
    <div class="{{ $containerClasses }}">
        @if (isset($slot->brand) || isset($brand))
            <div data-slot="header-brand" class="flex items-center shrink-0 gap-2">
                {{ isset($slot->brand) ? $slot->brand : $brand }}
            </div>
        @endif

        <div class="flex-1 min-w-0 {{ $directionClasses }}">
            {{ $slot }}
        </div>

        @if (isset($slot->actions) || isset($actions))
            <div data-slot="header-actions" class="flex items-center gap-2 shrink-0">
                {{ isset($slot->actions) ? $slot->actions : $actions }}
            </div>
        @endif
    </div>
</div>

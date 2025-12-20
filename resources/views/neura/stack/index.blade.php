@props([
    'direction' => 'vertical',
    'gap' => 'md',
    'align' => 'center',
    'justify' => null,
    'padding' => null,
    'margin' => null,
    'rounded' => null,

    'display' => 'flex',
    'position' => null,
])
@php
    $incomingClass = $attributes->get('class', '');

    $hasGap = str_contains($incomingClass, 'gap-');
    $hasAlign = str_contains($incomingClass, 'items-');
    $hasJustify = str_contains($incomingClass, 'justify-');
    $hasPadding = preg_match('/\bp[trblxy]?-\d+/', $incomingClass);
    $hasMargin = preg_match('/\bm[trblxy]?-\d+/', $incomingClass);
    $hasRounded = str_contains($incomingClass, 'rounded');

    $displayClasses = match ($display) {
        'inline-flex' => 'inline-flex',
        'block' => 'block',
        'grid' => 'grid',
        default => 'flex',
    };

    $directionClasses =
        $display === 'flex' || $display === 'inline-flex'
            ? match ($direction) {
                'horizontal' => 'flex-row',
                default => 'flex-col',
            }
            : null;

    $gapClasses =
        $hasGap || !in_array($display, ['flex', 'inline-flex', 'grid'], true)
            ? null
            : match ($gap) {
                'none' => 'gap-0',
                'xs' => 'gap-1',
                'sm' => 'gap-2',
                'md' => 'gap-4',
                'lg' => 'gap-6',
                'xl' => 'gap-8',
                default => 'gap-4',
            };

    $paddingClasses =
        $hasPadding || is_null($padding)
            ? null
            : match ($padding) {
                'none' => 'p-0',
                'xs' => 'p-1',
                'sm' => 'p-2',
                'md' => 'p-4',
                'lg' => 'p-6',
                'xl' => 'p-8',
                default => null,
            };

    $marginClasses =
        $hasMargin || is_null($margin)
            ? null
            : match ($margin) {
                'none' => 'm-0',
                'xs' => 'm-1',
                'sm' => 'm-2',
                'md' => 'm-4',
                'lg' => 'm-6',
                'xl' => 'm-8',
                default => null,
            };

    $roundedClasses =
        $hasRounded || is_null($rounded)
            ? null
            : match ($rounded) {
                'none' => 'rounded-none',
                'sm' => 'rounded-sm',
                'md' => 'rounded-md',
                'lg' => 'rounded-lg',
                'xl' => 'rounded-xl',
                '2xl' => 'rounded-2xl',
                '3xl' => 'rounded-3xl',
                '4xl' => 'rounded-4xl',
                '5xl' => 'rounded-5xl',
                '6xl' => 'rounded-6xl',
                '7xl' => 'rounded-7xl',
                '8xl' => 'rounded-8xl',
                '9xl' => 'rounded-9xl',
                'full' => 'rounded-full',
                default => null,
            };

    $alignClasses =
        $hasAlign || !in_array($display, ['flex', 'inline-flex'], true)
            ? null
            : match ($align) {
                'start' => 'items-start',
                'center' => 'items-center',
                'end' => 'items-end',
                'stretch' => 'items-stretch',
                default => null,
            };

    $justifyClasses =
        $hasJustify || !in_array($display, ['flex', 'inline-flex'], true)
            ? null
            : match ($justify) {
                'start' => 'justify-start',
                'center' => 'justify-center',
                'end' => 'justify-end',
                'between' => 'justify-between',
                'around' => 'justify-around',
                default => null,
            };

    $positionClasses = match ($position) {
        'relative' => 'relative',
        'absolute' => 'absolute',
        'fixed' => 'fixed',
        'sticky' => 'sticky',
        default => 'static',
    };

    $classes = array_filter([
        $displayClasses,
        $positionClasses,
        'w-full',
        $directionClasses,
        $gapClasses,
        $paddingClasses,
        $marginClasses,
        $roundedClasses,
        $alignClasses,
        $justifyClasses,
        '[&>*:not([class*="w-"])]:w-full',
    ]);
@endphp

<div {{ $attributes->class($classes) }} data-slot="stack">
    {{ $slot }}
</div>

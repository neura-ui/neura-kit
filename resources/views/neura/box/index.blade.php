@props([
    'padding' => 'default',
    'variant' => 'default',
    'width' => 'auto',
    'gap' => 'default',
    'display' => 'block',
    'direction' => 'vertical',
    'position' => null,
    'rounded' => null,
])

@php
    $gapClasses = match ($gap) {
        'none' => 'gap-0',
        'xs' => 'gap-1',
        'sm' => 'gap-2',
        'lg' => 'gap-4',
        'xl' => 'gap-5',
        default => 'gap-3',
    };

    $paddingClasses = match ($padding) {
        'none' => 'p-0',
        'xs' => 'p-2',
        'sm' => 'p-3',
        'lg' => 'p-6',
        'xl' => 'p-8',
        default => 'p-4',
    };

    $variantClasses = match ($variant) {
        'bordered' => 'border border-edge rounded-lg',
        'muted' => 'bg-surface-inset rounded-lg',
        'card' => 'bg-surface border border-edge rounded-lg shadow-sm',
        default => '',
    };

    $widthClasses = match ($width) {
        'auto' => 'w-auto',
        'full' => 'w-full',
        'fit' => 'w-fit',
        'sm' => 'w-64',
        'md' => 'w-96',
        'lg' => 'w-[32rem]',
        'xl' => 'w-[40rem]',
        default => null,
    };

    $displayClasses = match ($display) {
        'flex' => 'flex',
        'inline' => 'inline-block',
        'inline-flex' => 'inline-flex',
        'grid' => 'grid',
        default => 'block',
    };

    $directionClasses = $display === 'flex'
        ? match ($direction) {
            'horizontal' => 'flex-row',
            default => 'flex-col',
        }
        : null;

    $positionClasses = match ($position) {
        'relative' => 'relative',
        'absolute' => 'absolute',
        'fixed' => 'fixed',
        'sticky' => 'sticky',
        default => 'static',
    };

    $roundedClasses = match ($rounded) {
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

    $classes = array_filter([
        $displayClasses,
        $directionClasses,
        $positionClasses,
        $widthClasses,
        $paddingClasses,
        $variantClasses,
        $roundedClasses,
        $gapClasses,
    ]);
@endphp
<div {{ $attributes->class($classes) }} data-slot="box">
    {{ $slot }}
</div>

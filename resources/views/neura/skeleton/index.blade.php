@props([
    'variant' => 'text',
    'width' => null,
    'height' => null,
    'rounded' => null,
    'animate' => true,
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\PackResolver;

    $variantClasses = match ($variant) {
        'text' => 'h-4',
        'heading' => 'h-6',
        'title' => 'h-8',
        'paragraph' => 'h-3',
        'avatar' => '',
        'circle' => '',
        'button' => 'h-10',
        'card' => '',
        'image' => '',
        'badge' => 'h-6',
        default => 'h-4',
    };

    $roundedClass = match (true) {
        filled($rounded) => PackResolver::rounded($rounded),
        in_array($variant, ['avatar', 'circle', 'badge'], true) => 'rounded-full',
        in_array($variant, ['button', 'card', 'image'], true) => PackResolver::rounded(neura_config('skeleton', 'rounded')),
        default => null,
    };

    $widthClasses = match ($width) {
        'full' => 'w-full',
        '3/4' => 'w-3/4',
        '1/2' => 'w-1/2',
        '1/3' => 'w-1/3',
        '1/4' => 'w-1/4',
        'xs' => 'w-16',
        'sm' => 'w-24',
        'md' => 'w-32',
        'lg' => 'w-48',
        'xl' => 'w-64',
        default => null,
    };

    $heightClasses = match ($height) {
        'xs' => 'h-4',
        'sm' => 'h-6',
        'md' => 'h-8',
        'lg' => 'h-12',
        'xl' => 'h-16',
        '2xl' => 'h-24',
        default => null,
    };

    $sizeClasses = match ($variant) {
        'avatar' => match ($height) {
            'xs' => 'w-8 h-8',
            'sm' => 'w-10 h-10',
            'md' => 'w-12 h-12',
            'lg' => 'w-16 h-16',
            'xl' => 'w-20 h-20',
            default => 'w-12 h-12',
        },
        'circle' => match ($height) {
            'xs' => 'w-8 h-8',
            'sm' => 'w-10 h-10',
            'md' => 'w-12 h-12',
            'lg' => 'w-16 h-16',
            'xl' => 'w-20 h-20',
            default => 'w-12 h-12',
        },
        default => null,
    };

    $classes = [
        'bg-surface-inset',
        $variantClasses,
        $roundedClass,
        $widthClasses,
        $heightClasses,
        $sizeClasses,
        $animate ? 'animate-pulse' : '',
    ];
@endphp

<div 
    {{ $attributes->merge(['class' => Arr::toCssClasses(array_filter($classes))]) }}
    style="@if($width && !in_array($width, ['full', '3/4', '1/2', '1/3', '1/4', 'xs', 'sm', 'md', 'lg', 'xl'])) width: {{ $width }}; @endif @if($height && !in_array($height, ['xs', 'sm', 'md', 'lg', 'xl', '2xl'])) height: {{ $height }}; @endif"
>
    {{ $slot }}
</div>

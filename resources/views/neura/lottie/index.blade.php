@props([
    'animation',
    'delay' => 0,
    'speed' => 1,
    'floatSpeed' => 1000,
    'floatAmount' => 20,
    'floatOffset' => 0,
    'rotateAmount' => 0.5,
    'position' => '',
    'size' => 'w-24 md:w-40 h-24 md:h-40',
    'opacity' => 'opacity-90',
    'scale' => 1.5,
    'monochrome' => false,
])

@php
    $floatCalc = "Math.sin(Date.now() / {$floatSpeed} + {$floatOffset}) * {$floatAmount}";
    $rotateCalc = "({$floatCalc}) * ({$rotateAmount})";
    $monochrome = filled($monochrome) && $monochrome;
    $monochromeClasses = $monochrome ? '[&_svg]:brightness-0 [&_svg]:invert' : '';
@endphp

<div
    x-data="lottieAnimation()"
    data-lottie-animation="{{ asset($animation) }}"
    data-lottie-delay="{{ $delay }}"
    data-lottie-speed="{{ $speed }}"
    {{ $attributes->merge([
        'class' => "absolute {$position} {$size} {$opacity} pointer-events-none z-0 transition-transform duration-100 ease-in-out {$monochromeClasses} [&_svg]:drop-shadow-lg [&_svg]:h-auto [&_svg]:w-auto"
    ]) }}
    x-init="$nextTick(() => { setInterval(() => { const y = {{ $floatCalc }}; $el.style.transform = `translateY(${y}px) rotate(${y * {{ $rotateAmount }}}deg) scale({{ $scale }})` }, 50) })"
></div>

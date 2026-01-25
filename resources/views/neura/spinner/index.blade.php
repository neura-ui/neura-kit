@props([
    'size' => 'md',
    'variant' => 'default',
    'color' => 'primary',
    'label' => null,
    'labelPosition' => 'right',
    'speed' => 'normal',
])

@php
    // Size classes
    $sizeClasses = match ($size) {
        'xs' => 'size-3',
        'sm' => 'size-4',
        'md' => 'size-6',
        'lg' => 'size-8',
        'xl' => 'size-10',
        '2xl' => 'size-12',
        '3xl' => 'size-16',
        default => 'size-6',
    };

    // Label size classes
    $labelSizeClasses = match ($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
        'xl' => 'text-lg',
        '2xl' => 'text-xl',
        '3xl' => 'text-2xl',
        default => 'text-sm',
    };

    // Color classes
    $colorClasses = match ($color) {
        'primary' => 'text-primary-500',
        'secondary' => 'text-neutral-500',
        'success' => 'text-green-500',
        'danger' => 'text-red-500',
        'warning' => 'text-yellow-500',
        'info' => 'text-blue-500',
        'white' => 'text-white',
        'black' => 'text-black dark:text-white',
        'current' => 'text-current',
        default => 'text-primary-500',
    };

    // Animation speed
    $speedClasses = match ($speed) {
        'slow' => 'animate-[spin_1.5s_linear_infinite]',
        'normal' => 'animate-spin',
        'fast' => 'animate-[spin_0.5s_linear_infinite]',
        default => 'animate-spin',
    };

    // Container classes for label position
    $containerClasses = match ($labelPosition) {
        'top' => 'flex flex-col items-center gap-2',
        'bottom' => 'flex flex-col-reverse items-center gap-2',
        'left' => 'flex flex-row-reverse items-center gap-2',
        'right' => 'flex flex-row items-center gap-2',
        default => 'flex flex-row items-center gap-2',
    };
@endphp

@if ($label)
    <div {{ $attributes->class([$containerClasses]) }}>
@endif

@if ($variant === 'default')
    {{-- Classic circular spinner --}}
    <svg {{ $attributes->unless($label)->class([$sizeClasses, $colorClasses, $speedClasses, 'shrink-0']) }}
        @if($label) class="{{ $sizeClasses }} {{ $colorClasses }} {{ $speedClasses }} shrink-0" @endif
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>

@elseif ($variant === 'dots')
    {{-- Bouncing dots --}}
    <div {{ $attributes->unless($label)->class(['flex items-center gap-1', $colorClasses]) }}
        @if($label) class="flex items-center gap-1 {{ $colorClasses }}" @endif>
        @php
            $dotSize = match ($size) {
                'xs' => 'size-1',
                'sm' => 'size-1.5',
                'md' => 'size-2',
                'lg' => 'size-2.5',
                'xl' => 'size-3',
                '2xl' => 'size-3.5',
                '3xl' => 'size-4',
                default => 'size-2',
            };
        @endphp
        <span class="{{ $dotSize }} rounded-full bg-current animate-[bounce_1s_ease-in-out_infinite]"></span>
        <span class="{{ $dotSize }} rounded-full bg-current animate-[bounce_1s_ease-in-out_0.2s_infinite]"></span>
        <span class="{{ $dotSize }} rounded-full bg-current animate-[bounce_1s_ease-in-out_0.4s_infinite]"></span>
    </div>

@elseif ($variant === 'pulse')
    {{-- Pulsing circle --}}
    <div {{ $attributes->unless($label)->class([$sizeClasses, $colorClasses, 'relative shrink-0']) }}
        @if($label) class="{{ $sizeClasses }} {{ $colorClasses }} relative shrink-0" @endif>
        <span class="absolute inset-0 rounded-full bg-current opacity-75 animate-ping"></span>
        <span class="relative block rounded-full bg-current {{ $sizeClasses }}"></span>
    </div>

@elseif ($variant === 'bars')
    {{-- Vertical bars --}}
    <div {{ $attributes->unless($label)->class(['flex items-end gap-0.5', $colorClasses]) }}
        @if($label) class="flex items-end gap-0.5 {{ $colorClasses }}" @endif>
        @php
            $barWidth = match ($size) {
                'xs' => 'w-0.5',
                'sm' => 'w-1',
                'md' => 'w-1.5',
                'lg' => 'w-2',
                'xl' => 'w-2.5',
                '2xl' => 'w-3',
                '3xl' => 'w-4',
                default => 'w-1.5',
            };
            $barHeight = match ($size) {
                'xs' => 'h-3',
                'sm' => 'h-4',
                'md' => 'h-6',
                'lg' => 'h-8',
                'xl' => 'h-10',
                '2xl' => 'h-12',
                '3xl' => 'h-16',
                default => 'h-6',
            };
        @endphp
        <span class="{{ $barWidth }} {{ $barHeight }} bg-current rounded-sm animate-[scaleY_1s_ease-in-out_infinite] origin-bottom" style="animation-delay: 0s;"></span>
        <span class="{{ $barWidth }} {{ $barHeight }} bg-current rounded-sm animate-[scaleY_1s_ease-in-out_infinite] origin-bottom" style="animation-delay: 0.1s;"></span>
        <span class="{{ $barWidth }} {{ $barHeight }} bg-current rounded-sm animate-[scaleY_1s_ease-in-out_infinite] origin-bottom" style="animation-delay: 0.2s;"></span>
        <span class="{{ $barWidth }} {{ $barHeight }} bg-current rounded-sm animate-[scaleY_1s_ease-in-out_infinite] origin-bottom" style="animation-delay: 0.3s;"></span>
    </div>

@elseif ($variant === 'ring')
    {{-- Ring spinner --}}
    <div {{ $attributes->unless($label)->class([$sizeClasses, $colorClasses, $speedClasses, 'shrink-0 rounded-full border-2 border-current border-t-transparent']) }}
        @if($label) class="{{ $sizeClasses }} {{ $colorClasses }} {{ $speedClasses }} shrink-0 rounded-full border-2 border-current border-t-transparent" @endif>
    </div>

@elseif ($variant === 'dual-ring')
    {{-- Dual ring spinner --}}
    <div {{ $attributes->unless($label)->class([$sizeClasses, 'relative shrink-0']) }}
        @if($label) class="{{ $sizeClasses }} relative shrink-0" @endif>
        <div class="absolute inset-0 rounded-full border-2 border-current border-t-transparent {{ $colorClasses }} {{ $speedClasses }}"></div>
        <div class="absolute inset-1 rounded-full border-2 border-current border-b-transparent {{ $colorClasses }} animate-[spin_0.8s_linear_infinite_reverse]"></div>
    </div>

@elseif ($variant === 'square')
    {{-- Rotating square --}}
    <div {{ $attributes->unless($label)->class([$sizeClasses, $colorClasses, 'shrink-0 bg-current animate-[spin_1.2s_ease-in-out_infinite] rounded-sm']) }}
        @if($label) class="{{ $sizeClasses }} {{ $colorClasses }} shrink-0 bg-current animate-[spin_1.2s_ease-in-out_infinite] rounded-sm" @endif>
    </div>

@else
    {{-- Fallback to default --}}
    <svg {{ $attributes->unless($label)->class([$sizeClasses, $colorClasses, $speedClasses, 'shrink-0']) }}
        @if($label) class="{{ $sizeClasses }} {{ $colorClasses }} {{ $speedClasses }} shrink-0" @endif
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>
@endif

@if ($label)
        <span class="{{ $labelSizeClasses }} {{ $colorClasses }}">{{ $label }}</span>
    </div>
@endif

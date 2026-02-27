@props([
    'value' => 0,
    'max' => 100,
    'size' => 'md',
    'variant' => 'default',
    'color' => 'primary',
    'showValue' => false,
    'valuePosition' => 'right',
    'label' => null,
    'rounded' => 'full',
    'animated' => false,
    'striped' => false,
    'indeterminate' => false,
])

@php
    // Calculate percentage
    $percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;

    // Size classes (height)
    $sizeClasses = match ($size) {
        'xs' => 'h-1',
        'sm' => 'h-1.5',
        'md' => 'h-2.5',
        'lg' => 'h-4',
        'xl' => 'h-5',
        '2xl' => 'h-6',
        default => 'h-2.5',
    };

    // Text size for value display
    $textSizeClasses = match ($size) {
        'xs' => 'text-xs',
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-sm',
        'xl' => 'text-base',
        '2xl' => 'text-base',
        default => 'text-sm',
    };

    // Rounded classes
    $roundedClasses = match ($rounded) {
        'none' => 'rounded-none',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'full' => 'rounded-full',
        default => 'rounded-full',
    };

    // Background track color
    $trackColorClasses = match ($variant) {
        'default' => 'bg-surface-inset',
        'soft' => 'bg-surface-inset',
        'bordered' => 'bg-transparent border border-edge',
        default => 'bg-surface-inset',
    };

    // Progress bar color
    $barColorClasses = match ($color) {
        'primary' => 'bg-primary-500',
        'secondary' => 'bg-neutral-500',
        'success' => 'bg-green-500',
        'danger' => 'bg-red-500',
        'warning' => 'bg-yellow-500',
        'info' => 'bg-blue-500',
        'gradient' => 'bg-gradient-to-r from-primary-500 to-purple-500',
        'gradient-success' => 'bg-gradient-to-r from-green-400 to-emerald-500',
        'gradient-danger' => 'bg-gradient-to-r from-red-400 to-rose-500',
        'gradient-warning' => 'bg-gradient-to-r from-yellow-400 to-orange-500',
        'gradient-info' => 'bg-gradient-to-r from-blue-400 to-cyan-500',
        default => 'bg-primary-500',
    };

    // Text color for value
    $textColorClasses = match ($color) {
        'primary' => 'text-primary-600 dark:text-primary-400',
        'secondary' => 'text-fg-secondary',
        'success' => 'text-green-600 dark:text-green-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        default => 'text-primary-600 dark:text-primary-400',
    };

    // Animation classes
    $animationClasses = '';
    if ($indeterminate) {
        $animationClasses = 'animate-[progress-indeterminate_1.5s_ease-in-out_infinite]';
    } elseif ($animated) {
        $animationClasses = 'transition-all duration-500 ease-out';
    }

    // Striped pattern
    $stripedClasses = $striped ? 'bg-stripes' : '';
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label || ($showValue && $valuePosition === 'top'))
        <div class="flex justify-between items-center mb-1.5">
            @if ($label)
                <span class="{{ $textSizeClasses }} font-medium text-fg-secondary">{{ $label }}</span>
            @endif
            @if ($showValue && $valuePosition === 'top')
                <span class="{{ $textSizeClasses }} font-medium {{ $textColorClasses }}">
                    {{ round($percentage) }}%
                </span>
            @endif
        </div>
    @endif

    <div class="flex items-center gap-3">
        <div class="flex-1 {{ $sizeClasses }} {{ $roundedClasses }} {{ $trackColorClasses }} overflow-hidden"
            role="progressbar"
            aria-valuenow="{{ $value }}"
            aria-valuemin="0"
            aria-valuemax="{{ $max }}">
            
            @if ($indeterminate)
                <div class="h-full w-1/3 {{ $barColorClasses }} {{ $roundedClasses }} {{ $stripedClasses }} {{ $animationClasses }}"></div>
            @else
                <div class="h-full {{ $barColorClasses }} {{ $roundedClasses }} {{ $stripedClasses }} {{ $animationClasses }}"
                    style="width: {{ $percentage }}%"></div>
            @endif
        </div>

        @if ($showValue && $valuePosition === 'right')
            <span class="{{ $textSizeClasses }} font-medium {{ $textColorClasses }} min-w-[3ch] text-right">
                {{ round($percentage) }}%
            </span>
        @endif
    </div>

    @if ($showValue && $valuePosition === 'bottom')
        <div class="flex justify-end mt-1">
            <span class="{{ $textSizeClasses }} font-medium {{ $textColorClasses }}">
                {{ round($percentage) }}%
            </span>
        </div>
    @endif

    @if ($showValue && $valuePosition === 'inside' && in_array($size, ['lg', 'xl', '2xl']))
        {{-- For inside positioning, we need a different layout --}}
    @endif
</div>

<style>
    .bg-stripes {
        background-image: linear-gradient(
            45deg,
            rgba(255, 255, 255, 0.15) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, 0.15) 50%,
            rgba(255, 255, 255, 0.15) 75%,
            transparent 75%,
            transparent
        );
        background-size: 1rem 1rem;
        animation: progress-stripes 1s linear infinite;
    }

    @keyframes progress-stripes {
        from {
            background-position: 1rem 0;
        }
        to {
            background-position: 0 0;
        }
    }

    @keyframes progress-indeterminate {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(400%);
        }
    }
</style>

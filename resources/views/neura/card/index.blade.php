@props([
    'size' => 'full',
    'variant' => 'default',
    'color' => null,
    'padding' => 'normal',
    'shadow' => 'sm',
    'rounded' => 'lg',
])

@php
    // Size classes
    $sizeClasses = match ($size) {
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-full',
        default => 'max-w-full',
    };

    // Padding classes
    $paddingClasses = match ($padding) {
        'none' => '[:where(&)]:p-0',
        'xs' => '[:where(&)]:px-2 [:where(&)]:py-1',
        'sm' => '[:where(&)]:px-3 [:where(&)]:py-2',
        'normal' => '[:where(&)]:px-4 [:where(&)]:py-3',
        'md' => '[:where(&)]:px-5 [:where(&)]:py-4',
        'lg' => '[:where(&)]:px-6 [:where(&)]:py-5',
        'xl' => '[:where(&)]:px-8 [:where(&)]:py-6',
        default => '[:where(&)]:px-4 [:where(&)]:py-3',
    };

    // Rounded classes
    $roundedClasses = match ($rounded) {
        'none' => '[:where(&)]:rounded-none',
        'sm' => '[:where(&)]:rounded-sm',
        'md' => '[:where(&)]:rounded-md',
        'lg' => '[:where(&)]:rounded-lg',
        'xl' => '[:where(&)]:rounded-xl',
        '2xl' => '[:where(&)]:rounded-2xl',
        '3xl' => '[:where(&)]:rounded-3xl',
        'full' => '[:where(&)]:rounded-full',
        default => '[:where(&)]:rounded-lg',
    };

    // Shadow classes — layered compound shadows for modern depth
    $shadowClasses = match ($shadow) {
        'none' => '',
        'xs' => 'shadow-[0_1px_2px_rgb(0_0_0/0.04)] dark:shadow-[0_1px_2px_rgb(0_0_0/0.2)]',
        'sm' => 'shadow-[0_1px_3px_rgb(0_0_0/0.04),0_1px_2px_-1px_rgb(0_0_0/0.03)] dark:shadow-[0_1px_3px_rgb(0_0_0/0.3),0_1px_2px_-1px_rgb(0_0_0/0.2)]',
        'md' => 'shadow-[0_4px_6px_-1px_rgb(0_0_0/0.05),0_2px_4px_-2px_rgb(0_0_0/0.04)] dark:shadow-[0_4px_6px_-1px_rgb(0_0_0/0.35),0_2px_4px_-2px_rgb(0_0_0/0.25)]',
        'lg' => 'shadow-[0_10px_15px_-3px_rgb(0_0_0/0.05),0_4px_6px_-4px_rgb(0_0_0/0.04)] dark:shadow-[0_10px_15px_-3px_rgb(0_0_0/0.4),0_4px_6px_-4px_rgb(0_0_0/0.3)]',
        'xl' => 'shadow-[0_20px_25px_-5px_rgb(0_0_0/0.06),0_8px_10px_-6px_rgb(0_0_0/0.04)] dark:shadow-[0_20px_25px_-5px_rgb(0_0_0/0.45),0_8px_10px_-6px_rgb(0_0_0/0.3)]',
        '2xl' => 'shadow-[0_25px_50px_-12px_rgb(0_0_0/0.15)] dark:shadow-[0_25px_50px_-12px_rgb(0_0_0/0.5)]',
        'inner' => 'shadow-[inset_0_2px_4px_rgb(0_0_0/0.04)] dark:shadow-[inset_0_2px_4px_rgb(0_0_0/0.2)]',
        default => 'shadow-[0_1px_3px_rgb(0_0_0/0.04),0_1px_2px_-1px_rgb(0_0_0/0.03)] dark:shadow-[0_1px_3px_rgb(0_0_0/0.3),0_1px_2px_-1px_rgb(0_0_0/0.2)]',
    };

    // Variant styles — subtle borders with ring for crispness
    $variantStyles = match ($variant) {
        'default' => [
            'bg' => 'bg-surface',
            'border' => 'border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
        ],
        'outline' => [
            'bg' => 'bg-transparent',
            'border' => 'border-2 border-black/[0.1] dark:border-white/[0.12]',
        ],
        'soft' => [
            'bg' => 'bg-surface-inset',
            'border' => 'border border-black/[0.04] dark:border-white/[0.06]',
        ],
        'elevated' => [
            'bg' => 'bg-surface',
            'border' => 'border border-black/[0.04] dark:border-white/[0.06] ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
        ],
        'flat' => [
            'bg' => 'bg-neutral-50 dark:bg-white/[0.03]',
            'border' => 'border-0',
        ],
        'ghost' => [
            'bg' => 'bg-transparent',
            'border' => 'border-0',
        ],
        default => [
            'bg' => 'bg-surface',
            'border' => 'border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03]',
        ],
    };

    // Color variants (only apply if color is specified)
    $colorClasses = [];
    if ($color) {
        $colorVariants = match ($color) {
            'primary' => [
                'bg' => $variant === 'soft' ? 'bg-primary-50 dark:bg-primary-950' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-primary-500 dark:border-primary-400' : 'border border-primary-200 dark:border-primary-800',
                'text' => 'text-primary-900 dark:text-primary-100',
            ],
            'secondary' => [
                'bg' => $variant === 'soft' ? 'bg-surface-inset' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-neutral-500 dark:border-neutral-400' : 'border border-edge',
                'text' => 'text-fg',
            ],
            'success' => [
                'bg' => $variant === 'soft' ? 'bg-green-50 dark:bg-green-950' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-green-500 dark:border-green-400' : 'border border-green-200 dark:border-green-800',
                'text' => 'text-green-900 dark:text-green-100',
            ],
            'danger' => [
                'bg' => $variant === 'soft' ? 'bg-red-50 dark:bg-red-950' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-red-500 dark:border-red-400' : 'border border-red-200 dark:border-red-800',
                'text' => 'text-red-900 dark:text-red-100',
            ],
            'warning' => [
                'bg' => $variant === 'soft' ? 'bg-yellow-50 dark:bg-yellow-950' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-yellow-500 dark:border-yellow-400' : 'border border-yellow-200 dark:border-yellow-800',
                'text' => 'text-yellow-900 dark:text-yellow-100',
            ],
            'info' => [
                'bg' => $variant === 'soft' ? 'bg-blue-50 dark:bg-blue-950' : ($variant === 'ghost' ? 'bg-transparent' : 'bg-surface'),
                'border' => $variant === 'outline' || $variant === 'bordered' ? 'border-2 border-blue-500 dark:border-blue-400' : 'border border-blue-200 dark:border-blue-800',
                'text' => 'text-blue-900 dark:text-blue-100',
            ],
            default => [],
        };

        if (!empty($colorVariants)) {
            $variantStyles['bg'] = $colorVariants['bg'];
            $variantStyles['border'] = $colorVariants['border'];
            $colorClasses[] = $colorVariants['text'] ?? '';
        }
    }

    // Elevated variant gets a deeper shadow by default
    if ($variant === 'elevated' && $shadow === 'sm') {
        $shadowClasses = 'shadow-[0_10px_15px_-3px_rgb(0_0_0/0.05),0_4px_6px_-4px_rgb(0_0_0/0.04)] dark:shadow-[0_10px_15px_-3px_rgb(0_0_0/0.4),0_4px_6px_-4px_rgb(0_0_0/0.3)]';
    }

    // Build final classes array
    $classes = [
        $variantStyles['bg'],
        $variantStyles['border'],
        $paddingClasses,
        $roundedClasses,
        $shadowClasses,
        $sizeClasses,
        ...array_filter($colorClasses),
    ];

@endphp

<div {{ $attributes->merge(['class' => Arr::toCssClasses($classes)]) }}>
    {{ $slot }}
</div>

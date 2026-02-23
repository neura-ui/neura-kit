@props([
    'value' => null,
    'record' => null,
    'column' => null,
])

@php
    use Illuminate\Support\Arr;

    $rawValue = $value ?? data_get($record, $column->key);

    if ($column?->format instanceof Closure) {
        $formatted = ($column->format)($record);
    } else {
        $formatted = $rawValue;
    }

    $colors = $column->extraAttributes['colors'] ?? [];
    $icons = $column->extraAttributes['icons'] ?? [];
    $showDot = $column->extraAttributes['showDot'] ?? true;
    $uppercase = $column->extraAttributes['uppercase'] ?? true;

    if (is_array($formatted)) {
        $label = $formatted['label'] ?? 'N/A';
        $color = $formatted['color'] ?? 'neutral';
        $icon = $formatted['icon'] ?? null;
    } else {
        $label = is_string($formatted) ? $formatted : (string) ($formatted ?? 'N/A');
        $color = $colors[$rawValue] ?? 'neutral';
        $icon = $icons[$rawValue] ?? null;
    }

    if (isset($column->extraAttributes['badgeColor'])) {
        $colorAttr = $column->extraAttributes['badgeColor'];
        $color = $colorAttr instanceof Closure ? $colorAttr($record) : $colorAttr;
    }

    if (isset($column->extraAttributes['badgeIcon'])) {
        $iconAttr = $column->extraAttributes['badgeIcon'];
        $icon = $iconAttr instanceof Closure ? $iconAttr($record) : $iconAttr;
    }

    $label = $uppercase ? ucfirst($label) : $label;

    $bgColor = match ($color) {
        'green', 'success' => 'bg-emerald-50 dark:bg-emerald-500/10',
        'yellow', 'warning' => 'bg-amber-50 dark:bg-amber-500/10',
        'orange' => 'bg-orange-50 dark:bg-orange-500/10',
        'red', 'danger' => 'bg-red-50 dark:bg-red-500/10',
        'blue', 'info', 'primary' => 'bg-blue-50 dark:bg-blue-500/10',
        'purple' => 'bg-violet-50 dark:bg-violet-500/10',
        'teal' => 'bg-teal-50 dark:bg-teal-500/10',
        default => 'bg-neutral-100 dark:bg-white/[0.06]',
    };

    $textColor = match ($color) {
        'green', 'success' => 'text-emerald-700 dark:text-emerald-400',
        'yellow', 'warning' => 'text-amber-700 dark:text-amber-400',
        'orange' => 'text-orange-700 dark:text-orange-400',
        'red', 'danger' => 'text-red-700 dark:text-red-400',
        'blue', 'info', 'primary' => 'text-blue-700 dark:text-blue-400',
        'purple' => 'text-violet-700 dark:text-violet-400',
        'teal' => 'text-teal-700 dark:text-teal-400',
        default => 'text-neutral-600 dark:text-neutral-400',
    };

    $dotColor = match ($color) {
        'green', 'success' => 'bg-emerald-500',
        'yellow', 'warning' => 'bg-amber-500',
        'orange' => 'bg-orange-500',
        'red', 'danger' => 'bg-red-500',
        'blue', 'info', 'primary' => 'bg-blue-500',
        'purple' => 'bg-violet-500',
        'teal' => 'bg-teal-500',
        default => 'bg-neutral-400 dark:bg-neutral-500',
    };
@endphp

<div class="flex items-center">
    <span @class([
        'inline-flex items-center gap-1.5',
        'rounded-md px-1.5 py-0.5',
        'text-xs font-medium leading-4',
        'whitespace-nowrap select-none',
        $bgColor,
        $textColor,
    ])>
        @if($showDot)
            <span class="size-1.5 rounded-full {{ $dotColor }}"></span>
        @endif

        @if($icon)
            <x-icon :name="$icon" class="size-3" />
        @endif

        {{ $label }}
    </span>
</div>

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

    $colorClasses = match ($color) {
        'green', 'success' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
        'yellow', 'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300',
        'orange' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300',
        'red', 'danger' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        'blue', 'info', 'primary' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
        'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300',
        'teal' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300',
        'gray', 'neutral', 'secondary' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
        default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
    };
@endphp

<div class="flex items-center">
    <span @class([
        'inline-flex items-center gap-1.5',
        'rounded-lg px-2 py-1',
        'text-sm font-base leading-4',
        'whitespace-nowrap select-none',
        $colorClasses,
    ])>
        @if($showDot)
            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
        @endif

        @if($icon)
            <x-icon :name="$icon" class="size-3.5" />
        @endif

        {{ $label }}
    </span>
</div>

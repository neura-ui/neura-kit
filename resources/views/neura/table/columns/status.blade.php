@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])
@php
    if ($column?->format instanceof Closure) {
        $value = ($column->format)($value, $row, $column);
    }
    $status = $value;
    $statusEnum = $extraAttributes['enum'] ?? null;
    $colorMap = $extraAttributes['colors'] ?? [];

    if ($statusEnum) {
        if (is_object($status)) {
            $enum = $status;
        } else {
            $enum = $statusEnum::tryFrom($status);
        }

        $label = $enum?->label() ?? (string) $status;
        $color = $enum?->color() ?? 'neutral';
    } else {
        $label = (string) $status;
        $color = $colorMap[$status] ?? 'neutral';
    }

    $dotColor = match ($color) {
        'green', 'success' => 'bg-emerald-500',
        'yellow', 'warning' => 'bg-amber-500',
        'orange' => 'bg-orange-500',
        'red', 'danger' => 'bg-red-500',
        'blue', 'info' => 'bg-blue-500',
        'purple' => 'bg-violet-500',
        'teal' => 'bg-teal-500',
        default => 'bg-neutral-400 dark:bg-neutral-500',
    };

    $textColor = match ($color) {
        'green', 'success' => 'text-emerald-700 dark:text-emerald-400',
        'yellow', 'warning' => 'text-amber-700 dark:text-amber-400',
        'orange' => 'text-orange-700 dark:text-orange-400',
        'red', 'danger' => 'text-red-700 dark:text-red-400',
        'blue', 'info' => 'text-blue-700 dark:text-blue-400',
        'purple' => 'text-violet-700 dark:text-violet-400',
        'teal' => 'text-teal-700 dark:text-teal-400',
        default => 'text-neutral-600 dark:text-neutral-400',
    };
@endphp

<div class="flex items-center gap-2">
    <span class="size-1.5 rounded-full shrink-0 {{ $dotColor }}"></span>
    <span class="text-[13px] font-normal {{ $textColor }}">{{ $label }}</span>
</div>

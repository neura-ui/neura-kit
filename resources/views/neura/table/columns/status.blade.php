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

    /**
     * Calm, table-friendly color system
     */
    $colorClasses = match ($color) {
        'green', 'success' => 'bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-300',
        'yellow', 'warning' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300',
        'orange' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300',
        'red', 'danger' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        'blue', 'info' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
        'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300',
        'teal' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300',
        default => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300',
    };
@endphp

<div class="flex items-center">
    <span
        @class([
            'inline-flex items-center gap-1.5',
            'rounded-lg px-2 py-1',
            'text-sm font-base leading-4',
            'whitespace-nowrap select-none',
            $colorClasses,
        ])
    >
        {{-- Optional dot indicator --}}
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>

        {{ $label }}
    </span>
</div>

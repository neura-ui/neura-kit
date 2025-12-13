@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $status = $value;
    $statusEnum = $extraAttributes['enum'] ?? null;
    $colorMap = $extraAttributes['colors'] ?? [];

    if ($statusEnum) {
        if (is_object($status)) {
            $enum = $status;
        } else {
            $enum = $statusEnum::tryFrom($status);
        }

        $label = $enum?->label() ?? $status;
        $color = $enum?->color() ?? 'gray';
    } else {
        $label = $status;
        $color = $colorMap[$status] ?? 'gray';
    }

    $colorClasses = match($color) {
        'yellow' => 'bg-yellow-500 dark:bg-yellow-600',
        'green' => 'bg-green-500 dark:bg-green-600',
        'orange' => 'bg-orange-500 dark:bg-orange-600',
        'red' => 'bg-red-500 dark:bg-red-600',
        'blue' => 'bg-blue-500 dark:bg-blue-600',
        'purple' => 'bg-purple-500 dark:bg-purple-600',
        'teal' => 'bg-teal-500 dark:bg-teal-600',
        default => 'bg-neutral-500 dark:bg-neutral-600',
    };
@endphp

<div class="flex">
    <div @class([
        'text-white rounded-xl px-2 py-1 uppercase font-bold text-xs',
        $colorClasses,
    ])>
        {{ $label }}
    </div>
</div>

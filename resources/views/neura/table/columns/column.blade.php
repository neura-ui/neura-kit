@props([
    'value',
    'row' => null,
    'column' => null,
    'format' => null,
    'formatUsing' => null,
])

@php
    $displayValue = $value;

    if ($format && is_callable($format)) {
        $displayValue = $format($value, $row);
    } elseif ($formatUsing) {
        $displayValue = match($formatUsing) {
            'currency' => '$' . number_format($value, 2),
            'number' => number_format($value),
            'percentage' => number_format($value, 2) . '%',
            default => $value,
        };
    }
@endphp

<div class="text-neutral-900 dark:text-neutral-100">
    {{ $displayValue }}
</div>

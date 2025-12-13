@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    static $counters = [];
    $start = $extraAttributes['start'] ?? 1;
    $key = $column->key ?? 'default';

    if (!isset($counters[$key])) {
        $counters[$key] = $start;
    }

    $displayValue = $value ?? $counters[$key];
    $counters[$key]++;
@endphp

<div class="font-mono text-sm text-neutral-600 dark:text-neutral-400">
    #{{ $displayValue }}
</div>

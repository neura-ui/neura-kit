@props([
    'value',
    'row' => null,
    'column' => null,
])

@php
    $count = 0;

    if (is_countable($value)) {
        $count = count($value);
    } elseif (is_array($value)) {
        $count = count($value);
    } elseif (is_iterable($value)) {
        $count = iterator_count($value);
    } elseif (is_numeric($value)) {
        $count = (int) $value;
    }
@endphp

<div class="flex items-center">
    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium tabular-nums bg-neutral-100 dark:bg-white/[0.06] text-neutral-600 dark:text-neutral-400">
        {{ $count }}
    </span>
</div>

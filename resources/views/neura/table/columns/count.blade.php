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
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
        {{ $count }}
    </span>
</div>

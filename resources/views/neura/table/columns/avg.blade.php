@props([
    'value',
    'row' => null,
    'column' => null,
])

@php
    $items = is_array($value) ? $value : (is_iterable($value) ? iterator_to_array($value) : []);
    $avg = count($items) > 0 ? array_sum($items) / count($items) : 0;
@endphp

<div class="font-medium">
    {{ number_format($avg, 2) }}
</div>


@props([
    'value',
    'row' => null,
    'column' => null,
    'html' => null,
])

@php
    $htmlContent = $html ?? $value;

    if ($column && $column->format && is_callable($column->format)) {
        $htmlContent = $column->format($value, $row);
    }
@endphp

<div>
    {!! $htmlContent !!}
</div>


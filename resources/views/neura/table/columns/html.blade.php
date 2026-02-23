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

<div class="text-[13px] text-neutral-900 dark:text-neutral-100 [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a]:underline [&_a]:underline-offset-2">
    {!! $htmlContent !!}
</div>

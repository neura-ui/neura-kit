@props([
    'value',
    'row' => null,
    'column' => null,
    'formatUsing' => null,
])

@php
    $date = $value ? \Carbon\Carbon::parse($value) : null;
    $format = $formatUsing ?? 'Y-m-d H:i:s';

    if ($format === 'human') {
        $displayValue = $date?->diffForHumans();
    } elseif ($format === 'date') {
        $displayValue = $date?->format('Y-m-d');
    } elseif ($format === 'time') {
        $displayValue = $date?->format('H:i:s');
    } elseif ($format === 'datetime') {
        $displayValue = $date?->format('Y-m-d H:i:s');
    } else {
        $displayValue = $date?->format($format);
    }
@endphp

<div class="text-sm">
    {{ $displayValue ?? '-' }}
</div>


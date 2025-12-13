@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $relation = $extraAttributes['relation'] ?? null;
    $attribute = $extraAttributes['attribute'] ?? 'name';
    $fallback = $extraAttributes['fallback'] ?? __('No data');

    $displayValue = $fallback;

    if ($relation && $row) {
        $related = $row->{$relation} ?? null;
        if ($related) {
            if (is_object($related) && isset($related->{$attribute})) {
                $displayValue = $related->{$attribute};
            } elseif (is_array($related) && isset($related[$attribute])) {
                $displayValue = $related[$attribute];
            } elseif (is_string($related)) {
                $displayValue = $related;
            }
        }
    } elseif ($value) {
        $displayValue = is_object($value) && isset($value->{$attribute})
            ? $value->{$attribute}
            : (is_array($value) && isset($value[$attribute])
                ? $value[$attribute]
                : $value);
    }
@endphp

<div>
    {{ $displayValue }}
</div>


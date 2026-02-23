@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $relation = $extraAttributes['relation'] ?? null;
    $attribute = $extraAttributes['attribute'] ?? 'name';
    $fallback = $extraAttributes['fallback'] ?? null;

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

<div class="text-[13px] text-neutral-900 dark:text-neutral-100">
    @if($displayValue)
        {{ $displayValue }}
    @else
        <span class="text-neutral-300 dark:text-neutral-600 text-xs">—</span>
    @endif
</div>

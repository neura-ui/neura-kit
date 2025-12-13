@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $model = $extraAttributes['model'] ?? null;
    $attribute = $extraAttributes['attribute'] ?? 'name';
    $fallback = $extraAttributes['fallback'] ?? __('No data');

    $displayValue = $fallback;

    if ($model && $value && class_exists($model)) {
        $related = null;

        if (is_numeric($value)) {
            $related = $model::find($value);
        } else {

            $related = $model::where('name', $value)->first();
        }

        if ($related && isset($related->{$attribute})) {
            $displayValue = $related->{$attribute};
        }
    } elseif ($value) {
        $displayValue = $value;
    }
@endphp

<div>
    {{ $displayValue }}
</div>

@props([
    'value',
    'row' => null,
    'column' => null,
    'extraAttributes' => [],
])

@php
    $model = $extraAttributes['model'] ?? null;
    $attribute = $extraAttributes['attribute'] ?? 'name';
    $fallback = $extraAttributes['fallback'] ?? null;

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

<div class="text-[13px] text-neutral-900 dark:text-neutral-100">
    @if($displayValue)
        {{ $displayValue }}
    @else
        <span class="text-neutral-300 dark:text-neutral-600 text-xs">—</span>
    @endif
</div>

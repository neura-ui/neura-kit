@props([
    'value',
    'row' => null,
])

<div>
    {{ \Carbon\Carbon::make($value)->diffForHumans() }}
</div>


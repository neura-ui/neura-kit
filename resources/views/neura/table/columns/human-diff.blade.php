@php use Carbon\Carbon; @endphp
@props([
    'value',
    'row' => null,
])

<div>
    @if($value)
        {{ Carbon::make($value)->diffForHumans() }}
    @else
        <span class="text-gray-400 dark:text-gray-500">—</span>
    @endif
</div>

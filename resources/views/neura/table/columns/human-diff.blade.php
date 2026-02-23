@php use Carbon\Carbon; @endphp
@props([
    'value',
    'row' => null,
])

<div class="text-[13px] text-neutral-500 dark:text-neutral-400">
    @if($value)
        {{ Carbon::make($value)->diffForHumans() }}
    @else
        <span class="text-neutral-300 dark:text-neutral-600">—</span>
    @endif
</div>

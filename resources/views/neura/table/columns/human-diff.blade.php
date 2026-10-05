@php use Carbon\Carbon; @endphp
@props([
    'value',
    'row' => null,
])

<div class="nk-table-muted">
    @if($value)
        {{ Carbon::make($value)->diffForHumans() }}
    @else
        <span class="nk-table-dash">—</span>
    @endif
</div>

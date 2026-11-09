@props([
    'columns' => 3,
])

{{-- A row of figures, `columns` across. --}}
<div {{ $attributes->class(['nk-hover-card-stats'])->merge(['style' => '--nk-hover-card-columns: '.max(1, (int) $columns)]) }}>
    {{ $slot }}
</div>

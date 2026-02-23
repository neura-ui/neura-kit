@props([
    'value',
    'row' => null,
    'column' => null,
])

@php
    $items = is_array($value) ? $value : (is_iterable($value) ? iterator_to_array($value) : []);
@endphp

<div class="flex flex-wrap gap-1">
    @forelse($items as $item)
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-xs font-medium bg-neutral-100 dark:bg-white/[0.06] text-neutral-600 dark:text-neutral-400">
            {{ is_array($item) ? json_encode($item) : $item }}
        </span>
    @empty
        <span class="text-neutral-300 dark:text-neutral-600 text-xs">—</span>
    @endforelse
</div>

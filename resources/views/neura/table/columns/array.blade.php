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
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
            {{ is_array($item) ? json_encode($item) : $item }}
        </span>
    @empty
        <span class="text-neutral-400 dark:text-neutral-500 text-sm">{{ __('No items') }}</span>
    @endforelse
</div>

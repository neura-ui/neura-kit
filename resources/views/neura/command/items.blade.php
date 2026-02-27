@props([])

<div
    x-show="showItems && (visibleCount > 0 || !search.trim())"
    class="overflow-y-auto max-h-96 mt-2"
    {{ $attributes->merge(['class' => 'space-y-1']) }}
>
    {{ $slot }}
</div>

<div
    x-show="showItems && visibleCount === 0 && search.trim().length > 0"
    class="py-8 text-center text-sm text-neutral-500 dark:text-neutral-400"
>
    No results found
</div>

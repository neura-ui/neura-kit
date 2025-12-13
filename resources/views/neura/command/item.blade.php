@props([
    'icon' => null,
    'kbd' => null,
])

@php
    $icon = filled($icon) ? $icon : null;
    $kbd = filled($kbd) ? $kbd : null;
    $itemText = trim(strip_tags($slot->toHtml()));
@endphp

<button
    type="button"
    data-command-item
    data-item-text="{{ strtolower($itemText) }}"
    x-show="true"
    x-bind:class="{
        'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 border-neutral-200 dark:border-neutral-700': activeIndex === parseInt($el.getAttribute('data-visible-index') || -1),
        'hover:bg-neutral-100 dark:hover:bg-neutral-800': activeIndex !== parseInt($el.getAttribute('data-visible-index') || -1),
    }"
    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-md text-left text-sm text-neutral-900 dark:text-neutral-100 border border-transparent transition-colors"
    {{ $attributes }}
>
    @if($icon)
        <neura::icon :name="$icon" class="size-4 shrink-0" />
    @endif
    
    <span class="flex-1">{{ $slot }}</span>
    
    @if($kbd)
        <kbd class="px-2 py-1 text-xs font-medium bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300 rounded border border-neutral-300 dark:border-neutral-600">
            {{ $kbd }}
        </kbd>
    @endif
</button>

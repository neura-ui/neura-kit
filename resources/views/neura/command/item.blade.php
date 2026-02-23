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
        'bg-active text-fg border-edge': activeIndex === parseInt($el.getAttribute('data-visible-index') || -1),
        'hover:bg-hover': activeIndex !== parseInt($el.getAttribute('data-visible-index') || -1),
    }"
    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-md text-left text-sm text-fg border border-transparent transition-colors"
    {{ $attributes }}
>
    @if($icon)
        <neura::icon :name="$icon" class="size-4 shrink-0" />
    @endif
    
    <span class="flex-1">{{ $slot }}</span>
    
    @if($kbd)
        <kbd class="px-2 py-1 text-xs font-medium bg-surface-inset text-fg-muted rounded border border-edge">
            {{ $kbd }}
        </kbd>
    @endif
</button>

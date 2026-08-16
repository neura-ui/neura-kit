@props([
    'collapsable' => false,
    'icon' => null,
    'size' => 'md',
    'groupId' => null,
    'label' => null,
    'density' => 'default',
])

@php
    use Illuminate\View\ComponentSlot;

    $hasLabel = $label instanceof ComponentSlot
        ? $label->isNotEmpty()
        : filled($label);

    $isCompact = $density === 'compact';

    $labelClasses = match (true) {
        $isCompact && $size === 'sm' => 'text-[0.625rem] px-3 py-0.5',
        $isCompact && $size === 'lg' => 'text-sm px-3 py-1.5',
        $isCompact => 'text-xs px-3 py-1',
        $size === 'sm' => 'text-[0.625rem] px-3 py-1',
        $size === 'lg' => 'text-sm px-3 py-2',
        default => 'text-xs px-3 py-1.5',
    };

    $fontClass = $isCompact ? 'font-semibold' : 'font-medium';
    $iconClass = $isCompact ? 'size-3.5 shrink-0 text-fg-disabled' : 'size-4 shrink-0 text-fg-disabled';
    $chevronClass = $isCompact
        ? 'size-3 shrink-0 text-fg-disabled transition-transform duration-200'
        : 'size-3.5 shrink-0 text-fg-disabled transition-transform duration-200';
@endphp

@if ($hasLabel)
    <div class="in-[:has([data-collapsed]_&)]:hidden" data-slot="navlist-group-label">
        @if ($collapsable)
            <button
                x-on:click="expand()"
                x-bind:aria-expanded="expanded"
                type="button"
                @if($groupId) id="{{ $groupId }}" @endif
                class="
                    flex items-center justify-between w-full
                    {{ $labelClasses }}
                    {{ $fontClass }} uppercase tracking-wider
                    text-fg-muted
                    hover:text-neutral-700 dark:hover:text-neutral-300
                    transition-colors duration-150
                    cursor-pointer rounded-md
                    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50
                "
            >
                <span class="flex items-center gap-2 min-w-0">
                    @if ($icon)
                        <neura::icon :name="$icon" class="{{ $iconClass }}"/>
                    @endif
                    {{ $label }}
                </span>
                <neura::icon
                    name="chevron-right"
                    class="{{ $chevronClass }}"
                    x-bind:class="expanded ? 'rotate-90' : 'rotate-0'"
                />
            </button>
        @else
            <span
                @if($groupId) id="{{ $groupId }}" @endif
                class="
                    flex items-center gap-2 min-w-0
                    {{ $labelClasses }}
                    {{ $fontClass }} uppercase tracking-wider
                    text-fg-muted
                "
            >
                @if ($icon)
                    <neura::icon :name="$icon" class="{{ $iconClass }}"/>
                @endif
                {{ $label }}
            </span>
        @endif
    </div>
@endif

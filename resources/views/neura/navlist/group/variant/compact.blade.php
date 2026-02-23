@aware([
    'collapsable' => true,
    'label' => null,
    'icon' => null,
    'size' => 'md',
    'groupId' => null,
])

@php
    $labelClasses = match ($size) {
        'sm' => 'text-[0.625rem] px-3 py-0.5',
        'lg' => 'text-sm px-3 py-1.5',
        default => 'text-xs px-3 py-1',
    };

    $gapClass = match ($size) {
        'sm' => 'gap-y-0.5',
        'lg' => 'gap-y-1.5',
        default => 'gap-y-0.5',
    };
@endphp

{{-- Group header --}}
@if ($label)
    <div class="in-[:has([data-collapsed]_&)]:hidden">
        @if ($collapsable)
            <button
                x-on:click="expand()"
                x-bind:aria-expanded="expanded"
                type="button"
                @if($groupId) id="{{ $groupId }}" @endif
                class="
                    flex items-center justify-between w-full
                    {{ $labelClasses }}
                    font-semibold uppercase tracking-wider
                    text-fg-muted
                    hover:text-neutral-700 dark:hover:text-neutral-300
                    transition-colors duration-150
                    cursor-pointer rounded-md
                    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50
                "
            >
                <span class="flex items-center gap-2">
                    @if ($icon)
                        <neura::icon :name="$icon" class="size-3.5 text-fg-disabled"/>
                    @endif
                    {{ $label }}
                </span>
                <neura::icon
                    name="chevron-right"
                    class="size-3 text-fg-disabled transition-transform duration-200"
                    x-bind:class="expanded ? 'rotate-90' : 'rotate-0'"
                />
            </button>
        @else
            <span
                @if($groupId) id="{{ $groupId }}" @endif
                class="
                    flex items-center gap-2
                    {{ $labelClasses }}
                    font-semibold uppercase tracking-wider
                    text-fg-muted
                "
            >
                @if ($icon)
                    <neura::icon :name="$icon" class="size-3.5 text-fg-disabled"/>
                @endif
                {{ $label }}
            </span>
        @endif
    </div>
@endif

{{-- Group items --}}
<ul
    @if ($collapsable)
        x-show="expanded"
        x-collapse
    @endif
    role="list"
    class="
        flex flex-col {{ $gapClass }}
        ml-2 rtl:mr-2
        in-[:has([data-collapsed]_&)]:ml-0
        in-[:has([data-collapsed]_&)]:items-center
    "
>
    {{ $slot }}
</ul>

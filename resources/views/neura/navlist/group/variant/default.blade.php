@aware([
    'collapsable' => true,
    'label' => null,
    'icon' => null,
    'size' => 'md',
    'groupId' => null,
])

@php
    $labelClasses = match ($size) {
        'sm' => 'text-[0.625rem] px-3 py-1',
        'lg' => 'text-sm px-3 py-2',
        default => 'text-xs px-3 py-1.5',
    };

    $gapClass = match ($size) {
        'sm' => 'gap-y-0.5',
        'lg' => 'gap-y-1.5',
        default => 'gap-y-0.5',
    };

    $indentClass = $icon ? 'ml-5 rtl:mr-5' : 'ml-3 rtl:mr-3';
@endphp

{{-- Group label --}}
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
                    font-medium uppercase tracking-wider
                    text-fg-muted
                    hover:text-neutral-700 dark:hover:text-neutral-300
                    transition-colors duration-150
                    cursor-pointer rounded-md
                    focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50
                "
            >
                <span class="flex items-center gap-2">
                    @if ($icon)
                        <neura::icon :name="$icon" class="size-4 text-fg-disabled"/>
                    @endif
                    {{ $label }}
                </span>
                <neura::icon
                    name="chevron-right"
                    class="size-3.5 text-fg-disabled transition-transform duration-200"
                    x-bind:class="expanded ? 'rotate-90' : 'rotate-0'"
                />
            </button>
        @else
            <span
                @if($groupId) id="{{ $groupId }}" @endif
                class="
                    flex items-center gap-2
                    {{ $labelClasses }}
                    font-medium uppercase tracking-wider
                    text-fg-muted
                "
            >
                @if ($icon)
                    <neura::icon :name="$icon" class="size-4 text-fg-disabled"/>
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
        {{ $indentClass }} pl-3 rtl:pr-3
        border-l border-edge
        in-[:has([data-collapsed]_&)]:border-0
        in-[:has([data-collapsed]_&)]:pl-0
        in-[:has([data-collapsed]_&)]:ml-0
        in-[:has([data-collapsed]_&)]:items-center
    "
>
    {{ $slot }}
</ul>

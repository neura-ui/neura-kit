@props([
    'collapsable' => false,
    'icon' => null,
    'size' => 'md',
    'groupId' => null,
    'label' => null,
])

@php
    use Illuminate\View\ComponentSlot;

    $hasLabel = $label instanceof ComponentSlot
        ? $label->isNotEmpty()
        : filled($label);

    $gapClass = match ($size) {
        'sm' => 'gap-y-1',
        'lg' => 'gap-y-2',
        default => 'gap-y-1',
    };
@endphp

<div
    data-slot="navlist-group-card"
    class="flex flex-col gap-y-1 rounded-lg border border-edge/70 bg-surface-inset/80 p-1.5"
>
    <neura::navlist.group.label
        :collapsable="$collapsable"
        :icon="$icon"
        :size="$size"
        :group-id="$groupId"
        density="default"
    >
        @if ($hasLabel)
            <x-slot:label>{{ $label }}</x-slot:label>
        @endif
    </neura::navlist.group.label>

    <ul
        @if ($collapsable)
            x-show="expanded"
            x-collapse
        @endif
        role="list"
        class="flex flex-col {{ $gapClass }} in-[:has([data-collapsed]_&)]:items-center"
    >
        {{ $slot }}
    </ul>
</div>

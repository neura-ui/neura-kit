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

<div @class([
    'pb-2 mb-0.5',
    'border-b border-edge' => $hasLabel,
])>
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
</div>

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

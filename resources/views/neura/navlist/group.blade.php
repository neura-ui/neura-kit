@props([
    'collapsable' => false,
    'variant' => 'default',
    'size' => 'md',
    'label' => null,
    'icon' => null,
])

@php
    $groupId = 'navlist-group-' . \Illuminate\Support\Str::random(8);
@endphp

<div
    {{ $attributes->class('flex flex-col') }}
    data-slot="navlist-group"
    role="group"
    aria-labelledby="{{ $groupId }}"
    x-data="{ expanded: true, expand() { this.expanded = !this.expanded } }"
>
    @switch($variant)
        @case('compact')
            <neura::navlist.group.variant.compact
                :collapsable="$collapsable"
                :label="$label"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                {{ $slot }}
            </neura::navlist.group.variant.compact>
            @break

        @default
            <neura::navlist.group.variant.default
                :collapsable="$collapsable"
                :label="$label"
                :icon="$icon"
                :size="$size"
                :group-id="$groupId"
            >
                {{ $slot }}
            </neura::navlist.group.variant.default>
    @endswitch
</div>

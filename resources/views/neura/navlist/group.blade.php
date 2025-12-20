@props([
    'collapsable' => false,
    'variant' => 'default',
    'label' => null,
    'icon' => null,
])

<div
    {{ $attributes->class('flex flex-col gap-y-1') }}
    data-slot="navlist-group"
    x-data="{ expanded: true, expand() { this.expanded = !this.expanded } }"
>
    @switch($variant)
        @case('compact')
            <neura::navlist.group.variant.compact
                :collapsable="$collapsable"
                :label="$label"
                :icon="$icon"
            >
                {{ $slot }}
            </neura::navlist.group.variant.compact>
            @break

        @default
            <neura::navlist.group.variant.default
                :collapsable="$collapsable"
                :label="$label"
                :icon="$icon"
            >
                {{ $slot }}
            </neura::navlist.group.variant.default>
    @endswitch
</div>

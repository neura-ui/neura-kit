@props([
    'collapsable' => false,
    'variant' => 'default',
    'label' => false
])

@php

$classes = [
    'flex flex-col gap-y-1'
];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="navlist-group"
    x-data="{
        expanded: true,
        expand(){
            this.expanded = !this.expanded;
        }
    }"
>
    @switch($variant)
        @case('compact')
            <neura::navlist.group.variant.compact
                :$collapsable
            >
                {{ $slot }}
            </neura::navlist.group.variant.compact>
            @break
        @default
            <neura::navlist.group.variant.default
                :$collapsable
            >
                {{ $slot }}
            </neura::navlist.group.variant.default>
    @endswitch
</div>
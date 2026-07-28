{{-- Grid tile, rendered inside `<template x-for="entry in entries">`. --}}
@props([
    'sizes' => [],
    'colors' => [],
    'sprite' => 'nk-fm',
    'selectable' => true,
    'sortable' => true,
])

@php
    use Illuminate\Support\Arr;

    $tileClasses = Arr::toCssClasses([
        'group/entry relative flex cursor-default flex-col items-center rounded-xl border text-center outline-none transition-colors',
        $sizes['tile'],
        $colors['tile']['base'],
        $colors['tile']['selected'],
        $colors['tile']['focused'],
        // Grid reorder cues sit on the left/right edges (list uses top/bottom).
        $sortable ? 'data-[drop=before]:shadow-[inset_2px_0_0_0] data-[drop=before]:shadow-primary-500 data-[drop=after]:shadow-[inset_-2px_0_0_0] data-[drop=after]:shadow-primary-500' : null,
        $sortable ? ($colors['drop']['inside'] ?? '') : null,
        $sortable ? ($colors['drop']['dragging'] ?? '') : null,
        $sortable ? 'cursor-grab active:cursor-grabbing' : null,
    ]);
@endphp

<li
    :data-entry="entry.id"
    :data-kind="entry.kind"
    :data-selected="isSelected(entry.id) || null"
    :data-focused="focusId === entry.id || null"
    :data-dragging="isDragged(entry.id) || null"
    :data-drop="dropHint(entry.id)"
    :aria-selected="isSelected(entry.id).toString()"
    role="option"
    class="{{ $tileClasses }}"
    @if ($sortable)
        :draggable="canSortEntries"
        x-on:dragstart="onItemDragStart($event, entry)"
        x-on:dragend="onItemDragEnd()"
        x-on:dragover.prevent="onItemDragOver($event, entry)"
        x-on:dragleave="onItemDragLeave($event, entry)"
        x-on:drop.prevent.stop="onItemDrop($event, entry)"
    @endif
    x-on:click="select(entry, $event)"
    x-on:dblclick="open(entry)"
    x-on:contextmenu.prevent.stop="openMenu($event, entry)"
>
    @if ($selectable)
        <input
            type="checkbox"
            class="absolute start-2 top-2 size-3.5 rounded border-edge accent-primary-600 transition-opacity focus:ring-primary-500/30"
            :class="isSelected(entry.id) ? '' : 'opacity-0 group-hover/entry:opacity-100 focus:opacity-100'"
            :checked="isSelected(entry.id)"
            x-on:click.stop
            x-on:change="toggle(entry)"
            :aria-label="entry.name"
        />
    @endif

    {{-- Preview and glyph share one fixed-height media box, so every tile keeps
         the same compact rhythm whatever it holds. --}}
    <template x-if="entry.thumbnail">
        <span class="block w-full overflow-hidden rounded-lg bg-hover ring-1 ring-edge {{ $sizes['media'] }}">
            <img
                :src="entry.thumbnail"
                :alt="entry.name"
                loading="lazy"
                decoding="async"
                class="size-full object-cover"
                draggable="false"
            />
        </span>
    </template>

    <template x-if="!entry.thumbnail">
        <span class="flex w-full items-center justify-center {{ $sizes['media'] }}">
            <svg class="{{ $sizes['thumb'] }} shrink-0 {{ $colors['kind'] }}" aria-hidden="true">
                <use :href="`#{{ $sprite }}-${icon(entry)}`" />
            </svg>
        </span>
    </template>

    <span class="w-full min-w-0">
        <span class="block w-full truncate {{ $sizes['label'] }} {{ $colors['name'] }}" x-text="entry.name" :title="entry.name"></span>
        <span class="mt-0.5 block {{ $sizes['meta'] }} text-fg-muted" x-text="entry.sizeLabel"></span>
    </span>
</li>

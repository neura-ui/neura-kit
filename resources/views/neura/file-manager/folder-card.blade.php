{{-- A folder in the grid: a compact card, rendered inside `<template x-for="entry in folderEntries">`. --}}
@props([
    'sizes' => [],
    'colors' => [],
    'sprite' => 'nk-fm',
    'selectable' => true,
    'sortable' => true,
    'starrable' => false,
    'actionsWidth' => 'w-16',
])

@php
    use Illuminate\Support\Arr;

    $tileClasses = Arr::toCssClasses([
        'group/entry relative flex h-14 cursor-default items-center gap-3 rounded-xl border px-3 text-start outline-none transition-[border-color,box-shadow,background-color]',
        $colors['tile']['base'],
        $colors['tile']['selected'],
        $colors['tile']['focused'],
        // Grid reorder cues sit on the left/right edges (list uses top/bottom).
        $sortable ? 'data-[drop=before]:shadow-[inset_2px_0_0_0_var(--nk-fm-accent)] data-[drop=after]:shadow-[inset_-2px_0_0_0_var(--nk-fm-accent)]' : null,
        $sortable ? ($colors['drop']['inside'] ?? '') : null,
        $sortable ? ($colors['drop']['dragging'] ?? '') : null,
        $sortable ? 'cursor-grab active:cursor-grabbing' : null,
    ]);

    $hoverAction = 'inline-flex size-7 items-center justify-center rounded-lg bg-surface/95 text-fg shadow-sm ring-1 ring-edge transition-opacity opacity-0 group-hover/entry:opacity-100 focus-visible:opacity-100 group-data-[selected]/entry:opacity-100';
@endphp

<li
    :data-entry="entry.id"
    :data-kind="entry.kind"
    :data-selected="isSelected(entry.id) || null"
    :data-focused="focusId === entry.id || null"
    :data-dragging="isDragged(entry.id) || null"
    :data-drop="dropHint(entry.id)"
    :data-pending="entry.id === '__new__' || null"
    :data-cut="isCut(entry.id) || null"
    :aria-selected="isSelected(entry.id).toString()"
    role="option"
    class="{{ $tileClasses }}"
    @if ($sortable)
        :draggable="canSortEntries && !editing"
        x-on:dragstart="onItemDragStart($event, entry)"
        x-on:dragend="onItemDragEnd()"
        x-on:dragover.prevent="onItemDragOver($event, entry)"
        x-on:dragleave="onItemDragLeave($event, entry)"
        x-on:drop.prevent.stop="onItemDrop($event, entry)"
    @endif
    x-on:pointerdown="onPressStart($event, entry)"
    x-on:click="select(entry, $event)"
    x-on:dblclick="open(entry)"
    x-on:contextmenu.prevent.stop="openMenu($event, entry)"
>
    <span class="nk-fm-badge size-8" :data-kind="entry.kind" aria-hidden="true">
        <svg class="size-4"><use :href="`#{{ $sprite }}-${icon(entry)}`" /></svg>
    </span>
    <span class="flex min-w-0 flex-1 flex-col">
        <neura::file-manager.name-field />
        <span class="truncate {{ $sizes['label'] }} font-medium {{ $colors['name'] }}" x-show="!isEditing(entry.id)" x-text="entry.name" :title="entry.name"></span>
        <span class="truncate {{ $sizes['meta'] }} text-fg-muted" x-text="entry.sizeLabel"></span>
    </span>
    @if ($starrable)
            <button type="button" class="{{ $hoverAction }}" :class="entry.starred && '!opacity-100 text-amber-500'" x-on:click.stop="toggleStar(entry)"
                :aria-pressed="(!!entry.starred).toString()"
                :aria-label="(entry.starred ? @js(neura_trans('removeFromFavorites')) : @js(neura_trans('addToFavorites'))) + ' — ' + entry.name">
                <neura::icon name="star" variant="solid" class="size-4" x-show="entry.starred" />
                <neura::icon name="star" class="size-4" x-show="!entry.starred" />
            </button>
        @endif
        <button type="button" data-more class="{{ $hoverAction }}" x-on:click.stop="openMenuAt(entry, $el)"
        :aria-label="@js(neura_trans('moreActions')) + ' — ' + entry.name" title="{{ neura_trans('moreActions') }}">
        <neura::icon name="ellipsis-horizontal" class="size-4" />
    </button>
</li>

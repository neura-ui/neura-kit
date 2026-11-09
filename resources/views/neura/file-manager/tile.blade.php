{{-- Grid tile, rendered inside `<template x-for="entry in entries">`. --}}
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
        'group/entry relative flex cursor-default flex-col overflow-hidden rounded-xl border text-start outline-none transition-[border-color,box-shadow,background-color]',
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
    @if ($selectable)
        <input
            type="checkbox"
            class="absolute start-2 top-2 z-10 size-4 rounded border-edge accent-[var(--nk-fm-accent)] transition-opacity"
            :class="isSelected(entry.id) ? '' : 'opacity-0 group-hover/entry:opacity-100 focus:opacity-100'"
            :checked="isSelected(entry.id)"
            x-on:click.stop
            x-on:change="toggle(entry)"
            :aria-label="entry.name"
        />
    @endif

    {{-- The preview: the picture itself, a video still with its length, or a tile tinted
         by the type with its large icon and its extension. --}}
    <span class="relative block aspect-[4/3] w-full overflow-hidden" :data-kind="entry.kind">
        <template x-if="entry.preview.src">
            <img :src="entry.preview.src" alt="" loading="lazy" decoding="async" draggable="false" class="size-full object-cover" />
        </template>
        <template x-if="!entry.preview.src">
            <span class="nk-fm-tile flex size-full items-center justify-center" :data-kind="entry.kind" aria-hidden="true">
                <svg class="size-10"><use :href="`#{{ $sprite }}-${icon(entry)}`" /></svg>
            </span>
        </template>
        <template x-if="entry.preview.kind === 'video'">
            <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                <span class="flex size-10 items-center justify-center rounded-full bg-white/90 text-neutral-900 shadow">
                    <svg viewBox="0 0 24 24" class="ms-0.5 size-4" fill="currentColor"><path d="M8 5v14l11-7Z" /></svg>
                </span>
            </span>
        </template>
        <span
            class="absolute bottom-2 end-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[0.6875rem] font-medium tabular-nums text-white"
            x-show="entry.preview.duration"
            x-text="entry.preview.duration"
        ></span>
        <span
            class="nk-fm-tile-label absolute bottom-2 start-2 rounded-md px-1.5 py-0.5 text-[0.625rem] font-semibold tracking-wide"
            :data-kind="entry.kind"
            x-show="!entry.preview.src && entry.badge"
            x-text="entry.badge"
        ></span>

        <span class="absolute end-2 top-2 flex gap-1">
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
        </span>
    </span>

    <span class="flex w-full min-w-0 flex-col gap-0.5 px-2.5 py-2">
        <neura::file-manager.name-field />
        <span class="block w-full truncate {{ $sizes['label'] }} font-medium {{ $colors['name'] }}" x-show="!isEditing(entry.id)" x-text="entry.name" :title="entry.name"></span>
        <span class="block truncate {{ $sizes['meta'] }} text-fg-muted" :title="entry.modifiedTitle" x-text="entry.sizeLabel + ' · ' + entry.modifiedLabel"></span>
    </span>
</li>

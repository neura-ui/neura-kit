{{-- List row, rendered inside `<template x-for="entry in entries">`. --}}
@props([
    'sizes' => [],
    'colors' => [],
    'sprite' => 'nk-fm',
    'selectable' => true,
    'multiple' => true,
    'downloadable' => true,
    'deletable' => true,
    'sortable' => true,
    'starrable' => false,
    'actionsWidth' => 'w-16',
])

@php
    use Illuminate\Support\Arr;

    $rowClasses = Arr::toCssClasses([
        'group/entry flex w-full cursor-default items-center border-b border-edge outline-none transition-colors last:border-0',
        $sizes['row'],
        $colors['entry']['base'],
        $colors['entry']['selected'],
        $colors['entry']['focused'],
        $sortable ? ($colors['drop']['before'] ?? '') : null,
        $sortable ? ($colors['drop']['after'] ?? '') : null,
        $sortable ? ($colors['drop']['inside'] ?? '') : null,
        $sortable ? ($colors['drop']['dragging'] ?? '') : null,
        $sortable ? 'cursor-grab active:cursor-grabbing' : null,
    ]);

    $actionClasses = Arr::toCssClasses([
        'inline-flex shrink-0 items-center justify-center rounded-md transition-colors',
        'opacity-0 group-hover/entry:opacity-100 focus-visible:opacity-100 group-data-[selected]/entry:opacity-100',
        $sizes['action'],
        $colors['action'],
    ]);
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
    class="{{ $rowClasses }}"
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
    @if ($selectable && $multiple)
        <span class="flex w-6 shrink-0 items-center">
            <input
                type="checkbox"
                class="size-3.5 rounded border-edge accent-[var(--nk-fm-accent)] transition-opacity"
                :class="isSelected(entry.id) ? '' : 'opacity-0 group-hover/entry:opacity-100 focus:opacity-100'"
                :checked="isSelected(entry.id)"
                x-on:click.stop
                x-on:change="toggle(entry)"
                :aria-label="entry.name"
            />
        </span>
    @endif

    <span class="flex min-w-0 flex-1 items-center gap-2.5">
        {{-- The type in a tinted badge; a picture shows itself in it. --}}
        <span class="nk-fm-badge {{ $sizes['badge'] }}" :data-kind="entry.kind" aria-hidden="true">
            <template x-if="entry.preview.kind === 'image' && entry.preview.src">
                <img :src="entry.preview.src" alt="" loading="lazy" decoding="async" draggable="false" />
            </template>
            <template x-if="!(entry.preview.kind === 'image' && entry.preview.src)">
                <svg class="{{ $sizes['badgeIcon'] }}"><use :href="`#{{ $sprite }}-${icon(entry)}`" /></svg>
            </template>
        </span>

        <neura::file-manager.name-field />
        <span class="flex min-w-0 flex-1 flex-col justify-center" x-show="!isEditing(entry.id)">
            <button
                type="button"
                class="min-w-0 truncate text-start {{ $sizes['label'] }} {{ $colors['name'] }}"
                x-text="entry.name"
                :title="entry.name"
                x-on:click.stop="select(entry, $event)"
                x-on:dblclick.stop="open(entry)"
            ></button>

            {{-- Whole-tree results say where they live, and jump there on click. --}}
            <template x-if="isGlobalSearch && entry.pathNames">
                <button
                    type="button"
                    class="mt-0.5 flex min-w-0 items-center gap-1 truncate text-start {{ $sizes['meta'] }} text-fg-muted transition-colors hover:text-fg"
                    x-on:click.stop="reveal(entry)"
                    :title="@js(neura_trans('revealInFolder'))"
                >
                    <neura::icon name="folder" class="size-3 shrink-0" />
                    <span class="truncate" x-text="[rootLabel, ...entry.pathNames].join(' / ')"></span>
                </button>
            </template>
        </span>
    </span>

    <span class="hidden w-28 shrink-0 truncate {{ $sizes['meta'] }} text-fg-muted @[36rem]/list:block" x-text="entry.typeLabel"></span>

    <span class="hidden w-24 shrink-0 tabular-nums {{ $sizes['meta'] }} text-fg-muted @[30rem]/list:block" x-text="entry.sizeLabel"></span>

    <span class="hidden w-36 shrink-0 truncate {{ $sizes['meta'] }} text-fg-muted @[44rem]/list:block" x-text="entry.modifiedLabel" :title="entry.modifiedTitle"></span>

    <span class="flex {{ $actionsWidth }} shrink-0 items-center justify-end gap-0.5">
        @if ($starrable)
            <button
                type="button"
                class="{{ $actionClasses }}"
                :class="entry.starred && '!opacity-100 text-amber-500'"
                x-on:click.stop="toggleStar(entry)"
                :aria-pressed="(!!entry.starred).toString()"
                :aria-label="(entry.starred ? @js(neura_trans('removeFromFavorites')) : @js(neura_trans('addToFavorites'))) + ' — ' + entry.name"
            >
                <neura::icon name="star" variant="solid" class="{{ $sizes['glyph'] }}" x-show="entry.starred" />
                <neura::icon name="star" class="{{ $sizes['glyph'] }}" x-show="!entry.starred" />
            </button>
        @endif
        <button
            type="button"
            class="{{ $actionClasses }}"
            data-more
            x-on:click.stop="openMenuAt(entry, $el)"
            :aria-label="@js(neura_trans('moreActions')) + ' — ' + entry.name"
            title="{{ neura_trans('moreActions') }}"
        >
            <neura::icon name="ellipsis-horizontal" class="{{ $sizes['glyph'] }}" />
        </button>

        <template x-if="entry.isFolder">
            <button
                type="button"
                class="{{ $actionClasses }}"
                x-on:click.stop="open(entry)"
                :aria-label="entry.name"
            >
                <neura::icon name="chevron-right" class="{{ $sizes['glyph'] }}" />
            </button>
        </template>

        <template x-if="!entry.isFolder && @js($downloadable)">
            <button
                type="button"
                class="{{ $actionClasses }}"
                x-on:click.stop="action('download', entry)"
                :aria-label="@js(neura_trans('download')) + ' — ' + entry.name"
            >
                <neura::icon name="arrow-down-tray" class="{{ $sizes['glyph'] }}" />
            </button>
        </template>
    </span>
</li>

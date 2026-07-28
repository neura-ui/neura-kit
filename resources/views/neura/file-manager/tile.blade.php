{{-- Grid tile, rendered inside `<template x-for="entry in entries">`. --}}
@props([
    'sizes' => [],
    'colors' => [],
    'sprite' => 'nk-fm',
    'selectable' => true,
])

@php
    use Illuminate\Support\Arr;

    $tileClasses = Arr::toCssClasses([
        'group/entry relative flex cursor-default flex-col items-center rounded-xl border text-center outline-none transition-colors',
        $sizes['tile'],
        $colors['tile']['base'],
        $colors['tile']['selected'],
        $colors['tile']['focused'],
    ]);
@endphp

<li
    :data-entry="entry.id"
    :data-kind="entry.kind"
    :data-selected="isSelected(entry.id) || null"
    :data-focused="focusId === entry.id || null"
    :aria-selected="isSelected(entry.id).toString()"
    role="option"
    class="{{ $tileClasses }}"
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

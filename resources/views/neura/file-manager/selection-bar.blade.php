{{-- The selection's actions, floating at the foot of the files. --}}
@props([
    'sizes' => [],
    'downloadable' => true,
    'deletable' => true,
    'renamable' => true,
])

@php
    $button = 'inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-[0.8125rem] font-medium transition-colors hover:bg-white/10';
@endphp

<div
    data-fm-selection-bar
    role="toolbar"
    aria-label="{{ neura_trans('selectionActions') }}"
    class="nk-fm-selection-bar"
    x-show="selected.length > 0 && !touchSelect"
    x-cloak
    x-on:click.stop
    x-on:mousedown.stop
>
    <span class="me-1 whitespace-nowrap ps-2 text-[0.8125rem] font-medium">
        <span x-text="selected.length"></span> {{ neura_trans('selected') }}<span class="font-normal opacity-70" x-show="selectionSize !== '0 B'"> · <span x-text="selectionSize"></span></span>
    </span>

    @if ($downloadable)
        <button type="button" class="{{ $button }}" x-on:click="action('download')">
            <neura::icon name="arrow-down-tray" class="size-4" />
            <span class="hidden @[36rem]/fm:inline">{{ neura_trans('download') }}</span>
        </button>
    @endif

    <span class="relative">
        <button type="button" class="{{ $button }}" x-on:click="movePicker = true" :aria-expanded="movePicker.toString()">
            <neura::icon name="folder-arrow-down" class="size-4" />
            <span class="hidden @[36rem]/fm:inline">{{ neura_trans('move') }}</span>
        </button>
        <neura::file-manager.move-picker />
    </span>

    @if ($renamable)
        <button type="button" class="{{ $button }}" x-show="selected.length === 1" x-on:click="startRename(selectedEntries[0])">
            <neura::icon name="pencil-square" class="size-4" />
            <span class="hidden @[36rem]/fm:inline">{{ neura_trans('rename') }}</span>
        </button>
    @endif

    @if ($deletable)
        <button type="button" class="{{ $button }} text-red-300 hover:text-red-200" x-on:click="action('delete')">
            <neura::icon name="trash" class="size-4" />
            <span class="hidden @[36rem]/fm:inline">{{ neura_trans('delete') }}</span>
        </button>
    @endif

    <span class="mx-0.5 h-5 w-px bg-white/20" aria-hidden="true"></span>
    <button type="button" class="inline-flex size-8 items-center justify-center rounded-lg text-white/70 transition-colors hover:bg-white/10 hover:text-white" x-on:click="clearSelection()" aria-label="{{ neura_trans('clearSelection') }}">
        <neura::icon name="x-mark" class="size-4" />
    </button>
</div>

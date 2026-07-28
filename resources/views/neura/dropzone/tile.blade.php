{{--
    Gallery tile rendered inside `<template x-for="item in previews">`.
    Used when `previewMode="grid"` (the default for multiple image uploads).
--}}
@props([
    'sizes' => [],
    'colors' => [],
    'rounded' => 'rounded-lg',
    'removable' => true,
])

@php
    use Illuminate\Support\Arr;

    $tileClasses = Arr::toCssClasses([
        'group/item group/tile relative aspect-square overflow-hidden border transition-colors duration-200 motion-reduce:transition-none',
        $rounded,
        $colors['item']['base'],
        $colors['item']['error'],
    ]);

    $overlayActionClasses = Arr::toCssClasses([
        'inline-flex items-center justify-center rounded-full bg-black/55 text-white backdrop-blur-sm transition',
        'hover:bg-black/75 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
        'opacity-0 group-hover/tile:opacity-100 group-focus-within/tile:opacity-100',
        $sizes['action'],
    ]);
@endphp

<li
    data-slot="dropzone-tile"
    :data-status="item.status"
    :data-kind="item.kind"
    class="{{ $tileClasses }}"
>
    <template x-if="item.url">
        <img
            :src="item.url"
            :alt="item.name"
            loading="lazy"
            decoding="async"
            class="size-full object-cover"
        />
    </template>

    <template x-if="!item.url">
        <div class="flex size-full flex-col items-center justify-center gap-1.5">
            <neura::icon name="document" class="size-6 text-fg-disabled" />
            <span
                class="text-[0.625rem] font-bold uppercase tracking-wide {{ $colors['kind'] }}"
                x-text="item.extension"
            ></span>
        </div>
    </template>

    @if ($removable)
        <button
            type="button"
            class="{{ $overlayActionClasses }} absolute end-1.5 top-1.5 z-10"
            x-on:click.stop="item.status === 'uploading' ? cancel(item.uuid) : removeByUuid(item.uuid)"
            :aria-label="@js(neura_trans('removeFile')) + ' — ' + item.name"
        >
            <neura::icon name="x-mark" class="{{ $sizes['actionGlyph'] }}" />
        </button>
    @endif

    <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-linear-to-t from-black/80 via-black/40 to-transparent px-2 pb-1.5 pt-6">
        <p class="truncate text-[0.6875rem] font-medium text-white" x-text="item.name" :title="item.name"></p>

        <template x-if="item.status === 'uploading' || item.status === 'idle'">
            <div
                class="mt-1 w-full overflow-hidden rounded-full bg-white/25 {{ $sizes['bar'] }}"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="item.progress"
                :aria-label="item.name"
            >
                <div
                    class="h-full rounded-full bg-white transition-[width] duration-300 ease-out motion-reduce:transition-none"
                    :style="`width: ${item.progress}%`"
                ></div>
            </div>
        </template>
    </div>

    <template x-if="item.status === 'success'">
        <span class="absolute start-1.5 top-1.5 inline-flex items-center justify-center rounded-full bg-success-500 p-0.5 text-white shadow-sm">
            <neura::icon name="check" class="size-3" />
        </span>
    </template>

    <template x-if="item.status === 'error'">
        <div class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-1.5 bg-danger-950/75 p-2 text-center backdrop-blur-[1px]">
            <neura::icon name="exclamation-circle" class="size-5 text-danger-200" />
            <p class="line-clamp-2 text-[0.625rem] text-danger-100" x-text="item.error"></p>
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md bg-white/15 px-2 py-1 text-[0.625rem] font-medium text-white transition hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                x-on:click.stop="retry(item.uuid)"
            >
                <neura::icon name="arrow-path" class="size-3" />
                {{ neura_trans('retry') }}
            </button>
        </div>
    </template>
</li>

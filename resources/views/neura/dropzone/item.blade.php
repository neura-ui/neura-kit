{{--
    Preview row rendered inside `<template x-for="item in previews">`.
    Every visual state is driven by `data-status` / `data-kind` on the row.
--}}
@props([
    'sizes' => [],
    'colors' => [],
    'rounded' => 'rounded-lg',
    'removable' => true,
])

@php
    use Illuminate\Support\Arr;

    $itemClasses = Arr::toCssClasses([
        'group/item flex items-start border transition-colors duration-200 motion-reduce:transition-none',
        $rounded,
        $sizes['item'],
        $colors['item']['base'],
        $colors['item']['error'],
    ]);

    $actionClasses = Arr::toCssClasses([
        'inline-flex shrink-0 items-center justify-center rounded-lg transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',
        $sizes['action'],
        $colors['action']['base'],
    ]);

    $dangerActionClasses = Arr::toCssClasses([
        'inline-flex shrink-0 items-center justify-center rounded-lg transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-500',
        $sizes['action'],
        $colors['action']['danger'],
    ]);
@endphp

<li
    data-slot="dropzone-item"
    :data-status="item.status"
    :data-kind="item.kind"
    class="{{ $itemClasses }}"
>
    <template x-if="item.url">
        <img
            :src="item.url"
            :alt="item.name"
            loading="lazy"
            decoding="async"
            class="{{ $sizes['thumb'] }} shrink-0 object-cover {{ $colors['item']['thumb'] }}"
        />
    </template>

    <template x-if="!item.url">
        <span class="{{ $sizes['thumb'] }} flex shrink-0 items-center justify-center {{ $colors['item']['thumb'] }}">
            <span
                class="text-[0.625rem] font-bold uppercase tracking-wide {{ $colors['kind'] }}"
                x-text="item.extension"
            ></span>
        </span>
    </template>

    <div class="min-w-0 flex-1">
        <div class="flex items-start gap-2">
            <p class="{{ $sizes['label'] }} min-w-0 flex-1 truncate font-medium text-fg" x-text="item.name" :title="item.name"></p>

            @if ($removable)
                <div class="flex shrink-0 items-center gap-1">
                    <template x-if="item.status === 'error'">
                        <button
                            type="button"
                            class="{{ $actionClasses }}"
                            x-on:click.stop="retry(item.uuid)"
                            :aria-label="@js(neura_trans('retry')) + ' — ' + item.name"
                        >
                            <neura::icon name="arrow-path" class="{{ $sizes['actionGlyph'] }}" />
                        </button>
                    </template>

                    <template x-if="item.status === 'uploading'">
                        <button
                            type="button"
                            class="{{ $dangerActionClasses }}"
                            x-on:click.stop="cancel(item.uuid)"
                            :aria-label="@js(neura_trans('cancelUpload')) + ' — ' + item.name"
                        >
                            <neura::icon name="stop" class="{{ $sizes['actionGlyph'] }}" />
                        </button>
                    </template>

                    <template x-if="item.status !== 'uploading'">
                        <button
                            type="button"
                            class="{{ $actionClasses }}"
                            x-on:click.stop="removeByUuid(item.uuid)"
                            :aria-label="@js(neura_trans('removeFile')) + ' — ' + item.name"
                        >
                            <neura::icon name="x-mark" class="{{ $sizes['actionGlyph'] }}" />
                        </button>
                    </template>
                </div>
            @endif
        </div>

        <p class="{{ $sizes['meta'] }} mt-0.5 flex flex-wrap items-center gap-x-1.5">
            <span class="text-fg-muted" x-text="item.size"></span>
            <span class="text-fg-disabled" aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-1 font-medium {{ $colors['status'] }}">
                <template x-if="item.status === 'success'">
                    <neura::icon name="check-circle" class="size-3.5" />
                </template>
                <span x-text="statusLabel(item)"></span>
            </span>
        </p>

        <template x-if="item.status === 'uploading' || item.status === 'idle'">
            <div
                class="mt-2 w-full overflow-hidden rounded-full {{ $sizes['bar'] }} {{ $colors['bar']['track'] }}"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="item.progress"
                :aria-label="item.name"
            >
                <div
                    class="h-full rounded-full transition-[width] duration-300 ease-out motion-reduce:transition-none {{ $colors['bar']['fill'] }}"
                    :style="`width: ${item.progress}%`"
                ></div>
            </div>
        </template>

        <template x-if="item.status === 'error' && item.error">
            <p class="{{ $sizes['meta'] }} mt-1 text-danger-600 dark:text-danger-400" x-text="item.error"></p>
        </template>
    </div>
</li>

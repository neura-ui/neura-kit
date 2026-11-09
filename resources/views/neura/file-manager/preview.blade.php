{{-- Quick look: the file over the file manager, from one to the next. --}}
@props([
    'downloadable' => true,
    'sprite' => 'nk-fm',
])

@php
    $control = 'inline-flex size-9 items-center justify-center rounded-lg text-neutral-200 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-white/70';
@endphp

<div
    data-fm-preview
    role="dialog"
    aria-modal="true"
    :aria-label="previewEntry ? previewEntry.name : ''"
    tabindex="-1"
    x-ref="preview"
    class="nk-fm-preview absolute inset-0 z-50 flex flex-col overflow-hidden rounded-[inherit] bg-neutral-950 text-neutral-100"
    x-show="previewEntry"
    x-cloak
    x-on:keydown.stop="onPreviewKey($event)"
    x-on:click.stop
    x-on:contextmenu.stop
>
    <template x-if="previewEntry">
        <div class="flex min-h-0 flex-1 flex-col">
            {{-- What the file is, and what can be done with it. --}}
            <div class="flex shrink-0 items-center gap-3 border-b border-white/10 py-2.5 ps-4 pe-2">
                <span class="nk-fm-badge size-8" :data-kind="previewEntry.kind" aria-hidden="true">
                    <svg class="size-4"><use :href="`#{{ $sprite }}-${icon(previewEntry)}`" /></svg>
                </span>
                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-sm font-semibold" x-text="previewEntry.name"></span>
                    <span class="truncate text-xs text-neutral-400" x-text="previewMeta()"></span>
                </span>
                <span class="hidden shrink-0 text-xs tabular-nums text-neutral-400 @[32rem]/fm:inline" x-text="previewPosition()"></span>
                @if ($downloadable)
                    <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-white/15 px-3 text-xs font-medium text-neutral-100 transition-colors hover:bg-white/10" x-on:click="action('download', previewEntry)">
                        <neura::icon name="arrow-down-tray" class="size-4" />
                        <span class="hidden @[36rem]/fm:inline">{{ neura_trans('download') }}</span>
                    </button>
                @endif
                <button type="button" class="inline-flex h-9 items-center rounded-lg bg-neutral-100 px-3 text-xs font-semibold text-neutral-900 transition-colors hover:bg-white" x-on:click="emit('open', { item: raw(previewEntry), path: [...path] })">
                    {{ neura_trans('open') }}
                </button>
                <button type="button" class="{{ $control }}" x-on:click="closePreview()" aria-label="{{ neura_trans('closePreview') }}">
                    <neura::icon name="x-mark" class="size-5" />
                </button>
            </div>

            {{-- The file itself. --}}
            <div class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden p-6" x-on:wheel="onPreviewWheel($event)">
                <button type="button" class="{{ $control }} absolute start-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10" x-on:click="previewStep(-1)" :disabled="previewIndex() <= 0" aria-label="{{ neura_trans('previous') }}">
                    <neura::icon name="chevron-left" class="size-5" />
                </button>

                <template x-if="previewMode === 'image'">
                    <img :src="previewEntry.url" :alt="previewEntry.name" draggable="false"
                        class="max-h-full max-w-full select-none object-contain shadow-2xl"
                        :class="previewZoom > 1 ? 'cursor-grab active:cursor-grabbing' : ''"
                        :style="{ transform: `translate(${previewPan.x}px, ${previewPan.y}px) scale(${previewZoom})` }"
                        x-on:pointerdown="startPreviewPan($event)"
                        x-on:error="previewFailed = true" />
                </template>
                <template x-if="previewMode === 'thumbnail'">
                    <img :src="previewEntry.thumbnail" :alt="previewEntry.name" draggable="false" class="max-h-full max-w-full object-contain shadow-2xl" x-on:error="previewFailed = true" />
                </template>
                <template x-if="previewMode === 'video'">
                    <video :src="previewEntry.url" :poster="previewEntry.thumbnail || null" controls preload="metadata" class="max-h-full max-w-full rounded-md bg-black shadow-2xl" x-on:error="previewFailed = true"></video>
                </template>
                <template x-if="previewMode === 'audio'">
                    <audio :src="previewEntry.url" controls preload="metadata" class="w-full max-w-md" x-on:error="previewFailed = true"></audio>
                </template>
                <template x-if="previewMode === 'pdf'">
                    <iframe :src="previewEntry.url" :title="previewEntry.name" class="size-full rounded-md bg-white shadow-2xl"></iframe>
                </template>
                <template x-if="previewMode === 'text'">
                    <pre class="size-full overflow-auto rounded-md bg-neutral-900 p-4 font-mono text-xs leading-relaxed whitespace-pre-wrap text-neutral-200" x-text="previewText"></pre>
                </template>
                <template x-if="previewMode === 'loading'">
                    <neura::spinner variant="ring" size="md" color="current" />
                </template>
                <template x-if="previewMode === 'none'">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <span class="nk-fm-badge size-20 rounded-2xl" :data-kind="previewEntry.kind" aria-hidden="true">
                            <svg class="size-10"><use :href="`#{{ $sprite }}-${icon(previewEntry)}`" /></svg>
                        </span>
                        <p class="text-sm text-neutral-300">{{ neura_trans('noPreview') }}</p>
                        @if ($downloadable)
                            <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white/10 px-3 text-xs font-medium hover:bg-white/15" x-on:click="action('download', previewEntry)">
                                <neura::icon name="arrow-down-tray" class="size-4" /> {{ neura_trans('download') }}
                            </button>
                        @endif
                    </div>
                </template>

                <button type="button" class="{{ $control }} absolute end-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/10" x-on:click="previewStep(1)" :disabled="previewIndex() >= previewList.length - 1" aria-label="{{ neura_trans('next') }}">
                    <neura::icon name="chevron-right" class="size-5" />
                </button>

                <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-0.5 rounded-xl border border-white/10 bg-neutral-950/85 p-1" x-show="previewMode === 'image'">
                    <button type="button" class="{{ $control }} size-8" x-on:click="zoomPreview(-1)" aria-label="{{ neura_trans('zoomOut') }}"><neura::icon name="minus" class="size-4" /></button>
                    <span class="w-12 text-center text-xs tabular-nums" x-text="Math.round(previewZoom * 100) + ' %'"></span>
                    <button type="button" class="{{ $control }} size-8" x-on:click="zoomPreview(1)" aria-label="{{ neura_trans('zoomIn') }}"><neura::icon name="plus" class="size-4" /></button>
                    <button type="button" class="h-8 rounded-lg px-2.5 text-xs hover:bg-white/10" x-on:click="fitPreview()">{{ neura_trans('fit') }}</button>
                </div>
            </div>

            {{-- The other files of the folder. --}}
            <div class="hidden shrink-0 items-center justify-center gap-2 overflow-x-auto border-t border-white/10 px-4 py-2.5 @[32rem]/fm:flex" role="list" aria-label="{{ neura_trans('filesTitle') }}">
                <template x-for="(item, index) in previewList" :key="item.id">
                    <button
                        type="button"
                        role="listitem"
                        class="relative h-12 w-16 shrink-0 overflow-hidden rounded-md transition-opacity"
                        :class="item.id === previewing ? 'opacity-100 ring-2 ring-white' : 'opacity-50 hover:opacity-80'"
                        :aria-current="item.id === previewing ? 'true' : null"
                        :aria-label="item.name"
                        x-on:click="openPreview(item)"
                    >
                        <template x-if="item.preview.src">
                            <img :src="item.preview.src" alt="" loading="lazy" class="size-full object-cover" />
                        </template>
                        <template x-if="!item.preview.src">
                            <span class="nk-fm-tile flex size-full items-center justify-center" :data-kind="item.kind" aria-hidden="true">
                                <svg class="size-5"><use :href="`#{{ $sprite }}-${icon(item)}`" /></svg>
                            </span>
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>

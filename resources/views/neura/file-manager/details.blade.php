{{-- Side panel describing the focused entry. --}}
@props([
    'sizes' => [],
    'colors' => [],
    'sprite' => 'nk-fm',
    'downloadable' => true,
])

<aside
    x-show="detailsOpen"
    x-cloak
    class="hidden shrink-0 flex-col border-s border-edge bg-surface-raised/40 lg:flex {{ $sizes['panel'] }}"
    aria-label="{{ neura_trans('details') }}"
>
    <div class="flex items-center justify-between gap-2 border-b border-edge px-3 py-2">
        <p class="{{ $sizes['meta'] }} font-semibold uppercase tracking-wide text-fg-muted">
            {{ neura_trans('details') }}
        </p>
        <button
            type="button"
            class="inline-flex size-6 items-center justify-center rounded-md text-fg-muted transition-colors hover:bg-active hover:text-fg"
            x-on:click="detailsOpen = false"
            :aria-label="@js(neura_trans('close'))"
        >
            <neura::icon name="x-mark" class="size-3.5" />
        </button>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto p-3">
        <template x-if="!detailed">
            <p class="{{ $sizes['meta'] }} py-8 text-center text-fg-muted">
                {{ neura_trans('selectItemForDetails') }}
            </p>
        </template>

        <template x-if="detailed">
            <div class="space-y-4" :data-kind="detailed.kind">
                <div class="flex flex-col items-center gap-2 rounded-xl border border-edge bg-surface p-4 text-center">
                    <template x-if="detailed.thumbnail">
                        <img :src="detailed.thumbnail" :alt="detailed.name" class="size-20 rounded-lg object-cover ring-1 ring-edge" />
                    </template>

                    <template x-if="!detailed.thumbnail">
                        <svg class="size-12 text-fg-muted" aria-hidden="true">
                            <use :href="`#{{ $sprite }}-${icon(detailed)}`" />
                        </svg>
                    </template>

                    <p class="w-full break-words {{ $sizes['label'] }} font-medium text-fg" x-text="detailed.name"></p>
                    <p class="{{ $sizes['meta'] }} uppercase tracking-wide text-fg-muted" x-text="detailed.extension || detailed.kind"></p>
                </div>

                <dl class="space-y-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="{{ $sizes['meta'] }} text-fg-muted">{{ neura_trans('size') }}</dt>
                        <dd class="{{ $sizes['meta'] }} font-medium text-fg" x-text="detailed.sizeLabel"></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="{{ $sizes['meta'] }} text-fg-muted">{{ neura_trans('modified') }}</dt>
                        <dd class="{{ $sizes['meta'] }} font-medium text-fg" x-text="detailed.modifiedLabel"></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="{{ $sizes['meta'] }} text-fg-muted">{{ neura_trans('type') }}</dt>
                        <dd class="{{ $sizes['meta'] }} font-medium capitalize text-fg" x-text="detailed.kind"></dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="{{ $sizes['meta'] }} shrink-0 text-fg-muted">{{ neura_trans('path') }}</dt>
                        <dd class="{{ $sizes['meta'] }} truncate font-medium text-fg" x-text="trail.map((crumb) => crumb.name).join(' / ')"></dd>
                    </div>
                </dl>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-edge bg-surface px-2 py-1.5 {{ $sizes['meta'] }} font-medium text-fg-secondary transition-colors hover:bg-hover hover:text-fg"
                        x-on:click="open(detailed)"
                    >
                        <neura::icon name="folder-open" class="size-3.5" x-show="detailed.isFolder" />
                        <neura::icon name="eye" class="size-3.5" x-show="!detailed.isFolder" />
                        {{ neura_trans('open') }}
                    </button>

                    @if ($downloadable)
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-edge bg-surface px-2 py-1.5 {{ $sizes['meta'] }} font-medium text-fg-secondary transition-colors hover:bg-hover hover:text-fg"
                            x-show="!detailed.isFolder"
                            x-on:click="action('download', detailed)"
                        >
                            <neura::icon name="arrow-down-tray" class="size-3.5" />
                            {{ neura_trans('download') }}
                        </button>
                    @endif
                </div>
            </div>
        </template>
    </div>
</aside>

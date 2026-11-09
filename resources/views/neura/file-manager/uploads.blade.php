{{-- Uploads: each file sent, its progress as the app reports it, and what can be done. --}}
<section
    data-fm-uploads
    aria-live="polite"
    aria-label="{{ neura_trans('uploads') }}"
    class="nk-fm-uploads"
    x-show="uploadsOpen && uploads.length > 0"
    x-cloak
    x-on:click.stop
>
    <div class="flex h-10 items-center gap-1 border-b border-edge bg-surface-raised ps-3.5 pe-1.5">
        <span class="flex-1 truncate text-[0.8125rem] font-semibold" x-text="uploadTitle"></span>
        <button type="button" class="inline-flex size-7 items-center justify-center rounded-md text-fg-muted hover:bg-hover hover:text-fg"
            x-on:click="uploadsCollapsed = !uploadsCollapsed" :aria-expanded="(!uploadsCollapsed).toString()" aria-label="{{ neura_trans('collapse') }}">
            <span class="inline-flex transition-transform" :class="uploadsCollapsed && 'rotate-180'"><neura::icon name="chevron-down" class="size-4" /></span>
        </button>
        <button type="button" class="inline-flex size-7 items-center justify-center rounded-md text-fg-muted hover:bg-hover hover:text-fg disabled:opacity-40"
            x-on:click="closeUploads()" :disabled="uploadSummaryNow.active > 0" aria-label="{{ neura_trans('close') }}">
            <neura::icon name="x-mark" class="size-4" />
        </button>
    </div>

    <ul class="flex max-h-56 flex-col gap-3 overflow-y-auto px-3.5 py-3" x-show="!uploadsCollapsed">
        <template x-for="upload in uploads" :key="upload.id">
            <li class="flex flex-col gap-1.5" :data-status="upload.status">
                <div class="flex items-center gap-2 text-[0.8125rem]">
                    <span class="min-w-0 flex-1 truncate" :class="upload.status === 'failed' && 'text-danger-600 dark:text-danger-400'" x-text="upload.name" :title="upload.name"></span>

                    <span class="shrink-0 text-xs text-fg-muted" x-show="upload.status === 'waiting'">{{ neura_trans('waiting') }}</span>
                    <span class="shrink-0 text-xs tabular-nums text-fg-secondary" x-show="upload.status === 'sending'" x-text="upload.progress + ' %'"></span>
                    <span class="shrink-0 text-xs text-fg-muted" x-show="upload.status === 'cancelled'">{{ neura_trans('cancelled') }}</span>
                    <span class="shrink-0 text-success-600 dark:text-success-400" x-show="upload.status === 'done'" role="img" aria-label="{{ neura_trans('uploaded') }}">
                        <neura::icon name="check" class="size-4" />
                    </span>
                    <span class="shrink-0 text-xs text-danger-600 dark:text-danger-400" x-show="upload.status === 'failed'" x-text="upload.message || @js(neura_trans('failed'))"></span>

                    <button type="button" class="inline-flex size-6 shrink-0 items-center justify-center rounded-md text-fg-muted hover:bg-hover hover:text-fg"
                        x-show="upload.status === 'waiting' || upload.status === 'sending'"
                        x-on:click="cancelUpload(upload.id)"
                        :aria-label="@js(neura_trans('cancelUpload')) + ' — ' + upload.name">
                        <neura::icon name="x-mark" class="size-3.5" />
                    </button>
                    <button type="button" class="h-6 shrink-0 rounded-md border border-edge px-2 text-xs font-medium hover:bg-hover"
                        x-show="upload.status === 'failed'"
                        x-on:click="retryUpload(upload.id)">
                        {{ neura_trans('retry') }}
                    </button>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-hover" x-show="upload.status === 'sending'">
                    <div class="h-full rounded-full bg-[var(--nk-fm-accent)] transition-[width] duration-300" :style="{ width: upload.progress + '%' }"></div>
                </div>
            </li>
        </template>
    </ul>
</section>

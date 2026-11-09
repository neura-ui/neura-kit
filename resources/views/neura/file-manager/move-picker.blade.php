{{-- "Move to": the folders of the tree, the top one first. --}}
<div
    data-fm-move-picker
    role="dialog"
    aria-label="{{ neura_trans('moveTo') }}"
    class="absolute bottom-full start-0 z-40 mb-2 flex max-h-72 w-60 flex-col overflow-hidden rounded-xl border border-edge bg-surface text-fg shadow-xl"
    x-show="movePicker"
    x-cloak
    x-on:click.outside="movePicker = false"
    x-on:keydown.escape.stop="movePicker = false"
>
    <p class="border-b border-edge px-3 py-2 text-[0.6875rem] font-semibold uppercase tracking-wide text-fg-muted">{{ neura_trans('moveTo') }}</p>
    <ul class="flex flex-col gap-px overflow-y-auto p-1">
        <li>
            <button type="button" class="nk-fm-tree-row w-full" :disabled="path.length === 0" x-on:click="moveTo(null)">
                <span class="nk-fm-badge size-6 rounded-md" data-kind="folder" aria-hidden="true"><neura::icon name="home" class="size-3.5" /></span>
                <span class="truncate" x-text="rootLabel"></span>
            </button>
        </li>
        <template x-for="row in moveRows" :key="row.path.join('/')">
            <li>
                <button
                    type="button"
                    class="nk-fm-tree-row w-full disabled:opacity-40"
                    :style="{ paddingInlineStart: (row.depth * 0.875 + 0.375) + 'rem' }"
                    :disabled="!canMoveTo(row.id)"
                    x-on:click="moveTo(row.id)"
                >
                    <span class="nk-fm-badge size-6 rounded-md" data-kind="folder" aria-hidden="true"><neura::icon name="folder" class="size-3.5" /></span>
                    <span class="truncate" x-text="row.name"></span>
                </button>
            </li>
        </template>
    </ul>
</div>

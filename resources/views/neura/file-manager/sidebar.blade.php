{{-- Side panel: the app's shortcuts (Recent, Starred…) and the folder tree. --}}
@props([
    'sizes' => [],
    'shortcuts' => [],
])

<aside
    data-fm-sidebar
    class="nk-fm-sidebar flex shrink-0 flex-col gap-0.5 overflow-y-auto border-e border-edge bg-surface-raised/50 p-2 {{ $sizes['sidebar'] }}"
    :data-open="sidebarOpen || null"
    aria-label="{{ neura_trans('foldersTree') }}"
    x-on:keydown.escape.stop="sidebarOpen = false"
>
    @forelse ($shortcuts as $index => $shortcut)
        <button
            type="button"
            class="nk-fm-side-item"
            :aria-current="activeShortcut === @js((string) $shortcut['id']) ? 'page' : null"
            x-on:click="pickShortcut(@js((string) $shortcut['id']))"
        >
            <neura::icon :name="$shortcut['icon'] ?? ($index === 0 ? 'home' : 'folder')" class="size-4 shrink-0" />
            <span class="truncate">{{ $shortcut['label'] ?? $shortcut['id'] }}</span>
        </button>
    @empty
        <button type="button" class="nk-fm-side-item" :aria-current="path.length === 0 ? 'page' : null" x-on:click="goTo(null); sidebarOpen = false">
            <neura::icon name="home" class="size-4 shrink-0" />
            <span class="truncate">{{ neura_trans('home') }}</span>
        </button>
    @endforelse

    <p class="mx-2 mb-1 mt-4 text-[0.6875rem] font-semibold uppercase tracking-wide text-fg-muted" x-show="treeRows.length > 0">{{ neura_trans('folders') }}</p>

    <ul role="tree" aria-label="{{ neura_trans('foldersTree') }}" class="flex flex-col gap-px">
        <template x-for="row in treeRows" :key="row.path.join('/')">
            <li
                role="treeitem"
                :aria-expanded="row.hasChildren ? row.expanded.toString() : null"
                :aria-selected="isOpenFolder(row).toString()"
                class="nk-fm-tree-row"
                :data-current="isOpenFolder(row) || null"
                :data-drop="treeDrop === row.id || null"
                :style="{ paddingInlineStart: (row.depth * 0.875 + 0.25) + 'rem' }"
                x-on:dragover="onTreeDragOver($event, row)"
                x-on:dragleave="treeDrop === row.id && (treeDrop = null)"
                x-on:drop.prevent.stop="onTreeDrop($event, row)"
            >
                <button
                    type="button"
                    class="inline-flex size-5 shrink-0 items-center justify-center rounded text-fg-muted hover:text-fg"
                    :class="row.hasChildren ? '' : 'invisible'"
                    x-on:click.stop="toggleFolder(row.id)"
                    :aria-label="row.expanded ? @js(neura_trans('collapse')) : @js(neura_trans('expand'))"
                >
                    <span class="inline-flex transition-transform" :class="row.expanded && 'rotate-90'">
                        <neura::icon name="chevron-right" class="size-3.5" />
                    </span>
                </button>
                <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-start" x-on:click="openFolder(row.path)">
                    <span class="nk-fm-badge size-6 rounded-md" data-kind="folder" aria-hidden="true">
                        <neura::icon name="folder" class="size-3.5" />
                    </span>
                    <span class="truncate" x-text="row.name"></span>
                    <span class="ms-auto shrink-0 text-[0.6875rem] text-[var(--nk-fm-accent-ink)]" x-show="treeDrop === row.id" x-cloak>{{ neura_trans('dropHere') }}</span>
                </button>
            </li>
        </template>
    </ul>
</aside>

{{-- Tree node item --}}
<div
    class="relative flex items-center cursor-pointer transition-colors duration-150 group {{ $sizeClasses['padding'] }} {{ $sizeClasses['gap'] }} {{ $variantClasses['item'] }}"
    :class="{
        '{{ $variantClasses['selected'] }}': isSelected(item.id),
        'ring-2 ring-primary-500 ring-inset': isDropTarget(item.id),
    }"
    :style="'padding-left: ' + (item.level * indent + 8) + 'px'"
    @if($draggable)
        :draggable="true"
        x-on:dragstart="onDragStart($event, item)"
        x-on:dragend="onDragEnd($event)"
        x-on:dragover="onDragOver($event, item)"
        x-on:dragleave="onDragLeave($event)"
        x-on:drop="onDrop($event, item)"
    @endif
    x-on:click="selectItem(item.id, $event)"
    role="treeitem"
    :aria-expanded="item.type === 'folder' ? isExpanded(item.id) : undefined"
    :aria-selected="isSelected(item.id)"
>
    {{-- Expand/Collapse toggle --}}
    <template x-if="item.type === 'folder'">
        <button
            type="button"
            class="flex items-center justify-center shrink-0 {{ $sizeClasses['icon'] }} text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 transition-transform duration-200"
            :class="{ 'rotate-90': isExpanded(item.id) }"
            x-on:click.stop="toggleExpand(item.id)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </template>
    <template x-if="item.type !== 'folder'">
        <div class="{{ $sizeClasses['icon'] }} shrink-0"></div>
    </template>

    {{-- Icon --}}
    @if($showIcons)
        <span class="{{ $sizeClasses['icon'] }} shrink-0 {{ $colorClasses }}" :class="item.iconClass">
            {{-- Folder icon --}}
            <template x-if="item.type === 'folder'">
                <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                    <template x-if="!isExpanded(item.id)">
                        <path d="M19.5 21a3 3 0 003-3v-9a3 3 0 00-3-3h-6.379a1.5 1.5 0 01-1.06-.44L10.94 4.44A1.5 1.5 0 009.879 4H4.5a3 3 0 00-3 3v11a3 3 0 003 3h15z"></path>
                    </template>
                    <template x-if="isExpanded(item.id)">
                        <path d="M2.25 6.75c0-1.864 1.511-3.375 3.375-3.375h4.254c.476 0 .932.19 1.269.527l1.06 1.06c.337.337.793.527 1.269.527H18.75a3.375 3.375 0 013.375 3.375v1.125H3.75a1.5 1.5 0 00-1.5 1.5v7.125c0 .828.672 1.5 1.5 1.5h16.5c.828 0 1.5-.672 1.5-1.5v-8.625a3.375 3.375 0 00-3.375-3.375H9.227a1.125 1.125 0 01-.796-.33l-1.06-1.06a3.375 3.375 0 00-2.387-.988H5.625A3.375 3.375 0 002.25 6.75z"></path>
                    </template>
                </svg>
            </template>
            {{-- File icon --}}
            <template x-if="item.type !== 'folder' && (!item.icon || item.icon === 'file')">
                <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875z"></path>
                    <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"></path>
                </svg>
            </template>
            {{-- Image icon --}}
            <template x-if="item.icon === 'image'">
                <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd"></path>
                </svg>
            </template>
            {{-- Code icon --}}
            <template x-if="item.icon === 'code'">
                <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm14.25 6a.75.75 0 01-.22.53l-2.25 2.25a.75.75 0 11-1.06-1.06L15.44 12l-1.72-1.72a.75.75 0 111.06-1.06l2.25 2.25c.141.14.22.331.22.53zm-10.28-.53a.75.75 0 000 1.06l2.25 2.25a.75.75 0 101.06-1.06L8.56 12l1.72-1.72a.75.75 0 10-1.06-1.06l-2.25 2.25z" clip-rule="evenodd"></path>
                </svg>
            </template>
        </span>
    @endif

    {{-- Label --}}
    <span class="truncate {{ $sizeClasses['text'] }} text-neutral-900 dark:text-neutral-100" x-text="item.label || item.name"></span>

    {{-- Badge/Count --}}
    <template x-if="item.badge">
        <span class="ml-auto text-xs px-1.5 py-0.5 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-400" x-text="item.badge"></span>
    </template>

    {{-- Drag handle --}}
    @if($draggable)
        <div class="ml-auto opacity-0 group-hover:opacity-100 cursor-grab text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
            </svg>
        </div>
    @endif
</div>

{{-- Recursive tree item for deep nesting --}}
<div 
    class="tree-item-wrapper"
    :style="'padding-left: ' + item.level * 20 + 'px'"
>
    <div
        class="relative flex items-center cursor-pointer transition-colors duration-150 group {{ $sizeClasses['padding'] ?? 'py-1.5 px-2' }} {{ $sizeClasses['gap'] ?? 'gap-2' }} {{ $variantClasses['item'] ?? '' }}"
        :class="{
            '{{ $variantClasses['selected'] ?? 'bg-primary-50 dark:bg-primary-900/30' }}': isSelected(item.id),
            'ring-2 ring-primary-500 ring-inset': isDropTarget(item.id),
        }"
        @if($draggable)
            :draggable="true"
            @dragstart="onDragStart($event, item)"
            @dragend="onDragEnd($event)"
            @dragover="onDragOver($event, item)"
            @dragleave="onDragLeave($event)"
            @drop="onDrop($event, item)"
        @endif
        @click="selectItem(item.id, $event)"
        role="treeitem"
    >
        {{-- Expand/Collapse toggle --}}
        <template x-if="item.type === 'folder'">
            <button
                type="button"
                class="flex items-center justify-center shrink-0 {{ $sizeClasses['icon'] ?? 'size-5' }} text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 transition-transform duration-200"
                :class="{ 'rotate-90': isExpanded(item.id) }"
                @click.stop="toggleExpand(item.id)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </template>
        <template x-if="item.type !== 'folder'">
            <div class="{{ $sizeClasses['icon'] ?? 'size-5' }} shrink-0"></div>
        </template>

        {{-- Icon --}}
        @if($showIcons)
            <span class="{{ $sizeClasses['icon'] ?? 'size-5' }} shrink-0 {{ $colorClasses }}">
                <template x-if="item.type === 'folder'">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                        <path x-show="!isExpanded(item.id)" d="M19.5 21a3 3 0 003-3v-9a3 3 0 00-3-3h-6.379a1.5 1.5 0 01-1.06-.44L10.94 4.44A1.5 1.5 0 009.879 4H4.5a3 3 0 00-3 3v11a3 3 0 003 3h15z"></path>
                        <path x-show="isExpanded(item.id)" d="M2.25 6.75c0-1.864 1.511-3.375 3.375-3.375h4.254c.476 0 .932.19 1.269.527l1.06 1.06c.337.337.793.527 1.269.527H18.75a3.375 3.375 0 013.375 3.375v1.125H3.75a1.5 1.5 0 00-1.5 1.5v7.125c0 .828.672 1.5 1.5 1.5h16.5c.828 0 1.5-.672 1.5-1.5v-8.625a3.375 3.375 0 00-3.375-3.375H9.227a1.125 1.125 0 01-.796-.33l-1.06-1.06a3.375 3.375 0 00-2.387-.988H5.625A3.375 3.375 0 002.25 6.75z"></path>
                    </svg>
                </template>
                <template x-if="item.type !== 'folder'">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875z"></path>
                        <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"></path>
                    </svg>
                </template>
            </span>
        @endif

        {{-- Label --}}
        <span class="truncate {{ $sizeClasses['text'] ?? 'text-sm' }} text-neutral-900 dark:text-neutral-100" x-text="item.label || item.name"></span>

        {{-- Badge --}}
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

    {{-- Nested children --}}
    <template x-if="item.type === 'folder' && item.children && item.children.length > 0">
        <div x-show="isExpanded(item.id)" x-collapse>
            <template x-for="child in item.children" :key="child.id">
                <div x-data="{ item: { ...child, level: item.level + 1 } }">
                    @include('neura::tree.recursive-item')
                </div>
            </template>
        </div>
    </template>
</div>

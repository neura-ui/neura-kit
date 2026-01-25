@props([
    'item' => null,
    'level' => 0,
    'sizeClasses' => [],
    'variantClasses' => [],
    'colorClasses' => '',
    'showIcons' => true,
    'showLines' => true,
    'draggable' => false,
])

@php
    $indent = $level * 20;
@endphp

<div 
    class="tree-item-wrapper"
    :style="'padding-left: ' + (item.level || 0) * 20 + 'px'"
>
    {{-- Tree lines --}}
    @if($showLines)
        <template x-if="(item.level || 0) > 0">
            <div class="absolute left-0 top-0 bottom-0 flex">
                <template x-for="i in (item.level || 0)" :key="i">
                    <div class="w-5 border-l {{ $variantClasses['line'] ?? 'border-neutral-300 dark:border-neutral-700' }}"></div>
                </template>
            </div>
        </template>
    @endif

    {{-- Item row --}}
    <div
        class="relative flex items-center cursor-pointer transition-colors duration-150 {{ $sizeClasses['padding'] ?? 'py-1.5 px-2' }} {{ $sizeClasses['gap'] ?? 'gap-2' }} {{ $variantClasses['item'] ?? '' }}"
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
        :aria-expanded="item.type === 'folder' ? isExpanded(item.id) : undefined"
        :aria-selected="isSelected(item.id)"
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
            <template x-if="item.icon">
                <span class="{{ $sizeClasses['icon'] ?? 'size-5' }} shrink-0" :class="item.iconClass || '{{ $colorClasses }}'">
                    <template x-if="item.icon === 'folder'">
                        <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                            <path x-show="!isExpanded(item.id)" d="M19.5 21a3 3 0 003-3v-9a3 3 0 00-3-3h-6.379a1.5 1.5 0 01-1.06-.44L10.94 4.44A1.5 1.5 0 009.879 4H4.5a3 3 0 00-3 3v11a3 3 0 003 3h15z"></path>
                            <path x-show="isExpanded(item.id)" d="M2.25 6.75c0-1.864 1.511-3.375 3.375-3.375h4.254c.476 0 .932.19 1.269.527l1.06 1.06c.337.337.793.527 1.269.527H18.75a3.375 3.375 0 013.375 3.375v1.125H3.75a1.5 1.5 0 00-1.5 1.5v7.125c0 .828.672 1.5 1.5 1.5h16.5c.828 0 1.5-.672 1.5-1.5v-8.625a3.375 3.375 0 00-3.375-3.375H9.227a1.125 1.125 0 01-.796-.33l-1.06-1.06a3.375 3.375 0 00-2.387-.988H5.625A3.375 3.375 0 002.25 6.75z"></path>
                        </svg>
                    </template>
                    <template x-if="item.icon === 'file'">
                        <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875z"></path>
                            <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"></path>
                        </svg>
                    </template>
                    <template x-if="item.icon === 'image'">
                        <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd"></path>
                        </svg>
                    </template>
                    <template x-if="item.icon === 'code'">
                        <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6zm4.5 7.5a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5a.75.75 0 01.75-.75zm3.75.75a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5zm2.25-.75a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5a.75.75 0 01.75-.75zM6 9a.75.75 0 00-.75.75v1.5a.75.75 0 001.5 0v-1.5A.75.75 0 006 9zm3.75.75a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5zm2.25-.75a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0112 9zm3.75.75a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0v-1.5zM18 9a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0118 9z" clip-rule="evenodd"></path>
                        </svg>
                    </template>
                </span>
            </template>
            <template x-if="!item.icon">
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
            </template>
        @endif

        {{-- Label --}}
        <span class="truncate {{ $sizeClasses['text'] ?? 'text-sm' }} text-neutral-900 dark:text-neutral-100" x-text="item.label || item.name"></span>

        {{-- Badge/Count --}}
        <template x-if="item.badge">
            <span class="ml-auto text-xs px-1.5 py-0.5 rounded-full bg-neutral-200 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-400" x-text="item.badge"></span>
        </template>

        {{-- Actions --}}
        <template x-if="item.actions">
            <div class="ml-auto flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <template x-for="action in item.actions" :key="action.id">
                    <button 
                        type="button"
                        class="p-1 rounded hover:bg-neutral-200 dark:hover:bg-neutral-700 text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300"
                        @click.stop="$dispatch('tree-action', { action: action, item: item })"
                        :title="action.label"
                    >
                        <span class="size-4" x-html="action.icon"></span>
                    </button>
                </template>
            </div>
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

    {{-- Children --}}
    <template x-if="item.type === 'folder' && item.children && item.children.length > 0">
        <div x-show="isExpanded(item.id)" x-collapse>
            <template x-for="child in item.children" :key="child.id">
                <div x-data="{ item: { ...child, level: (item.level || 0) + 1 } }">
                    <div 
                        class="tree-item-wrapper"
                        :style="'padding-left: ' + item.level * 20 + 'px'"
                    >
                        {{-- Item row --}}
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
                            :aria-expanded="item.type === 'folder' ? isExpanded(item.id) : undefined"
                            :aria-selected="isSelected(item.id)"
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

                        {{-- Nested children (recursive) --}}
                        <template x-if="item.type === 'folder' && item.children && item.children.length > 0">
                            <div x-show="isExpanded(item.id)" x-collapse>
                                <template x-for="grandchild in item.children" :key="grandchild.id">
                                    <div x-data="{ item: { ...grandchild, level: item.level + 1 } }">
                                        @include('neura::tree.recursive-item', ['sizeClasses' => $sizeClasses, 'variantClasses' => $variantClasses, 'colorClasses' => $colorClasses, 'showIcons' => $showIcons, 'draggable' => $draggable])
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

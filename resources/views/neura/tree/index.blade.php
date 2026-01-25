@props([
    'items' => [],
    'variant' => 'default',
    'size' => 'md',
    'selectable' => false,
    'multiSelect' => false,
    'showIcons' => true,
    'expandAll' => false,
    'onlyFolders' => false,
    'wireModel' => null,
])

@php
    use Illuminate\Support\Str;
    
    $componentId = 'tree-' . Str::random(8);
    
    // Clean, modern sizes
    $sizeConfig = match ($size) {
        'sm' => [
            'text' => 'text-xs',
            'icon' => 'size-3.5',
            'padding' => 'px-1.5 py-0.5',
            'gap' => 'gap-1.5',
            'indent' => 12,
        ],
        'md' => [
            'text' => 'text-sm',
            'icon' => 'size-4',
            'padding' => 'px-2 py-1',
            'gap' => 'gap-2',
            'indent' => 16,
        ],
        'lg' => [
            'text' => 'text-base',
            'icon' => 'size-5',
            'padding' => 'px-2.5 py-1.5',
            'gap' => 'gap-2.5',
            'indent' => 20,
        ],
        default => [
            'text' => 'text-sm',
            'icon' => 'size-4',
            'padding' => 'px-2 py-1',
            'gap' => 'gap-2',
            'indent' => 16,
        ],
    };
    
    // Minimal, clean variants (shadcn/Notion style)
    $variantConfig = match ($variant) {
        'default' => [
            'item' => 'hover:bg-neutral-100/60 dark:hover:bg-neutral-800/60 rounded-md transition-colors',
            'selected' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100',
            'chevron' => 'text-neutral-400 hover:text-neutral-600 dark:text-neutral-600 dark:hover:text-neutral-400',
        ],
        'ghost' => [
            'item' => 'hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors',
            'selected' => 'bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 font-medium',
            'chevron' => 'text-neutral-400 hover:text-neutral-600 dark:text-neutral-600 dark:hover:text-neutral-400',
        ],
        'bordered' => [
            'item' => 'hover:bg-neutral-50 dark:hover:bg-neutral-900 border-l-2 border-transparent hover:border-neutral-300 dark:hover:border-neutral-700 transition-all',
            'selected' => 'bg-neutral-50 dark:bg-neutral-900 !border-neutral-900 dark:!border-neutral-100 text-neutral-900 dark:text-neutral-100',
            'chevron' => 'text-neutral-400 hover:text-neutral-600 dark:text-neutral-600 dark:hover:text-neutral-400',
        ],
        default => [
            'item' => 'hover:bg-neutral-100/60 dark:hover:bg-neutral-800/60 rounded-md transition-colors',
            'selected' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100',
            'chevron' => 'text-neutral-400 hover:text-neutral-600 dark:text-neutral-600 dark:hover:text-neutral-400',
        ],
    };
@endphp

<div 
    id="{{ $componentId }}"
    x-data="neuraTree({
        items: {{ json_encode($items) }},
        selectable: {{ $selectable ? 'true' : 'false' }},
        multiSelect: {{ $multiSelect ? 'true' : 'false' }},
        expandAll: {{ $expandAll ? 'true' : 'false' }},
        onlyFolders: {{ $onlyFolders ? 'true' : 'false' }},
        indent: {{ $sizeConfig['indent'] }},
        wireModel: {{ $wireModel ? "'" . $wireModel . "'" : 'null' }},
    })"
    x-init="init()"
    {{ $attributes->class(['w-full select-none']) }}
    role="tree"
    aria-label="File tree"
>
    <template x-for="item in visibleItems" :key="item.id">
        <div 
            class="relative {{ $sizeConfig['padding'] }} {{ $sizeConfig['gap'] }} {{ $variantConfig['item'] }} flex items-center group cursor-pointer"
            :class="{
                '{{ $variantConfig['selected'] }}': isSelected(item.id),
                'opacity-40': dragState.dragging && dragState.draggedId === item.id,
            }"
            :style="`padding-left: ${item.level * indent + 8}px`"
            @click="handleSelect(item, $event)"
            draggable="true"
            @dragstart="handleDragStart($event, item)"
            @dragend="handleDragEnd($event)"
            @dragover="handleDragOver($event, item)"
            @dragleave="handleDragLeave($event)"
            @drop="handleDrop($event, item)"
            role="treeitem"
            :aria-level="item.level + 1"
            :aria-expanded="item.type === 'folder' ? isExpanded(item.id) : undefined"
            :aria-selected="isSelected(item.id)"
        >
            {{-- Drop indicator --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'before'"
                class="absolute -top-px left-0 right-0 h-0.5 bg-primary-500 rounded-full"
                x-transition
            ></div>
            
            {{-- Chevron for folders --}}
            <button
                x-show="item.type === 'folder' && hasChildren(item)"
                @click.stop="toggleExpand(item.id)"
                class="{{ $sizeConfig['icon'] }} {{ $variantConfig['chevron'] }} shrink-0 flex items-center justify-center rounded transition-all duration-200"
                :class="{ 'rotate-90': isExpanded(item.id) }"
                type="button"
                :aria-label="isExpanded(item.id) ? 'Collapse' : 'Expand'"
            >
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="item.type !== 'folder' || !hasChildren(item)" class="{{ $sizeConfig['icon'] }} shrink-0"></div>

            {{-- Icon --}}
            @if($showIcons)
                <div class="{{ $sizeConfig['icon'] }} shrink-0 text-neutral-500 dark:text-neutral-400" :class="item.iconClass">
                    {{-- Folder icons (Notion/shadcn style) --}}
                    <template x-if="item.type === 'folder'">
                        <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path 
                                x-show="!isExpanded(item.id)"
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                            />
                            <path 
                                x-show="isExpanded(item.id)"
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"
                            />
                        </svg>
                    </template>
                    
                    {{-- File icons based on type --}}
                    <template x-if="item.type === 'file' || item.type === 'document'">
                        <template x-if="item.icon === 'image'">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </template>
                        <template x-if="item.icon === 'code'">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </template>
                        <template x-if="!item.icon || (item.icon !== 'image' && item.icon !== 'code')">
                            <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </template>
                    </template>
                </div>
            @endif

            {{-- Label --}}
            <span 
                class="{{ $sizeConfig['text'] }} truncate font-medium text-neutral-700 dark:text-neutral-300"
                :class="{
                    'text-neutral-900 dark:text-neutral-100': isSelected(item.id)
                }"
                x-text="item.label || item.name"
            ></span>

            {{-- Badge (Notion style) --}}
            <template x-if="item.badge">
                <span 
                    class="ml-auto text-xs px-1.5 py-0.5 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 font-medium"
                    x-text="item.badge"
                ></span>
            </template>

            {{-- Drag handle (subtle) --}}
            <div 
                class="ml-auto opacity-0 group-hover:opacity-100 {{ $sizeConfig['icon'] }} text-neutral-400 cursor-grab active:cursor-grabbing transition-opacity"
            >
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                </svg>
            </div>

            {{-- Drop indicator after --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'after'"
                class="absolute -bottom-px left-0 right-0 h-0.5 bg-primary-500 rounded-full"
                x-transition
            ></div>

            {{-- Drop zone for folders --}}
            <div 
                x-show="dragState.dropTarget === item.id && dragState.dropPosition === 'inside'"
                class="absolute inset-0 bg-primary-500/10 border-2 border-primary-500 border-dashed rounded-md pointer-events-none"
                x-transition
            ></div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('neuraTree', (config) => ({
        items: config.items || [],
        selectable: config.selectable || false,
        multiSelect: config.multiSelect || false,
        expandAll: config.expandAll || false,
        onlyFolders: config.onlyFolders || false,
        indent: config.indent || 16,
        wireModel: config.wireModel || null,
        
        expanded: new Set(),
        selected: new Set(),
        
        dragState: {
            dragging: false,
            draggedId: null,
            draggedItem: null,
            dropTarget: null,
            dropPosition: null, // 'before', 'after', 'inside'
        },

        init() {
            if (this.expandAll) {
                this.expandAllFolders(this.items);
            }
            
            // Listen for Livewire updates
            if (this.wireModel) {
                this.$watch('items', () => {
                    this.syncToLivewire();
                });
            }
        },

        get visibleItems() {
            let items = this.onlyFolders 
                ? this.filterFoldersOnly(this.items) 
                : this.items;
            return this.flattenTree(items, 0);
        },

        filterFoldersOnly(items) {
            return items
                .filter(item => item.type === 'folder')
                .map(item => ({
                    ...item,
                    children: item.children ? this.filterFoldersOnly(item.children) : []
                }));
        },

        flattenTree(items, level) {
            let result = [];
            for (const item of items) {
                result.push({ ...item, level });
                if (item.type === 'folder' && this.isExpanded(item.id) && item.children?.length) {
                    result = result.concat(this.flattenTree(item.children, level + 1));
                }
            }
            return result;
        },

        expandAllFolders(items) {
            items.forEach(item => {
                if (item.type === 'folder' && item.children?.length > 0) {
                    this.expanded.add(item.id);
                    this.expandAllFolders(item.children);
                }
            });
        },

        isExpanded(id) {
            return this.expanded.has(id);
        },

        toggleExpand(id) {
            if (this.expanded.has(id)) {
                this.expanded.delete(id);
            } else {
                this.expanded.add(id);
            }
            this.expanded = new Set(this.expanded);
        },

        hasChildren(item) {
            return item.children && item.children.length > 0;
        },

        isSelected(id) {
            return this.selected.has(id);
        },

        handleSelect(item, event) {
            if (!this.selectable) return;

            if (this.multiSelect && (event.ctrlKey || event.metaKey)) {
                if (this.selected.has(item.id)) {
                    this.selected.delete(item.id);
                } else {
                    this.selected.add(item.id);
                }
            } else {
                this.selected = new Set([item.id]);
            }
            
            this.$dispatch('tree:select', {
                selected: Array.from(this.selected),
                item: item
            });
        },

        // Drag and Drop with visual feedback
        handleDragStart(event, item) {
            this.dragState.dragging = true;
            this.dragState.draggedId = item.id;
            this.dragState.draggedItem = item;
            
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.id);
        },

        handleDragEnd(event) {
            this.dragState.dragging = false;
            this.dragState.draggedId = null;
            this.dragState.draggedItem = null;
            this.dragState.dropTarget = null;
            this.dragState.dropPosition = null;
        },

        handleDragOver(event, item) {
            if (!this.dragState.dragging || this.dragState.draggedId === item.id) return;
            
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            
            const rect = event.currentTarget.getBoundingClientRect();
            const y = event.clientY - rect.top;
            const height = rect.height;
            
            // Determine drop position
            if (item.type === 'folder') {
                if (y < height * 0.25) {
                    this.dragState.dropPosition = 'before';
                } else if (y > height * 0.75) {
                    this.dragState.dropPosition = 'after';
                } else {
                    this.dragState.dropPosition = 'inside';
                }
            } else {
                this.dragState.dropPosition = y < height / 2 ? 'before' : 'after';
            }
            
            this.dragState.dropTarget = item.id;
        },

        handleDragLeave(event) {
            // Only clear if leaving the component
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.dragState.dropTarget = null;
                this.dragState.dropPosition = null;
            }
        },

        handleDrop(event, targetItem) {
            event.preventDefault();
            
            if (!this.dragState.draggedItem || this.dragState.draggedId === targetItem.id) {
                return;
            }

            const draggedItem = {...this.dragState.draggedItem};
            const position = this.dragState.dropPosition;

            // Remove from old position
            this.removeItemFromTree(this.items, draggedItem.id);

            // Insert at new position
            if (position === 'inside' && targetItem.type === 'folder') {
                const target = this.findItemInTree(this.items, targetItem.id);
                if (target) {
                    if (!target.children) target.children = [];
                    target.children.unshift(draggedItem);
                    this.expanded.add(targetItem.id);
                }
            } else {
                this.insertItemRelative(this.items, targetItem.id, draggedItem, position);
            }

            // Sync with Livewire if needed
            this.syncToLivewire();

            // Dispatch event
            this.$dispatch('tree:move', {
                item: draggedItem,
                target: targetItem,
                position: position,
                items: this.items
            });

            // Reset drag state
            this.handleDragEnd(event);
        },

        findItemInTree(items, id) {
            for (const item of items) {
                if (item.id === id) return item;
                if (item.children) {
                    const found = this.findItemInTree(item.children, id);
                    if (found) return found;
                }
            }
            return null;
        },

        removeItemFromTree(items, id) {
            for (let i = 0; i < items.length; i++) {
                if (items[i].id === id) {
                    items.splice(i, 1);
                    return true;
                }
                if (items[i].children && this.removeItemFromTree(items[i].children, id)) {
                    return true;
                }
            }
            return false;
        },

        insertItemRelative(items, targetId, newItem, position) {
            for (let i = 0; i < items.length; i++) {
                if (items[i].id === targetId) {
                    const index = position === 'before' ? i : i + 1;
                    items.splice(index, 0, newItem);
                    return true;
                }
                if (items[i].children && this.insertItemRelative(items[i].children, targetId, newItem, position)) {
                    return true;
                }
            }
            return false;
        },

        syncToLivewire() {
            if (this.wireModel && this.$wire) {
                this.$wire.set(this.wireModel, this.items);
            }
        },
    }));
});
</script>
@endpush
@endonce

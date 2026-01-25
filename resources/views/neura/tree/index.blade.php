@props([
    'items' => [],
    'variant' => 'default',
    'draggable' => false,
    'selectable' => false,
    'multiSelect' => false,
    'showIcons' => true,
    'showLines' => false,
    'size' => 'md',
    'color' => 'primary',
    'expandAll' => false,
    'onlyFolders' => false,
])

@php
    // Size classes
    $sizeClasses = match ($size) {
        'sm' => [
            'text' => 'text-sm',
            'icon' => 'size-4',
            'padding' => 'py-1 px-2',
            'indent' => 16,
            'gap' => 'gap-1.5',
        ],
        'md' => [
            'text' => 'text-sm',
            'icon' => 'size-5',
            'padding' => 'py-1.5 px-2',
            'indent' => 20,
            'gap' => 'gap-2',
        ],
        'lg' => [
            'text' => 'text-base',
            'icon' => 'size-6',
            'padding' => 'py-2 px-3',
            'indent' => 24,
            'gap' => 'gap-2.5',
        ],
        default => [
            'text' => 'text-sm',
            'icon' => 'size-5',
            'padding' => 'py-1.5 px-2',
            'indent' => 20,
            'gap' => 'gap-2',
        ],
    };

    // Variant classes
    $variantClasses = match ($variant) {
        'default' => [
            'item' => 'hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md',
            'selected' => 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300',
            'line' => 'border-neutral-300 dark:border-neutral-700',
        ],
        'minimal' => [
            'item' => 'hover:bg-neutral-50 dark:hover:bg-neutral-900',
            'selected' => 'bg-neutral-100 dark:bg-neutral-800 font-medium',
            'line' => 'border-neutral-200 dark:border-neutral-800',
        ],
        'bordered' => [
            'item' => 'hover:bg-neutral-50 dark:hover:bg-neutral-900 border-l-2 border-transparent hover:border-primary-500',
            'selected' => 'bg-primary-50 dark:bg-primary-900/30 border-l-2 !border-primary-500',
            'line' => 'border-neutral-300 dark:border-neutral-700',
        ],
        'filled' => [
            'item' => 'hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-lg',
            'selected' => 'bg-primary-500 text-white',
            'line' => 'border-neutral-300 dark:border-neutral-700',
        ],
        default => [
            'item' => 'hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md',
            'selected' => 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300',
            'line' => 'border-neutral-300 dark:border-neutral-700',
        ],
    };

    // Color classes for icons
    $colorClasses = match ($color) {
        'primary' => 'text-primary-500',
        'secondary' => 'text-neutral-500',
        'success' => 'text-green-500',
        'danger' => 'text-red-500',
        'warning' => 'text-yellow-500',
        'info' => 'text-blue-500',
        default => 'text-primary-500',
    };

    $componentId = 'tree-' . uniqid();
@endphp

<div 
    id="{{ $componentId }}"
    x-data="neuraTreeView({
        items: {{ json_encode($items) }},
        draggable: {{ $draggable ? 'true' : 'false' }},
        selectable: {{ $selectable ? 'true' : 'false' }},
        multiSelect: {{ $multiSelect ? 'true' : 'false' }},
        expandAll: {{ $expandAll ? 'true' : 'false' }},
        onlyFolders: {{ $onlyFolders ? 'true' : 'false' }},
        indent: {{ $sizeClasses['indent'] }},
    })"
    {{ $attributes->class(['w-full']) }}
    role="tree"
>
    {{-- Recursive tree rendering --}}
    <template x-for="item in processedItems" :key="item.id">
        <div class="tree-node">
            @include('neura::tree.node', [
                'sizeClasses' => $sizeClasses,
                'variantClasses' => $variantClasses,
                'colorClasses' => $colorClasses,
                'showIcons' => $showIcons,
                'showLines' => $showLines,
                'draggable' => $draggable,
            ])
        </div>
    </template>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('neuraTreeView', (config) => ({
        items: config.items || [],
        draggable: config.draggable || false,
        selectable: config.selectable || false,
        multiSelect: config.multiSelect || false,
        expandAll: config.expandAll || false,
        onlyFolders: config.onlyFolders || false,
        indent: config.indent || 20,
        expandedItems: new Set(),
        selectedItems: new Set(),
        draggedItem: null,
        dropTarget: null,

        init() {
            if (this.expandAll) {
                this.expandAllItems(this.items);
            }
        },

        get processedItems() {
            let items = this.items;
            if (this.onlyFolders) {
                items = this.filterFolders(items);
            }
            return this.flattenItems(items, 0);
        },

        filterFolders(items) {
            return items.filter(item => item.type === 'folder').map(item => ({
                ...item,
                children: item.children ? this.filterFolders(item.children) : []
            }));
        },

        flattenItems(items, level) {
            let result = [];
            for (const item of items) {
                result.push({ ...item, level });
                if (item.type === 'folder' && item.children && this.isExpanded(item.id)) {
                    result = result.concat(this.flattenItems(item.children, level + 1));
                }
            }
            return result;
        },

        expandAllItems(items) {
            items.forEach(item => {
                if (item.type === 'folder' && item.children?.length > 0) {
                    this.expandedItems.add(item.id);
                    this.expandAllItems(item.children);
                }
            });
        },

        isExpanded(itemId) {
            return this.expandedItems.has(itemId);
        },

        hasChildren(item) {
            return item.type === 'folder' && item.children && item.children.length > 0;
        },

        toggleExpand(itemId) {
            if (this.expandedItems.has(itemId)) {
                this.expandedItems.delete(itemId);
            } else {
                this.expandedItems.add(itemId);
            }
            this.expandedItems = new Set(this.expandedItems);
        },

        isSelected(itemId) {
            return this.selectedItems.has(itemId);
        },

        selectItem(itemId, event) {
            if (!this.selectable) return;

            if (this.multiSelect && (event?.ctrlKey || event?.metaKey)) {
                if (this.selectedItems.has(itemId)) {
                    this.selectedItems.delete(itemId);
                } else {
                    this.selectedItems.add(itemId);
                }
            } else {
                this.selectedItems = new Set([itemId]);
            }
            this.selectedItems = new Set(this.selectedItems);
            
            this.$dispatch('tree-select', { 
                selectedItems: Array.from(this.selectedItems),
                item: this.findItemById(itemId, this.items)
            });
        },

        findItemById(id, items) {
            for (const item of items) {
                if (item.id === id) return item;
                if (item.children) {
                    const found = this.findItemById(id, item.children);
                    if (found) return found;
                }
            }
            return null;
        },

        onDragStart(event, item) {
            if (!this.draggable) return;
            this.draggedItem = item;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.id);
            event.target.classList.add('opacity-50');
        },

        onDragEnd(event) {
            event.target.classList.remove('opacity-50');
            this.draggedItem = null;
            this.dropTarget = null;
        },

        onDragOver(event, item) {
            if (!this.draggable || !this.draggedItem) return;
            if (this.draggedItem.id === item.id) return;
            
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dropTarget = item.id;
        },

        onDragLeave(event) {
            this.dropTarget = null;
        },

        onDrop(event, targetItem) {
            if (!this.draggable || !this.draggedItem) return;
            if (this.draggedItem.id === targetItem.id) return;

            event.preventDefault();
            
            this.removeItem(this.draggedItem.id, this.items);
            
            if (targetItem.type === 'folder') {
                if (!targetItem.children) targetItem.children = [];
                targetItem.children.push({...this.draggedItem});
                this.expandedItems.add(targetItem.id);
            } else {
                const parent = this.findParent(targetItem.id, this.items, null);
                if (parent) {
                    const index = parent.children.findIndex(c => c.id === targetItem.id);
                    parent.children.splice(index + 1, 0, {...this.draggedItem});
                } else {
                    const index = this.items.findIndex(i => i.id === targetItem.id);
                    this.items.splice(index + 1, 0, {...this.draggedItem});
                }
            }

            this.$dispatch('tree-move', {
                movedItem: this.draggedItem,
                targetItem: targetItem,
                items: this.items
            });

            this.dropTarget = null;
            this.draggedItem = null;
            this.expandedItems = new Set(this.expandedItems);
        },

        removeItem(id, items) {
            const index = items.findIndex(i => i.id === id);
            if (index !== -1) {
                items.splice(index, 1);
                return true;
            }
            for (const item of items) {
                if (item.children && this.removeItem(id, item.children)) {
                    return true;
                }
            }
            return false;
        },

        findParent(id, items, parent) {
            for (const item of items) {
                if (item.id === id) return parent;
                if (item.children) {
                    const found = this.findParent(id, item.children, item);
                    if (found) return found;
                }
            }
            return null;
        },

        isDropTarget(itemId) {
            return this.dropTarget === itemId;
        },
    }));
});
</script>
@endonce

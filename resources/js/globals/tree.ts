/**
 * NeuraKit Tree Component
 * 
 * A tree view component with folder/file hierarchy, drag & drop support,
 * and Livewire integration.
 */

interface TreeItem {
    id: string;
    type: 'folder' | 'file' | 'document';
    name?: string;
    label?: string;
    icon?: string;
    iconClass?: string;
    badge?: string;
    children?: TreeItem[];
    level?: number;
}

interface TreeConfig {
    items: TreeItem[];
    selectable: boolean;
    multiSelect: boolean;
    expandAll: boolean;
    onlyFolders: boolean;
    indent: number;
    wireModel: string | null;
}

interface DragState {
    dragging: boolean;
    draggedId: string | null;
    draggedItem: TreeItem | null;
    dropTarget: string | null;
    dropPosition: 'before' | 'after' | 'inside' | null;
}

// Ensure we only register once
const NK_TREE_BOOT = (window as any).__NK_TREE_BOOT__ ??= { booted: false };

if (!NK_TREE_BOOT.booted) {
    NK_TREE_BOOT.booted = true;

    document.addEventListener('alpine:init', () => {
        (window as any).Alpine.data('neuraTree', (config: TreeConfig) => ({
            items: config.items || [],
            selectable: config.selectable || false,
            multiSelect: config.multiSelect || false,
            expandAll: config.expandAll || false,
            onlyFolders: config.onlyFolders || false,
            indent: config.indent || 16,
            wireModel: config.wireModel || null,
            
            expanded: new Set<string>(),
            selected: new Set<string>(),
            
            dragState: {
                dragging: false,
                draggedId: null,
                draggedItem: null,
                dropTarget: null,
                dropPosition: null,
            } as DragState,

            init() {
                if (this.expandAll) {
                    this.expandAllFolders(this.items);
                }
                
                // Listen for Livewire updates
                if (this.wireModel) {
                    (this as any).$watch('items', () => {
                        this.syncToLivewire();
                    });
                }
            },

            get visibleItems(): TreeItem[] {
                let items = this.onlyFolders 
                    ? this.filterFoldersOnly(this.items) 
                    : this.items;
                return this.flattenTree(items, 0);
            },

            filterFoldersOnly(items: TreeItem[]): TreeItem[] {
                return items
                    .filter(item => item.type === 'folder')
                    .map(item => ({
                        ...item,
                        children: item.children ? this.filterFoldersOnly(item.children) : []
                    }));
            },

            flattenTree(items: TreeItem[], level: number): TreeItem[] {
                let result: TreeItem[] = [];
                for (const item of items) {
                    result.push({ ...item, level });
                    if (item.type === 'folder' && this.isExpanded(item.id) && item.children?.length) {
                        result = result.concat(this.flattenTree(item.children, level + 1));
                    }
                }
                return result;
            },

            expandAllFolders(items: TreeItem[]) {
                items.forEach(item => {
                    if (item.type === 'folder' && item.children && item.children.length > 0) {
                        this.expanded.add(item.id);
                        this.expandAllFolders(item.children);
                    }
                });
            },

            isExpanded(id: string): boolean {
                return this.expanded.has(id);
            },

            toggleExpand(id: string) {
                if (this.expanded.has(id)) {
                    this.expanded.delete(id);
                } else {
                    this.expanded.add(id);
                }
                this.expanded = new Set(this.expanded);
            },

            hasChildren(item: TreeItem): boolean {
                return item.children !== undefined && item.children.length > 0;
            },

            isSelected(id: string): boolean {
                return this.selected.has(id);
            },

            handleSelect(item: TreeItem, event: MouseEvent) {
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
                
                (this as any).$dispatch('tree:select', {
                    selected: Array.from(this.selected),
                    item: item
                });
            },

            // Drag and Drop with visual feedback
            handleDragStart(event: DragEvent, item: TreeItem) {
                this.dragState.dragging = true;
                this.dragState.draggedId = item.id;
                this.dragState.draggedItem = item;
                
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', item.id);
                }
            },

            handleDragEnd(_event: DragEvent) {
                this.dragState.dragging = false;
                this.dragState.draggedId = null;
                this.dragState.draggedItem = null;
                this.dragState.dropTarget = null;
                this.dragState.dropPosition = null;
            },

            handleDragOver(event: DragEvent, item: TreeItem) {
                if (!this.dragState.dragging || this.dragState.draggedId === item.id) return;
                
                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                
                const target = event.currentTarget as HTMLElement;
                const rect = target.getBoundingClientRect();
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

            handleDragLeave(event: DragEvent) {
                const target = event.currentTarget as HTMLElement;
                // Only clear if leaving the component
                if (!target.contains(event.relatedTarget as Node)) {
                    this.dragState.dropTarget = null;
                    this.dragState.dropPosition = null;
                }
            },

            handleDrop(event: DragEvent, targetItem: TreeItem) {
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
                (this as any).$dispatch('tree:move', {
                    item: draggedItem,
                    target: targetItem,
                    position: position,
                    items: this.items
                });

                // Reset drag state
                this.handleDragEnd(event);
            },

            findItemInTree(items: TreeItem[], id: string): TreeItem | null {
                for (const item of items) {
                    if (item.id === id) return item;
                    if (item.children) {
                        const found = this.findItemInTree(item.children, id);
                        if (found) return found;
                    }
                }
                return null;
            },

            removeItemFromTree(items: TreeItem[], id: string): boolean {
                for (let i = 0; i < items.length; i++) {
                    if (items[i].id === id) {
                        items.splice(i, 1);
                        return true;
                    }
                    const children = items[i].children;
                    if (children && this.removeItemFromTree(children, id)) {
                        return true;
                    }
                }
                return false;
            },

            insertItemRelative(items: TreeItem[], targetId: string, newItem: TreeItem, position: string | null): boolean {
                for (let i = 0; i < items.length; i++) {
                    if (items[i].id === targetId) {
                        const index = position === 'before' ? i : i + 1;
                        items.splice(index, 0, newItem);
                        return true;
                    }
                    const children = items[i].children;
                    if (children && this.insertItemRelative(children, targetId, newItem, position)) {
                        return true;
                    }
                }
                return false;
            },

            syncToLivewire() {
                if (this.wireModel && (this as any).$wire) {
                    (this as any).$wire.set(this.wireModel, this.items);
                }
            },
        }));
    });
}

export {};

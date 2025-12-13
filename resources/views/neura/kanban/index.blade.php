@props([
    'columns' => [],
    'emptyState' => null,
    'draggable' => true,
    'draggableColumns' => true,
    'onCardMove' => null,
    'showCount' => true,
])

@php
    use Illuminate\Support\Arr;

    $boardColumns = collect($columns ?? []);
    $emptyStateLabel = $emptyState ?? neura_trans('noCardsYet');
@endphp

<div 
    x-data="{
        columns: @js($boardColumns->toArray()),
        draggedCard: null,
        draggedFromColumn: null,
        draggedFromIndex: null,
        dragOverColumn: null,
        draggedColumnIndex: null,
        dragOverColumnIndex: null,
        
        init() {
            this.$nextTick(() => {
                const wireModel = this.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
                
                if (wireModel && this.$wire) {
                    const prop = this.$root.getAttribute(wireModel);
                    const livewireValue = this.$wire.get(prop);
                    
                    if (livewireValue && Array.isArray(livewireValue)) {
                        this.columns = livewireValue;
                    }
                } else {
                    const alpineModel = this.$root?._x_model?.get();
                    if (alpineModel && Array.isArray(alpineModel)) {
                        this.columns = alpineModel;
                    }
                }
            });
            
            this.$watch('columns', (value) => {
                const wireModel = this.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
                
                if (wireModel && this.$wire) {
                    const prop = this.$root.getAttribute(wireModel);
                    const isLive = wireModel.includes('.live');
                    this.$wire.set(prop, value, isLive);
                }
                
                this.$root?._x_model?.set(value);
                
                if (this.onCardMove && typeof this.onCardMove === 'function') {
                    this.onCardMove(value);
                }
            }, { deep: true });
        },
        
        handleDragStart(columnIndex, cardIndex, event) {
            if (!@js($draggable)) return;
            
            this.draggedCard = { ...this.columns[columnIndex].cards[cardIndex] };
            this.draggedFromColumn = columnIndex;
            this.draggedFromIndex = cardIndex;
            
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target);
        },
        
        handleDragOver(columnIndex, event) {
            if (!@js($draggable)) return;
            
            if (this.draggedColumnIndex !== null) return;
            
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            this.dragOverColumn = columnIndex;
        },
        
        handleDragLeave() {
            this.dragOverColumn = null;
        },
        
        handleDrop(columnIndex, event) {
            if (!@js($draggable)) return;
            
            event.preventDefault();
            
            if (this.draggedCard === null || this.draggedFromColumn === null) return;
            
            const targetColumn = columnIndex;
            const sourceColumn = this.draggedFromColumn;
            
            if (sourceColumn === targetColumn) {
                this.draggedCard = null;
                this.draggedFromColumn = null;
                this.draggedFromIndex = null;
                this.dragOverColumn = null;
                return;
            }
            
            Alpine.mutateDom(() => {
                const updatedColumns = [...this.columns];
                
                const card = updatedColumns[sourceColumn].cards[this.draggedFromIndex];
                
                updatedColumns[sourceColumn].cards.splice(this.draggedFromIndex, 1);
                
                if (!updatedColumns[targetColumn].cards) {
                    updatedColumns[targetColumn].cards = [];
                }
                
                updatedColumns[targetColumn].cards.push(card);
                
                this.columns = updatedColumns;
                
                this.$dispatch('kanban:card-moved', {
                    card: card,
                    fromColumn: sourceColumn,
                    toColumn: targetColumn,
                    fromIndex: this.draggedFromIndex,
                    toIndex: updatedColumns[targetColumn].cards.length - 1,
                    columns: this.columns
                });
            });
            
            this.draggedCard = null;
            this.draggedFromColumn = null;
            this.draggedFromIndex = null;
            this.dragOverColumn = null;
        },
        
        handleCardDrop(columnIndex, cardIndex, event) {
            if (!@js($draggable)) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            if (this.draggedCard === null || this.draggedFromColumn === null) return;
            
            const targetColumn = columnIndex;
            const targetCardIndex = cardIndex;
            const sourceColumn = this.draggedFromColumn;
            
            if (sourceColumn === targetColumn && this.draggedFromIndex === targetCardIndex) {
                this.draggedCard = null;
                this.draggedFromColumn = null;
                this.draggedFromIndex = null;
                this.dragOverColumn = null;
                return;
            }
            
            Alpine.mutateDom(() => {
                const updatedColumns = [...this.columns];
                
                const card = updatedColumns[sourceColumn].cards[this.draggedFromIndex];
                
                updatedColumns[sourceColumn].cards.splice(this.draggedFromIndex, 1);
                
                if (!updatedColumns[targetColumn].cards) {
                    updatedColumns[targetColumn].cards = [];
                }
                
                const insertIndex = sourceColumn === targetColumn && this.draggedFromIndex < targetCardIndex 
                    ? targetCardIndex 
                    : targetCardIndex + 1;
                
                updatedColumns[targetColumn].cards.splice(insertIndex, 0, card);
                
                this.columns = updatedColumns;
                
                this.$dispatch('kanban:card-moved', {
                    card: card,
                    fromColumn: sourceColumn,
                    toColumn: targetColumn,
                    fromIndex: this.draggedFromIndex,
                    toIndex: insertIndex,
                    columns: this.columns
                });
            });
            
            this.draggedCard = null;
            this.draggedFromColumn = null;
            this.draggedFromIndex = null;
            this.dragOverColumn = null;
        },
        
        handleColumnDragStart(columnIndex, event) {
            if (!@js($draggableColumns)) return;
            
            this.draggedColumnIndex = columnIndex;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target);
        },
        
        handleColumnDragOver(columnIndex, event) {
            if (!@js($draggableColumns)) return;
            
            if (this.draggedCard !== null) return;
            
            event.preventDefault();
            event.stopPropagation();
            event.dataTransfer.dropEffect = 'move';
            this.dragOverColumnIndex = columnIndex;
        },
        
        handleColumnDragLeave() {
            this.dragOverColumnIndex = null;
        },
        
        handleColumnDrop(columnIndex, event) {
            if (!@js($draggableColumns)) return;
            
            if (this.draggedCard !== null) return;
            
            event.preventDefault();
            event.stopPropagation();
            
            if (this.draggedColumnIndex === null || this.draggedColumnIndex === columnIndex) {
                this.draggedColumnIndex = null;
                this.dragOverColumnIndex = null;
                return;
            }
            
            Alpine.mutateDom(() => {
                const updatedColumns = [...this.columns];
                const [movedColumn] = updatedColumns.splice(this.draggedColumnIndex, 1);
                updatedColumns.splice(columnIndex, 0, movedColumn);
                
                this.columns = updatedColumns;
                
                this.$dispatch('kanban:column-moved', {
                    column: movedColumn,
                    fromIndex: this.draggedColumnIndex,
                    toIndex: columnIndex,
                    columns: this.columns
                });
            });
            
            this.draggedColumnIndex = null;
            this.dragOverColumnIndex = null;
        }
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    @if($boardColumns->isEmpty())
        <div class="rounded-[28px] border border-dashed border-neutral-200 dark:border-neutral-800 px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
            {{ $emptyStateLabel }}
        </div>
    @else
        <div class="overflow-x-auto">
            <div class="flex gap-4 px-1 pb-2">
                <template x-for="(column, columnIndex) in columns" :key="columnIndex">
                    <section 
                        :class="[
                            'flex flex-col rounded-[24px] bg-white dark:bg-neutral-950 border shadow-sm min-w-[18rem] max-w-xs px-4 py-4',
                            dragOverColumn === columnIndex ? 'border-blue-500 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-950/20' : 'border-neutral-200 dark:border-neutral-900',
                            dragOverColumnIndex === columnIndex && @js($draggableColumns) ? 'border-purple-500 dark:border-purple-400 bg-purple-50/50 dark:bg-purple-950/20' : '',
                            draggedColumnIndex === columnIndex && @js($draggableColumns) ? 'opacity-50' : '',
                            column.class || ''
                        ]"
                        x-on:dragover.prevent="handleDragOver(columnIndex, $event)"
                        x-on:dragleave="handleDragLeave()"
                        x-on:drop="handleDrop(columnIndex, $event)"
                    >
                        <div 
                            :draggable="@js($draggableColumns)"
                            x-on:dragstart="handleColumnDragStart(columnIndex, $event)"
                            x-on:dragover.prevent="handleColumnDragOver(columnIndex, $event)"
                            x-on:dragleave="handleColumnDragLeave()"
                            x-on:drop="handleColumnDrop(columnIndex, $event)"
                            :class="[
                                'flex items-start justify-between gap-3',
                                @js($draggableColumns) ? 'cursor-move' : ''
                            ]"
                        >
                            <div>
                                <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate" x-text="column.title || ''"></p>
                                <template x-if="column.description">
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-[2px]" x-text="column.description"></p>
                                </template>
                            </div>
                            <template x-if="@js($showCount)">
                                <span class="text-[11px] font-semibold text-neutral-500 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-900 px-2 py-1 rounded-full" x-text="(column.cards || []).length"></span>
                            </template>
                        </div>

                        <div class="mt-4 flex flex-col gap-3">
                            <template x-for="(card, cardIndex) in (column.cards || [])" :key="cardIndex">
                                <article 
                                    :draggable="@js($draggable)"
                                    x-on:dragstart="handleDragStart(columnIndex, cardIndex, $event)"
                                    x-on:dragover.prevent=""
                                    x-on:drop="handleCardDrop(columnIndex, cardIndex, $event)"
                                    :class="[
                                        'flex flex-col gap-2 rounded-2xl border bg-neutral-50 dark:bg-neutral-900/60 p-4 shadow-sm transition',
                                        draggedFromColumn === columnIndex && draggedFromIndex === cardIndex 
                                            ? 'opacity-50 border-blue-500 dark:border-blue-400' 
                                            : 'border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700',
                                        @js($draggable) ? 'cursor-move' : ''
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 space-y-1">
                                            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate" x-text="card.title || ''"></p>
                                            <template x-if="card.subtitle">
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate" x-text="card.subtitle"></p>
                                            </template>
                                        </div>
                                        <template x-if="card.badge">
                                            <span class="text-[10px] font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-900/80 px-2 py-1 rounded-full" x-text="card.badge"></span>
                                        </template>
                                    </div>
                                    <template x-if="card.description">
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400" x-text="card.description"></p>
                                    </template>
                                    <template x-if="card.meta">
                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-medium text-neutral-500 dark:text-neutral-400">
                                            <template x-for="(metaItem, metaIndex) in (Array.isArray(card.meta) ? card.meta : [card.meta])" :key="metaIndex">
                                                <span class="px-2 py-1 rounded-full bg-neutral-100 dark:bg-neutral-900/80" x-text="metaItem"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="card.footer">
                                        <div class="mt-2 text-[11px] text-neutral-400 dark:text-neutral-500" x-text="card.footer"></div>
                                    </template>
                                </article>
                            </template>
                            <template x-if="!column.cards || column.cards.length === 0">
                                <p class="text-xs text-center text-neutral-400 dark:text-neutral-500 italic" x-text="column.emptyState || @js($emptyStateLabel)"></p>
                            </template>
                        </div>

                        <template x-if="column.action && column.action.label">
                            <div class="mt-4">
                                <button 
                                    type="button" 
                                    :class="[
                                        'flex items-center justify-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-neutral-600 border border-dashed border-neutral-300 rounded-2xl px-3 py-2 transition hover:border-neutral-400 hover:bg-neutral-50 dark:text-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700 dark:hover:bg-neutral-900/50',
                                        column.action.class || '',
                                        (column.action.attributes && column.action.attributes.class) || ''
                                    ]"
                                    x-init="
                                        const attrs = column.action.attributes || {};
                                        Object.keys(attrs).forEach(key => {
                                            if (key !== 'class') {
                                                $el.setAttribute(key, attrs[key]);
                                            }
                                        });
                                    "
                                >
                                    <template x-if="column.action.icon">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </template>
                                    <span x-text="column.action.label"></span>
                                </button>
                            </div>
                        </template>
                    </section>
                </template>
            </div>
        </div>
    @endif
</div>


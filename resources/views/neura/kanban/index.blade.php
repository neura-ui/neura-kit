@props([
    'columns' => [],
    'emptyState' => null,
    'draggable' => true,
    'draggableColumns' => true,
    'onCardMove' => null,
    'onCardClick' => null,
    'showCount' => true,
    'variant' => 'default',
])

@php
    use Illuminate\Support\Arr;

    $boardColumns = collect($columns ?? []);
    $emptyStateLabel = $emptyState ?? neura_trans('noCardsYet');

    $variants = [
        'default' => [
            'column' => 'rounded-[24px] bg-surface-raised backdrop-blur-xl border shadow-sm min-w-[18rem] max-w-xs px-4 py-4',
            'card' => 'rounded-2xl border bg-surface p-4 shadow-sm',
            'gap' => 'gap-4',
            'cardGap' => 'gap-3',
        ],
        'compact' => [
            'column' => 'rounded-xl bg-surface-raised backdrop-blur-xl border shadow-sm min-w-[15rem] max-w-[17rem] px-3 py-3',
            'card' => 'rounded-lg border bg-surface p-3 shadow-xs',
            'gap' => 'gap-3',
            'cardGap' => 'gap-2',
        ],
        'flat' => [
            'column' => 'rounded-xl bg-surface-inset min-w-[18rem] max-w-xs px-4 py-4',
            'card' => 'rounded-xl bg-surface-raised border border-edge p-4',
            'gap' => 'gap-4',
            'cardGap' => 'gap-2',
        ],
        'outlined' => [
            'column' => 'rounded-xl border-2 border-edge border-dashed bg-transparent min-w-[18rem] max-w-xs px-4 py-4',
            'card' => 'rounded-xl border border-edge bg-surface p-4 shadow-sm',
            'gap' => 'gap-4',
            'cardGap' => 'gap-3',
        ],
        'colorful' => [
            'column' => 'rounded-[24px] bg-surface-raised backdrop-blur-xl border shadow-sm min-w-[18rem] max-w-xs px-4 pt-0 pb-4 overflow-hidden',
            'card' => 'rounded-2xl border bg-surface p-4 shadow-sm',
            'gap' => 'gap-4',
            'cardGap' => 'gap-3',
        ],
    ];

    $v = $variants[$variant] ?? $variants['default'];
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
        justDragged: false,
        
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
            this.justDragged = true;
            this.$nextTick(() => { this.justDragged = false; });
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
            this.justDragged = true;
            this.$nextTick(() => { this.justDragged = false; });
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
        <div class="rounded-[28px] border border-dashed border-edge px-6 py-12 text-center text-sm text-fg-muted">
            {{ $emptyStateLabel }}
        </div>
    @else
        <div class="overflow-x-auto">
            <div class="flex {{ $v['gap'] }} px-1 pb-2">
                <template x-for="(column, columnIndex) in columns" :key="columnIndex">
                    <section 
                        :class="[
                            'flex flex-col {{ $v['column'] }}',
                            dragOverColumn === columnIndex ? 'border-blue-500 dark:border-blue-400 bg-blue-50/50 dark:bg-blue-950/20' : '{{ $variant !== 'flat' && $variant !== 'outlined' ? 'border-edge' : '' }}',
                            dragOverColumnIndex === columnIndex && @js($draggableColumns) ? 'border-purple-500 dark:border-purple-400 bg-purple-50/50 dark:bg-purple-950/20' : '',
                            draggedColumnIndex === columnIndex && @js($draggableColumns) ? 'opacity-50' : '',
                            column.class || ''
                        ]"
                        x-on:dragover.prevent="handleDragOver(columnIndex, $event)"
                        x-on:dragleave="handleDragLeave()"
                        x-on:drop="handleDrop(columnIndex, $event)"
                    >
                        @if($variant === 'colorful')
                        <div class="h-1.5 -mx-4 mb-3" :style="{ backgroundColor: column.color || '#6366f1' }"></div>
                        @endif

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
                            <div class="flex items-center gap-2 min-w-0">
                                @if($variant === 'colorful')
                                <span class="size-2.5 rounded-full shrink-0" :style="{ backgroundColor: column.color || '#6366f1' }"></span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-fg truncate" x-text="column.title || ''"></p>
                                    <template x-if="column.description">
                                        <p class="text-xs text-fg-muted mt-[2px]" x-text="column.description"></p>
                                    </template>
                                </div>
                            </div>
                            <template x-if="@js($showCount)">
                                <span class="text-[11px] font-semibold text-fg-muted bg-surface-inset px-2 py-1 rounded-full shrink-0" x-text="(column.cards || []).length"></span>
                            </template>
                        </div>

                        <div class="mt-4 flex flex-col {{ $v['cardGap'] }}">
                            <template x-for="(card, cardIndex) in (column.cards || [])" :key="cardIndex">
                                <article 
                                    :draggable="@js($draggable)"
                                    x-on:dragstart="handleDragStart(columnIndex, cardIndex, $event)"
                                    x-on:dragover.prevent=""
                                    x-on:drop="handleCardDrop(columnIndex, cardIndex, $event)"
                                    x-on:click.stop="if (@js($onCardClick) && !justDragged) { if (typeof $wire !== 'undefined' && $wire) { $wire.call(@js($onCardClick), card, columnIndex, cardIndex) } else { $dispatch('kanban-card-clicked', { card, columnIndex, cardIndex }) } }"
                                    :class="[
                                        'flex flex-col gap-2 {{ $v['card'] }} transition',
                                        draggedFromColumn === columnIndex && draggedFromIndex === cardIndex 
                                            ? 'opacity-50 border-blue-500 dark:border-blue-400' 
                                            : 'border-edge hover:border-edge-hover',
                                        @js($draggable) ? 'cursor-move' : (@js($onCardClick) ? 'cursor-pointer' : '')
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 space-y-1">
                                            <p class="text-sm font-semibold text-fg truncate" x-text="card.title || ''"></p>
                                            <template x-if="card.subtitle">
                                                <p class="text-xs text-fg-muted truncate" x-text="card.subtitle"></p>
                                            </template>
                                        </div>
                                        <template x-if="card.badge">
                                            <span class="text-[10px] font-semibold uppercase tracking-wide text-fg-secondary bg-surface-inset px-2 py-1 rounded-full" x-text="card.badge"></span>
                                        </template>
                                    </div>
                                    <template x-if="card.description">
                                        <p class="text-xs text-fg-muted" x-text="card.description"></p>
                                    </template>
                                    <template x-if="card.meta">
                                        <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-medium text-fg-muted">
                                            <template x-for="(metaItem, metaIndex) in (Array.isArray(card.meta) ? card.meta : [card.meta])" :key="metaIndex">
                                                <span class="px-2 py-1 rounded-full bg-surface-inset" x-text="metaItem"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="card.footer">
                                        <div class="mt-2 text-[11px] text-fg-disabled" x-text="card.footer"></div>
                                    </template>
                                </article>
                            </template>
                            <template x-if="!column.cards || column.cards.length === 0">
                                <p class="text-xs text-center text-fg-disabled italic" x-text="column.emptyState || @js($emptyStateLabel)"></p>
                            </template>
                        </div>

                        <template x-if="column.action && column.action.label">
                            <div class="mt-4">
                                <button 
                                    type="button" 
                                    :class="[
                                        'flex items-center justify-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-fg-secondary border border-dashed border-edge rounded-2xl px-3 py-2 transition hover:border-edge-hover hover:bg-hover',
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


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
    use Neura\Kit\Support\PackResolver;

    $boardColumns = collect($columns ?? []);
    $emptyStateLabel = $emptyState ?? neura_trans('noCardsYet');

    $kanbanRoundedClass = PackResolver::rounded(neura_config('kanban', 'rounded'));
    $kanbanShadowClass = PackResolver::shadow(neura_config('kanban', 'shadow'));
    $kanbanVariant = in_array($variant, ['default', 'compact', 'flat', 'outlined', 'colorful'], true) ? $variant : 'default';
@endphp

<div
    data-variant="{{ $kanbanVariant }}"
    x-data="{
        columns: @js($boardColumns->toArray()),
        draggedCard: null,
        draggedFromColumn: null,
        draggedFromIndex: null,
        dragOverColumn: null,
        draggedColumnIndex: null,
        dragOverColumnIndex: null,
        justDragged: false,
        status: '',
        moveToTemplate: @js(neura_trans('moveToColumn', null, 'Move to {column}')),
        movedTemplate: @js(neura_trans('cardMovedTo', null, '{title} moved to {column}')),
        
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
        
        handleDragLeave(event) {
            if (event.currentTarget && event.relatedTarget && event.currentTarget.contains(event.relatedTarget)) return;
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

            const insertAt = (this.columns[targetColumn].cards || []).length;
            this.placeCard(sourceColumn, this.draggedFromIndex, targetColumn, insertAt);
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

            const insertIndex = sourceColumn === targetColumn && this.draggedFromIndex < targetCardIndex
                ? targetCardIndex
                : targetCardIndex + 1;

            this.placeCard(sourceColumn, this.draggedFromIndex, targetColumn, insertIndex);
        },

        moveCard(fromColumn, fromIndex, toColumn) {
            if (toColumn < 0 || toColumn >= this.columns.length || toColumn === fromColumn) return;
            const insertAt = (this.columns[toColumn].cards || []).length;
            this.placeCard(fromColumn, fromIndex, toColumn, insertAt);
        },

        placeCard(sourceColumn, sourceIndex, targetColumn, insertIndex) {
            const before = this.captureCards();
            let movedCard = null;

            Alpine.mutateDom(() => {
                const updatedColumns = this.columns.map((col) => ({ ...col, cards: [...(col.cards || [])] }));
                const card = updatedColumns[sourceColumn].cards[sourceIndex];
                if (!card) return;

                updatedColumns[sourceColumn].cards.splice(sourceIndex, 1);

                if (!updatedColumns[targetColumn].cards) {
                    updatedColumns[targetColumn].cards = [];
                }

                const index = Math.max(0, Math.min(insertIndex, updatedColumns[targetColumn].cards.length));
                updatedColumns[targetColumn].cards.splice(index, 0, card);
                movedCard = card;
                this.columns = updatedColumns;

                this.$dispatch('kanban:card-moved', {
                    card: card,
                    fromColumn: sourceColumn,
                    toColumn: targetColumn,
                    fromIndex: sourceIndex,
                    toIndex: index,
                    columns: this.columns
                });
            });

            if (movedCard) this.announce(movedCard, targetColumn);
            this.draggedCard = null;
            this.draggedFromColumn = null;
            this.draggedFromIndex = null;
            this.dragOverColumn = null;
            this.justDragged = true;
            this.$nextTick(() => { this.justDragged = false; });
            this.playCards(before);
        },

        cardKey(card) {
            if (card && card.id != null && String(card.id) !== '') return 'id:' + card.id;
            if (!this.$root._kbKeys) this.$root._kbKeys = new WeakMap();
            if (!this.$root._kbSeq) this.$root._kbSeq = 1;
            const map = this.$root._kbKeys;
            if (!map.has(card)) map.set(card, 'k' + (this.$root._kbSeq++));
            return map.get(card);
        },

        captureCards() {
            const map = new Map();
            this.$root.querySelectorAll('[data-kb-card]').forEach((el) => {
                map.set(el.getAttribute('data-kb-card'), el.getBoundingClientRect());
            });
            return map;
        },

        playCards(before) {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const ease = 'cubic-bezier(0.16, 1, 0.3, 1)';
            const attempt = (tries) => {
                const els = [...this.$root.querySelectorAll('[data-kb-card]')];
                const ready = els.some((el) => {
                    const first = before.get(el.getAttribute('data-kb-card'));
                    if (!first) return true;
                    const last = el.getBoundingClientRect();
                    return Math.abs(first.left - last.left) > 1 || Math.abs(first.top - last.top) > 1;
                });
                if (!ready && tries < 10) {
                    requestAnimationFrame(() => attempt(tries + 1));
                    return;
                }
                els.forEach((el) => {
                    const key = el.getAttribute('data-kb-card');
                    const first = before.get(key);
                    el.getAnimations().forEach((animation) => animation.cancel());
                    if (!first) {
                        el.animate(
                            [{ opacity: 0, transform: 'scale(0.98)' }, { opacity: 1, transform: 'scale(1)' }],
                            { duration: 180, easing: ease }
                        );
                        return;
                    }
                    const last = el.getBoundingClientRect();
                    const dx = first.left - last.left;
                    const dy = first.top - last.top;
                    if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
                    el.animate(
                        [{ transform: 'translate(' + dx + 'px, ' + dy + 'px)' }, { transform: 'translate(0px, 0px)' }],
                        { duration: 320, easing: ease }
                    );
                });
            };
            this.$nextTick(() => requestAnimationFrame(() => attempt(0)));
        },

        announce(card, toColumn) {
            const title = (card && card.title) || '';
            const column = (this.columns[toColumn] && this.columns[toColumn].title) || '';
            this.status = this.movedTemplate.replace('{title}', title).replace('{column}', column);
        },

        moveLabel(columnIndex) {
            const column = (this.columns[columnIndex] && this.columns[columnIndex].title) || '';
            return this.moveToTemplate.replace('{column}', column);
        },

        assigneeName(card) {
            const person = card && card.assignee;
            if (!person) return '';
            if (typeof person === 'string') return person;
            return person.name || person.initials || '';
        },

        assigneeInitials(card) {
            const person = card && card.assignee;
            if (!person) return '';
            if (typeof person === 'object' && person.initials) return String(person.initials).slice(0, 2).toUpperCase();
            const name = this.assigneeName(card).trim();
            const parts = name.split(/\s+/).filter(Boolean);
            if (parts.length > 1) return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
            return name.slice(0, 2).toUpperCase();
        },

        assigneeSrc(card) {
            const person = card && card.assignee;
            return person && typeof person === 'object' && person.src ? person.src : '';
        },

        assigneeTone(card) {
            const seed = this.assigneeName(card);
            let hash = 0;
            for (let i = 0; i < seed.length; i++) hash = (hash + seed.charCodeAt(i)) % 5;
            return String(hash);
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
            
            const before = this.captureCards();
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
            this.playCards(before);
        }
    }"
    {{ $attributes->merge(['class' => 'nk-kanban']) }}
>
    <p class="nk-kanban-status" role="status" x-text="status"></p>
    @if($boardColumns->isEmpty())
        <div @class(['nk-kanban-board-empty', $kanbanRoundedClass])>
            {{ $emptyStateLabel }}
        </div>
    @else
        <div class="nk-kanban-scroll">
            <div class="nk-kanban-board">
                <template x-for="(column, columnIndex) in columns" :key="columnIndex">
                    <section
                        :class="[
                            'nk-kanban-col {{ $kanbanRoundedClass }}',
                            dragOverColumn === columnIndex ? 'is-over' : '',
                            dragOverColumnIndex === columnIndex && @js($draggableColumns) ? 'is-col-over' : '',
                            draggedColumnIndex === columnIndex && @js($draggableColumns) ? 'is-dragging' : '',
                            column.class || ''
                        ]"
                        x-on:dragover.prevent="handleDragOver(columnIndex, $event)"
                        x-on:dragleave="handleDragLeave($event)"
                        x-on:drop="handleDrop(columnIndex, $event)"
                    >
                        @if($kanbanVariant === 'colorful')
                        <div class="nk-kanban-accent" :style="'background:' + (column.color || 'var(--color-primary-500)')"></div>
                        @endif

                        <div
                            class="nk-kanban-head"
                            :class="@js($draggableColumns) ? 'is-handle' : ''"
                            :draggable="@js($draggableColumns)"
                            x-on:dragstart="handleColumnDragStart(columnIndex, $event)"
                            x-on:dragover.prevent="handleColumnDragOver(columnIndex, $event)"
                            x-on:dragleave="handleColumnDragLeave()"
                            x-on:drop="handleColumnDrop(columnIndex, $event)"
                        >
                            <div class="nk-kanban-heading">
                                @if($kanbanVariant === 'colorful')
                                <span class="nk-kanban-dot" :style="'background:' + (column.color || 'var(--color-primary-500)')"></span>
                                @endif
                                <div>
                                    <p class="nk-kanban-title" x-text="column.title || ''"></p>
                                    <template x-if="column.description">
                                        <p class="nk-kanban-desc" x-text="column.description"></p>
                                    </template>
                                </div>
                            </div>
                            <template x-if="@js($showCount)">
                                <span class="nk-kanban-count" x-text="(column.cards || []).length"></span>
                            </template>
                        </div>

                        <div class="nk-kanban-cards">
                            <template x-for="(card, cardIndex) in (column.cards || [])" :key="cardKey(card)">
                                <article
                                    :data-kb-card="cardKey(card)"
                                    :draggable="@js($draggable)"
                                    x-on:dragstart="handleDragStart(columnIndex, cardIndex, $event)"
                                    x-on:dragover.prevent=""
                                    x-on:drop="handleCardDrop(columnIndex, cardIndex, $event)"
                                    x-on:click.stop="if (@js($onCardClick) && !justDragged) { if (typeof $wire !== 'undefined' && $wire) { $wire.call(@js($onCardClick), card, columnIndex, cardIndex) } else { $dispatch('kanban-card-clicked', { card, columnIndex, cardIndex }) } }"
                                    :class="[
                                        'nk-kanban-card {{ $kanbanRoundedClass }} {{ $kanbanShadowClass }}',
                                        draggedFromColumn === columnIndex && draggedFromIndex === cardIndex ? 'is-dragging' : '',
                                        @js($draggable) ? 'is-draggable' : (@js($onCardClick) ? 'is-clickable' : '')
                                    ]"
                                >
                                    <div class="nk-kanban-card-top">
                                        <div class="nk-kanban-card-copy">
                                            <p class="nk-kanban-card-title" x-text="card.title || ''"></p>
                                            <template x-if="card.subtitle">
                                                <p class="nk-kanban-sub" x-text="card.subtitle"></p>
                                            </template>
                                        </div>
                                        <template x-if="card.badge">
                                            <span class="nk-kanban-badge" :data-tone="String(card.badge || '').toLowerCase()" x-text="card.badge"></span>
                                        </template>
                                    </div>
                                    <template x-if="card.description">
                                        <p class="nk-kanban-body" x-text="card.description"></p>
                                    </template>
                                    <template x-if="card.meta">
                                        <div class="nk-kanban-meta">
                                            <template x-for="(metaItem, metaIndex) in (Array.isArray(card.meta) ? card.meta : [card.meta])" :key="metaIndex">
                                                <span class="nk-kanban-chip" x-text="metaItem"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="card.footer">
                                        <p class="nk-kanban-footer" x-text="card.footer"></p>
                                    </template>
                                    <template x-if="assigneeName(card)">
                                        <div class="nk-kanban-person">
                                            <span class="nk-kanban-avatar" :data-tone="assigneeTone(card)">
                                                <template x-if="assigneeSrc(card)">
                                                    <img :src="assigneeSrc(card)" alt="" />
                                                </template>
                                                <template x-if="!assigneeSrc(card)">
                                                    <span x-text="assigneeInitials(card)"></span>
                                                </template>
                                            </span>
                                            <span class="nk-kanban-person-name" x-text="assigneeName(card)"></span>
                                        </div>
                                    </template>
                                    <div class="nk-kanban-moves" x-show="columns.length > 1">
                                        <template x-if="columnIndex > 0">
                                            <button
                                                type="button"
                                                class="nk-kanban-move"
                                                :aria-label="moveLabel(columnIndex - 1)"
                                                x-on:click.stop="moveCard(columnIndex, cardIndex, columnIndex - 1)"
                                            >
                                                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                                    <path d="M10 3.5 5.5 8 10 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template x-if="columnIndex < columns.length - 1">
                                            <button
                                                type="button"
                                                class="nk-kanban-move"
                                                :aria-label="moveLabel(columnIndex + 1)"
                                                x-on:click.stop="moveCard(columnIndex, cardIndex, columnIndex + 1)"
                                            >
                                                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                                    <path d="M6 3.5 10.5 8 6 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </article>
                            </template>
                            <template x-if="!column.cards || column.cards.length === 0">
                                <p class="nk-kanban-empty" x-text="column.emptyState || @js($emptyStateLabel)"></p>
                            </template>
                        </div>

                        <template x-if="column.action && column.action.label">
                            <button
                                type="button"
                                class="nk-kanban-add"
                                :class="[column.action.class || '', (column.action.attributes && column.action.attributes.class) || '']"
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
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </template>
                                <span x-text="column.action.label"></span>
                            </button>
                        </template>
                    </section>
                </template>
            </div>
        </div>
    @endif
</div>


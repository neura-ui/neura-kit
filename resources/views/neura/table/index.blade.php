@php
    $classes = [
        'relative',
        'rounded-md border border-neutral-200 dark:border-neutral-800',
        'bg-white dark:bg-neutral-950',
    ];
@endphp

<div class="space-y-4" x-data="{
    resizing: false,
    resizeColumn: null,
    startX: 0,
    startWidth: 0,
    currentTh: null,
    columnWidths: @js($columnWidths ?? []),
    handleResize: null,
    stopResize: null,
    initResize(event, columnKey) {
        event.preventDefault();
        event.stopPropagation();
        this.resizing = true;
        this.resizeColumn = columnKey;
        this.startX = event.clientX;
        this.currentTh = event.currentTarget.closest('th');
        this.startWidth = this.currentTh.offsetWidth;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';

        this.handleResize = (e) => {
            if (!this.resizing || !this.currentTh) return;
            e.preventDefault();
            e.stopPropagation();
            const diff = e.clientX - this.startX;
            const minWidth = parseInt(this.currentTh.style.minWidth) || 50;
            const maxWidth = parseInt(this.currentTh.style.maxWidth) || Infinity;
            const newWidth = Math.max(minWidth, Math.min(maxWidth, this.startWidth + diff));

            this.currentTh.style.width = newWidth + 'px';
            const columnIndex = Array.from(this.currentTh.parentElement.children).indexOf(this.currentTh) + 1;
            const allTds = document.querySelectorAll(`tbody tr td:nth-child(${columnIndex})`);
            allTds.forEach(td => {
                td.style.width = newWidth + 'px';
            });

            this.columnWidths[this.resizeColumn] = newWidth;
        };

        this.stopResize = () => {
            if (!this.resizing) return;
            const finalWidth = this.columnWidths[this.resizeColumn];
            if (finalWidth) {
                @this.set('columnWidths.' + this.resizeColumn, finalWidth);
            }
            this.resizing = false;
            this.resizeColumn = null;
            this.currentTh = null;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            document.removeEventListener('mousemove', this.handleResize);
            document.removeEventListener('mouseup', this.stopResize);
            this.handleResize = null;
            this.stopResize = null;
        };

        document.addEventListener('mousemove', this.handleResize);
        document.addEventListener('mouseup', this.stopResize);
    }
}">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-1">
            <div class="relative flex-1 max-w-sm">
                <neura::input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ neura_trans('search') }}"
                    leftIcon="magnifying-glass"
                    clearable
                    size="sm"
                />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <neura::dropdown position="bottom-end">
                <x-slot:button>
                    <neura::button variant="outline" size="sm" class="gap-2" icon="funnel" iconAfter="chevron-down" iconVariant="mini" iconClasses="size-4 !text-neutral-600 dark:!text-neutral-400">
                        <span>{{ neura_trans('filters') }}</span>
                        @if(count(array_filter($filters ?? [])))
                            <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-neutral-900 dark:bg-neutral-50 text-white dark:text-neutral-950">
                                {{ count(array_filter($filters ?? [])) }}
                            </span>
                        @endif
                    </neura::button>
                </x-slot:button>

                <x-slot:menu class="min-w-64!">
                    <div class="p-3 space-y-3">
                        @forelse($this->getFilterableColumns() as $column)
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                                    {{ $column->label }}
                                </label>
                                <neura::table.filter-input :column="$column" />
                            </div>
                        @empty
                            <div class="text-sm text-neutral-500 dark:text-neutral-400 text-center py-2">
                                {{ neura_trans('noFilterableColumns') }}
                            </div>
                        @endforelse

                        @if($this->hasActiveFilters())
                            <div class="pt-2 border-t border-neutral-200 dark:border-neutral-800">
                                <neura::button wire:click="clearFilters" variant="ghost" size="sm" class="w-full justify-center">
                                    {{ neura_trans('clearFilters') }}
                                </neura::button>
                            </div>
                        @endif
                    </div>
                </x-slot:menu>
            </neura::dropdown>

            <neura::dropdown position="bottom-end">
                <x-slot:button>
                    <neura::button variant="outline" size="sm" class="gap-2" icon="eye" iconAfter="chevron-down" iconVariant="mini" iconClasses="size-4 !text-neutral-600 dark:!text-neutral-400">
                        <span>{{ __('Columns') }}</span>
                    </neura::button>
                </x-slot:button>

                <x-slot:menu class="min-w-64!">
                    <div class="p-2 space-y-1">
                        @foreach($this->columns() as $column)
                            <div class="px-2 py-1.5 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-colors">
                                <neura::checkbox wire:model.live="visibleColumns.{{ $column->key }}" label="{{ $column->label }}"/>
                            </div>
                        @endforeach
                    </div>
                </x-slot:menu>
            </neura::dropdown>

            @if(count($this->actions()) > 0)
                <div class="flex items-center gap-2">
                    @foreach($this->actions() as $action)
                        @if(is_string($action))
                            {!! $action !!}
                        @else
                            {!! $action->render() !!}
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="{{ \Illuminate\Support\Arr::toCssClasses($classes) }}" style="position: relative;">
        <div class="relative w-full overflow-x-auto">
            <table class="w-full border-collapse text-sm" style="table-layout: fixed; min-width: 100%;">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                        @foreach($this->getVisibleColumns() as $column)
                            <th
                                class="h-10 px-3 text-left align-middle font-normal text-neutral-500 dark:text-neutral-400 relative group border-r border-neutral-200 dark:border-neutral-800 last:border-r-0"
                                data-column-key="{{ $column->key }}"
                                x-bind:style="'width: ' + (columnWidths['{{ $column->key }}'] ? columnWidths['{{ $column->key }}'] + 'px' : '{{ $column->width ? $column->width . 'px' : 'auto' }}') + '; min-width: {{ $column->minWidth ?? 100 }}px; {{ $column->maxWidth ? 'max-width: ' . $column->maxWidth . 'px;' : '' }}'"
                            >
                                <div
                                    @class([
                                        'flex items-center gap-2 flex-1 min-w-0 pr-4',
                                        'cursor-pointer select-none hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors' => $column->sortable,
                                    ])
                                    @if($column->sortable)
                                        wire:click="sort('{{ $column->key }}')"
                                    @endif
                                >
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ $column->label }}</span>
                                    @if($column->sortable && $sortBy === $column->key)
                                        @if ($sortDirection === 'asc')
                                            <neura::icon name="arrow-up" class="size-3 text-neutral-400 dark:text-neutral-500 shrink-0" />
                                        @else
                                            <neura::icon name="arrow-down" class="size-3 text-neutral-400 dark:text-neutral-500 shrink-0" />
                                        @endif
                                    @endif
                                </div>
                                @if($column->resizable ?? true)
                                    <div
                                        class="absolute right-0 top-0 bottom-0 w-1.5 cursor-col-resize hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors z-20"
                                        style="margin-right: -2px;"
                                        @mousedown.prevent.stop="initResize($event, '{{ $column->key }}')"
                                        x-bind:class="{'bg-blue-500 dark:bg-blue-600': resizing && resizeColumn === '{{ $column->key }}'}"
                                        title="{{ neura_trans('dragToResize') }}"
                                    ></div>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->data() as $index => $row)
                        <tr class="border-b border-neutral-200 dark:border-neutral-800 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 group">
                            @foreach($this->getVisibleColumns() as $columnIndex => $column)
                                <td
                                    class="px-3 py-2 align-middle border-r border-neutral-200 dark:border-neutral-800 last:border-r-0 bg-white dark:bg-neutral-950 overflow-hidden"
                                    data-column-key="{{ $column->key }}"
                                    x-bind:style="'width: ' + (columnWidths['{{ $column->key }}'] ? columnWidths['{{ $column->key }}'] + 'px' : '{{ $column->width ? $column->width . 'px' : 'auto' }}')"
                                >
                                    <div class="text-sm text-neutral-900 dark:text-neutral-100">
                                        <x-dynamic-component
                                            :component="$column->component"
                                            :value="$row->{$column->key} ?? data_get($row, $column->key)"
                                            :row="$row"
                                            :column="$column"
                                            :format="$column->format"
                                            :formatUsing="$column->formatUsing"
                                            :html="$column->html"
                                            :extraAttributes="$column->extraAttributes"
                                        />
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->getVisibleColumns()) }}" class="p-8 text-center text-neutral-500 dark:text-neutral-400">
                                {{ neura_trans('noResultsFound') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between">
        {{ $this->data()->links('neura::table.pagination') }}
    </div>
</div>

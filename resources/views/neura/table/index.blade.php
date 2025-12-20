@php
    use Illuminate\Support\Arr;

    $rows = $this->data();
    $columns = $this->visibleColumns();
    $sortBy = $this->sortBy ?? '';
    $sortDirection = $this->sortDirection ?? 'asc';
    $columnWidths = $this->columnWidths ?? [];

    $containerClasses = [
        'relative',
        'rounded-xl',
        'bg-white dark:bg-neutral-950',
        'border border-neutral-200/60 dark:border-neutral-800/60',
        'shadow-sm',
        'overflow-hidden',
    ];
@endphp

<div class="space-y-3">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2">
        <div class="flex-1 max-w-xs flex gap-2 items-center">
            <div class="max-w-sm">
                @if ($this->hasSearchableColumns())
                    <neura::input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ neura_trans('search') }}"
                        leftIcon="magnifying-glass"
                        clearable
                        size="sm"
                    />
                @endif
            </div>
            <div wire:loading>
                <neura::icon.loading />
            </div>
        </div>

        <div class="flex items-center gap-1">
            @if ($this->hasBulkActions())
                <neura::dropdown position="bottom-end" :disabled="empty($selected)">
                    <x-slot:button>
                        <neura::button
                            variant="ghost"
                            size="sm"
                            icon="squares-plus"
                            :disabled="empty($selected)"
                        >
                            Bulk actions
                        </neura::button>
                    </x-slot:button>

                    <x-slot:menu>
                        @foreach ($this->bulkActions() as $action)
                            <neura::dropdown.item
                                wire:click="runBulkAction('{{ $action['key'] }}')"
                            >
                                {{ $action['label'] }}
                            </neura::dropdown.item>
                        @endforeach
                    </x-slot:menu>
                </neura::dropdown>
            @endif
            @if (count($columns) > 1)
                <neura::dropdown position="bottom-end">
                    <x-slot:button>
                        <neura::button
                            variant="ghost"
                            size="sm"
                            icon="view-columns"
                        >
                            Columns
                        </neura::button>
                    </x-slot:button>

                    <x-slot:menu>
                        @foreach ($this->columns() as $column)
                            @if (!isset($column->key))
                                @continue
                            @endif

                            <neura::dropdown.item class="flex items-center gap-2">
                                <neura::checkbox
                                    mode="boolean"
                                    wire:model.live="visibleColumns.{{ $column->key }}"
                                    size="sm"
                                    label="{{ $column->label }}"/>
                            </neura::dropdown.item>
                        @endforeach
                    </x-slot:menu>
                </neura::dropdown>
            @endif

            @foreach ($this->actions() as $action)
                {!! is_string($action) ? $action : $action->render() !!}
            @endforeach
        </div>
    </div>

    @if ($this->hasBulkActions() && !empty($selected))
        <div
            class="mx-4 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-blue-900 dark:text-blue-100">
                        <neura::icon name="check-circle"/>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">
                            {{ $this->selectedCount }} {{ $this->selectedCount === 1 ? neura_trans('rowSelected') : neura_trans('rowsSelected') }}
                        </span>
                    </div>

                    @if (!$selectAll && $this->selectedCount < $this->totalRows && $selectPage)
                        <button wire:click="selectAllRows"
                                class="text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 font-medium underline">
                            {{ neura_trans('selectAllRows', ['count' => $this->totalRows]) }}
                        </button>
                    @endif

                    @if ($selectAll)
                        <span class="text-sm text-blue-700 dark:text-blue-300">
                            {{ neura_trans('allRowsSelected', ['count' => $this->totalRows]) }}
                        </span>
                    @endif
                </div>

                <button
                    wire:click="deselectAllRows"
                    class="text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 font-medium">
                    {{ neura_trans('deselectAll') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="{{ Arr::toCssClasses($containerClasses) }}">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-sm">
                <thead>
                <tr>
                    @if ($this->hasBulkActions())
                        <th class="w-10 px-4">
                            <neura::checkbox
                                wire:model.live="selectPage"
                                size="sm"
                            />
                        </th>
                    @endif

                    @foreach ($columns as $index => $column)
                        @php
                            $isSortable = $column->sortable ?? false;
                            $isResizable = $column->resizable ?? false;
                            $isCurrentSort = $sortBy === $column->key;
                            $width = $columnWidths[$column->key] ?? $column->width ?? null;
                        @endphp
                        <th
                            @if($width) style="width: {{ $width }}px; min-width: {{ $column->minWidth ?? 50 }}px; max-width: {{ $column->maxWidth ?? 'none' }};" @endif
                            class="h-11 px-4 text-left text-xs font-medium text-neutral-500 relative group {{ $isSortable ? 'cursor-pointer select-none hover:bg-neutral-50 dark:hover:bg-neutral-900' : '' }}"
                            @if($isSortable)
                                wire:click="sort('{{ $column->key }}')"
                            @endif
                        >
                            <div class="flex items-center gap-1.5">
                                <span>{{ $column->label }}</span>
                                @if($isSortable)
                                    <span class="flex flex-col">
                                        @if($isCurrentSort && $sortDirection === 'asc')
                                            <neura::icon name="chevron-up" class="w-3.5 h-3.5 text-primary-500" />
                                        @elseif($isCurrentSort && $sortDirection === 'desc')
                                            <neura::icon name="chevron-down" class="w-3.5 h-3.5 text-primary-500" />
                                        @else
                                            <neura::icon name="chevron-up-down" class="w-3.5 h-3.5 text-neutral-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        @endif
                                    </span>
                                @endif
                            </div>
                            @if($isResizable && !$loop->last)
                                <div
                                    class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-primary-500 active:bg-primary-600"
                                    x-data="{ startX: 0, startWidth: 0, resizing: false }"
                                    x-on:mousedown.stop.prevent="
                                        resizing = true;
                                        startX = $event.clientX;
                                        startWidth = $el.parentElement.offsetWidth;
                                        document.body.style.cursor = 'col-resize';
                                        document.body.style.userSelect = 'none';

                                        const onMouseMove = (e) => {
                                            if (!resizing) return;
                                            const diff = e.clientX - startX;
                                            const newWidth = Math.max({{ $column->minWidth ?? 50 }}, Math.min({{ $column->maxWidth ?? 500 }}, startWidth + diff));
                                            $el.parentElement.style.width = newWidth + 'px';
                                        };

                                        const onMouseUp = () => {
                                            resizing = false;
                                            document.body.style.cursor = '';
                                            document.body.style.userSelect = '';
                                            const newWidth = $el.parentElement.offsetWidth;
                                            $wire.set('columnWidths.{{ $column->key }}', newWidth);
                                            document.removeEventListener('mousemove', onMouseMove);
                                            document.removeEventListener('mouseup', onMouseUp);
                                        };

                                        document.addEventListener('mousemove', onMouseMove);
                                        document.addEventListener('mouseup', onMouseUp);
                                    "
                                ></div>
                            @endif
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>

                        @if ($this->hasBulkActions())
                            <td class="px-4">
                                <neura::checkbox.group wire:model.live="selected">
                                    <neura::checkbox value="{{ $row->{$this->getRowKey()} }}" size="sm"/>
                                </neura::checkbox.group>
                            </td>
                        @endif
                        @foreach ($columns as $column)
                            @php
                                $width = $columnWidths[$column->key] ?? $column->width ?? null;
                            @endphp
                            <td
                                class="px-4 py-2.5 truncate"
                                @if($width) style="width: {{ $width }}px; max-width: {{ $width }}px;" @endif
                            >
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
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + ($this->hasBulkActions() ? 1 : 0) }}">
                            <div class="py-12 text-center text-sm text-neutral-500">
                                {!! $this->emptyStateHtml() !!}
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="pt-3">
        {{ $rows->links('neura::table.pagination') }}
    </div>
</div>

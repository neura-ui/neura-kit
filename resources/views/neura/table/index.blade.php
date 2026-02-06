@php
    use Illuminate\Support\Arr;

    $rows = $this->data();
    $columns = $this->visibleColumns();
    $sortBy = $this->sortBy ?? '';
    $sortDirection = $this->sortDirection ?? 'asc';
    $columnWidths = $this->columnWidths ?? [];
@endphp

<div class="space-y-0">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-1 pb-4">
        <div class="flex-1 flex gap-2 items-center max-w-xs">
            @if ($this->hasSearchableColumns())
                <div class="relative w-full">
                    <neura::input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ neura_trans('search') }}"
                        leftIcon="magnifying-glass"
                        clearable
                        size="sm"
                    />
                </div>
            @endif
            <div wire:loading class="shrink-0">
                <neura::icon.loading />
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            @if ($this->hasBulkActions())
                <neura::dropdown position="bottom-end" :disabled="empty($selected)">
                    <x-slot:button>
                        <neura::button
                            variant="ghost"
                            size="sm"
                            icon="squares-plus"
                            :disabled="empty($selected)"
                        >
                            {{ neura_trans('bulkActions') }}
                        </neura::button>
                    </x-slot:button>

                    <x-slot:menu>
                        @foreach ($this->getNormalizedBulkActions() as $action)
                            @php
                                $itemVariant = match($action['variant'] ?? 'soft') {
                                    'danger', 'danger-soft', 'danger-ghost' => 'danger',
                                    default => 'soft',
                                };
                            @endphp
                            <neura::dropdown.item
                                wire:click="runBulkAction('{{ $action['key'] }}')"
                                variant="{{ $itemVariant }}"
                                icon="{{ $action['icon'] ?? '' }}"
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
                        <neura::button variant="ghost" size="sm" icon="view-columns" />
                    </x-slot:button>

                    <x-slot:menu>
                        @foreach ($this->columns() as $column)
                            @if (!isset($column->key)) @continue @endif
                            <neura::dropdown.item class="flex items-center gap-2">
                                <neura::checkbox
                                    mode="boolean"
                                    wire:model.live="visibleColumns.{{ $column->key }}"
                                    size="sm"
                                    label="{{ $column->label }}"
                                />
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

    {{-- Bulk Selection Banner --}}
    @if ($this->hasBulkActions() && !empty($selected))
        <div class="mb-3 rounded-lg bg-primary-50 dark:bg-primary-950/20 border border-primary-200/60 dark:border-primary-800/40 px-4 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-primary-800 dark:text-primary-200">
                        <neura::icon name="check-circle" class="size-4" />
                        <span class="font-medium">
                            {{ $this->selectedCount }} {{ $this->selectedCount === 1 ? neura_trans('rowSelected') : neura_trans('rowsSelected') }}
                        </span>
                    </div>

                    @if (!$selectAll && $this->selectedCount < $this->totalRows && $selectPage)
                        <button wire:click="selectAllRows"
                            class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-200 font-medium underline underline-offset-2">
                            {{ neura_trans('selectAllRows', ['count' => $this->totalRows]) }}
                        </button>
                    @endif

                    @if ($selectAll)
                        <span class="text-sm text-primary-600 dark:text-primary-400">
                            {{ neura_trans('allRowsSelected', ['count' => $this->totalRows]) }}
                        </span>
                    @endif
                </div>

                <button wire:click="deselectAllRows"
                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-200 font-medium">
                    {{ neura_trans('deselectAll') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="rounded-lg border border-neutral-200/70 dark:border-neutral-800/70 bg-white dark:bg-neutral-950 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                {{-- Header --}}
                <thead class="sticky top-0 z-10">
                    <tr class="bg-neutral-50/80 dark:bg-neutral-900/80 backdrop-blur-sm border-b border-neutral-200/70 dark:border-neutral-800/70">
                        @if ($this->hasBulkActions() && $rows->total() > 0)
                            <th class="w-10 pl-4 pr-2 py-2.5">
                                <neura::checkbox wire:model.live="selectPage" size="sm" />
                            </th>
                        @endif

                        @foreach ($columns as $index => $column)
                            @php
                                $isSortable = $column->sortable ?? false;
                                $isResizable = $column->resizable ?? false;
                                $isCurrentSort = $sortBy === $column->key;
                                $width = $columnWidths[$column->key] ?? $column->width ?? ($isResizable ? 150 : null);
                            @endphp
                            <th @if($width) style="width: {{ $width }}px; min-width: {{ $width }}px;" @endif
                                class="px-4 py-2.5 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider whitespace-nowrap relative group select-none {{ $isSortable ? 'cursor-pointer hover:text-neutral-700 dark:hover:text-neutral-300 transition-colors' : '' }}"
                                @if($isSortable)
                                    wire:click="sort('{{ $column->key }}')"
                                @endif
                            >
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $column->label }}</span>
                                    @if($isSortable)
                                        @if($isCurrentSort && $sortDirection === 'asc')
                                            <neura::icon name="chevron-up" class="size-3.5 text-primary-500" />
                                        @elseif($isCurrentSort && $sortDirection === 'desc')
                                            <neura::icon name="chevron-down" class="size-3.5 text-primary-500" />
                                        @else
                                            <neura::icon name="chevron-up-down" class="size-3.5 text-neutral-300 dark:text-neutral-600 opacity-0 group-hover:opacity-100 transition-opacity duration-150" />
                                        @endif
                                    @endif
                                </div>

                                @if($isResizable && !$loop->last)
                                    @php $bulkActionsOffset = $this->hasBulkActions() ? 1 : 0; @endphp
                                    <div
                                        class="absolute right-0 top-1.5 bottom-1.5 w-0.5 cursor-col-resize rounded-full bg-transparent hover:bg-primary-400 active:bg-primary-500 transition-colors"
                                        x-data="{ resizing: false }"
                                        x-on:mousedown.stop.prevent="
                                            if (resizing) return;
                                            resizing = true;
                                            const th = $el.parentElement;
                                            const startX = $event.clientX;
                                            const startWidth = th.offsetWidth;
                                            const minW = {{ $column->minWidth ?? 50 }};
                                            const bulkOffset = {{ $bulkActionsOffset }};
                                            document.body.style.cursor = 'col-resize';
                                            document.body.style.userSelect = 'none';
                                            const onMouseMove = (e) => {
                                                const diff = e.clientX - startX;
                                                const newWidth = Math.max(minW, startWidth + diff);
                                                th.style.width = newWidth + 'px';
                                                th.style.minWidth = newWidth + 'px';
                                                const colIndex = Array.from(th.parentElement.children).indexOf(th);
                                                document.querySelectorAll('tbody tr td:nth-child(' + (colIndex + 1 + bulkOffset) + ')').forEach(td => {
                                                    td.style.width = newWidth + 'px';
                                                    td.style.minWidth = newWidth + 'px';
                                                });
                                            };
                                            const onMouseUp = () => {
                                                resizing = false;
                                                document.body.style.cursor = '';
                                                document.body.style.userSelect = '';
                                                document.removeEventListener('mousemove', onMouseMove);
                                                document.removeEventListener('mouseup', onMouseUp);
                                                $wire.resizeColumn('{{ $column->key }}', Math.round(th.offsetWidth));
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

                {{-- Body --}}
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800/60">
                    @forelse ($rows as $row)
                        <tr class="group transition-colors duration-100 hover:bg-neutral-50/70 dark:hover:bg-neutral-900/40">
                            @if ($this->hasBulkActions())
                                <td class="pl-4 pr-2 py-2">
                                    <neura::checkbox.group wire:model.live="selected">
                                        <neura::checkbox value="{{ $row->{$this->getRowKey()} }}" size="sm" />
                                    </neura::checkbox.group>
                                </td>
                            @endif

                            @foreach ($columns as $column)
                                @php
                                    $tdWidth = $columnWidths[$column->key] ?? $column->width ?? ($column->resizable ?? false ? 150 : null);
                                @endphp
                                <td class="px-4 py-2.5 whitespace-nowrap"
                                    @if($tdWidth) style="width: {{ $tdWidth }}px; min-width: {{ $tdWidth }}px;" @endif
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
                                <div class="flex flex-col items-center justify-center py-16 px-6">
                                    <div class="size-12 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
                                        <neura::icon name="inbox" class="size-6 text-neutral-400 dark:text-neutral-500" />
                                    </div>
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400 text-center">
                                        {!! $this->emptyStateHtml() !!}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($rows->hasPages())
        <div class="pt-4">
            {{ $rows->links('neura::table.pagination') }}
        </div>
    @endif
</div>

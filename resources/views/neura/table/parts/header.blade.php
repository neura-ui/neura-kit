@props([
    'columns' => [],
    'rows',
    'sortBy' => '',
    'sortDirection' => 'asc',
    'columnWidths' => [],
    'theadBgClass' => '',
    'thPadding' => '',
])

<thead class="sticky top-0 z-10">
    <tr class="border-b border-separator">
        @if ($this->hasBulkActions() && $rows->total() > 0)
            <th class="w-10 pl-3 pr-1 py-2 {{ $theadBgClass }}">
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
                class="{{ $thPadding }} text-left font-medium whitespace-nowrap relative group select-none {{ $theadBgClass }} {{ $isSortable ? 'cursor-pointer' : '' }}"
                @if($isSortable)
                    wire:click="sort('{{ $column->key }}')"
                    aria-sort="{{ $isCurrentSort ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                @endif
            >
                <div class="flex items-center gap-1">
                    <span>{{ $column->label }}</span>
                    @if($isSortable)
                        <neura::icon
                            name="chevron-up"
                            class="nk-table-sort {{ $isCurrentSort ? 'is-on' : '' }} {{ $isCurrentSort && $sortDirection === 'desc' ? 'is-desc' : '' }}"
                        />
                    @endif
                </div>

                @if($isResizable && !$loop->last)
                    @php $bulkActionsOffset = $this->hasBulkActions() ? 1 : 0; @endphp
                    <div
                        class="nk-table-resize"
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

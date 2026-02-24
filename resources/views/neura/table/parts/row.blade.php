@props([
    'row',
    'columns' => [],
    'columnWidths' => [],
    'rowClass' => '',
    'tdPadding' => 'px-3 py-2',
])

@php
    $rowId = $row->{$this->getRowKey()};
@endphp

<tr class="{{ $rowClass }}">
    @if ($this->hasBulkActions())
        <td class="pl-3 pr-1 py-1.5">
            <neura::checkbox.group wire:model.live="selected">
                <neura::checkbox value="{{ $rowId }}" size="sm" />
            </neura::checkbox.group>
        </td>
    @endif

    @foreach ($columns as $column)
        @php
            $tdWidth = $columnWidths[$column->key] ?? $column->width ?? ($column->resizable ?? false ? 150 : null);
            $cellValue = $row->{$column->key} ?? data_get($row, $column->key);
        @endphp

        <neura::table.parts.cell
            :column="$column"
            :row="$row"
            :cellValue="$cellValue"
            :rowId="$rowId"
            :tdPadding="$tdPadding"
            :tdWidth="$tdWidth"
        />
    @endforeach
</tr>

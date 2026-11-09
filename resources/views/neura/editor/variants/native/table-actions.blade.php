{{--
    Table actions, shared by the toolbar's table menu and the right-click menu.

    `close` is the expression that shuts the menu the actions sit in.
--}}

@props(['close' => ''])

@php
    $t = fn (string $key, string $fallback) => neura_trans('editor'.$key, null, $fallback);
    $then = fn (string $action) => trim($close) === '' ? $action : "{$action}; {$close}";

    $structure = [
        [$t('RowAbove', 'Insert row above'), "insertRow('above')"],
        [$t('RowBelow', 'Insert row below'), "insertRow('below')"],
        [$t('ColumnLeft', 'Insert column left'), "insertColumn('left')"],
        [$t('ColumnRight', 'Insert column right'), "insertColumn('right')"],
    ];

    $fills = ['#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#d9d2e9', '#efefef'];
    $inks = ['#000000', '#666666', '#b7b7b7', '#cc0000', '#e69138', '#6aa84f', '#3c78d8', '#674ea7'];
    $weights = [
        'none' => $t('BorderNone', 'No border'),
        'thin' => $t('BorderThin', 'Thin'),
        'medium' => $t('BorderMedium', 'Medium'),
        'thick' => $t('BorderThick', 'Thick'),
    ];
@endphp

{{-- Colours and borders unfold in place, so the menu stays short. --}}
<div class="nk-table-actions" x-data="{ section: '' }">
<p class="nk-table-menu-note" x-show="cellCount > 1" x-cloak>
    <span x-text="cellCount"></span> {{ $t('CellsPicked', 'cells selected') }}
</p>

@foreach($structure as [$label, $action])
    <button type="button" class="nk-editor-menu-item" x-on:click="{{ $then($action) }}">{{ $label }}</button>
@endforeach

<div class="nk-table-menu-divider" aria-hidden="true"></div>

<button type="button" class="nk-editor-menu-item" x-bind:disabled="!canMerge()" x-on:click="{{ $then('mergeCells()') }}">
    {{ $t('MergeCells', 'Merge cells') }}
</button>
<button type="button" class="nk-editor-menu-item" x-bind:disabled="!canUnmerge()" x-on:click="{{ $then('unmergeCells()') }}">
    {{ $t('UnmergeCells', 'Unmerge cells') }}
</button>
<button type="button" class="nk-editor-menu-item" x-on:click="{{ $then('toggleHeaderRow()') }}">
    {{ $t('HeaderRow', 'Toggle header row') }}
</button>
<button type="button" class="nk-editor-menu-item" x-bind:disabled="!tableInfo()?.sized" x-on:click="{{ $then('distributeColumns()') }}">
    {{ $t('DistributeColumns', 'Distribute columns') }}
</button>

<div class="nk-table-menu-divider" aria-hidden="true"></div>

<button type="button" class="nk-editor-menu-item nk-table-unfold" x-bind:aria-expanded="section === 'fill' ? 'true' : 'false'" x-on:click="section = section === 'fill' ? '' : 'fill'; $dispatch('nk-unfold')">
    {{ $t('CellColor', 'Cell colour') }}
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</button>
<div class="nk-table-menu-section" role="group" aria-label="{{ $t('CellColor', 'Cell colour') }}" x-show="section === 'fill'">
    <div class="nk-table-swatches">
        @foreach($fills as $color)
            <button type="button" class="nk-table-swatch" style="background: {{ $color }}" title="{{ $color }}" aria-label="{{ $color }}"
                x-on:click="{{ $then("setCellFill('{$color}')") }}"></button>
        @endforeach
        <button type="button" class="nk-table-swatch is-none" title="{{ $t('NoColor', 'None') }}" aria-label="{{ $t('NoColor', 'None') }}"
            x-on:click="{{ $then('setCellFill(null)') }}"></button>
    </div>
</div>

<button type="button" class="nk-editor-menu-item nk-table-unfold" x-bind:aria-expanded="section === 'borders' ? 'true' : 'false'" x-on:click="section = section === 'borders' ? '' : 'borders'; $dispatch('nk-unfold')">
    {{ $t('Borders', 'Borders') }}
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
</button>
<div class="nk-table-menu-section" role="group" aria-label="{{ $t('Borders', 'Borders') }}" x-show="section === 'borders'">
    <div class="nk-table-weights">
        @foreach($weights as $weight => $label)
            <button type="button" class="nk-table-weight" title="{{ $label }}" aria-label="{{ $label }}" data-weight="{{ $weight }}"
                x-bind:aria-pressed="(tableInfo()?.border ?? 'thin') === '{{ $weight }}' ? 'true' : 'false'"
                x-on:click="{{ $then("setTableBorder('{$weight}')") }}"><span aria-hidden="true"></span></button>
        @endforeach
    </div>
    <div class="nk-table-swatches" role="group" aria-label="{{ $t('BorderColor', 'Border colour') }}">
        @foreach($inks as $color)
            <button type="button" class="nk-table-swatch" style="background: {{ $color }}" title="{{ $color }}" aria-label="{{ $color }}"
                x-bind:aria-pressed="tableInfo()?.borderColor === '{{ $color }}' ? 'true' : 'false'"
                x-on:click="{{ $then("setTableBorderColor('{$color}')") }}"></button>
        @endforeach
        <button type="button" class="nk-table-swatch is-none" title="{{ $t('NoColor', 'None') }}" aria-label="{{ $t('NoColor', 'None') }}"
            x-on:click="{{ $then('setTableBorderColor(null)') }}"></button>
    </div>
</div>

<div class="nk-table-menu-divider" aria-hidden="true"></div>

<button type="button" class="nk-editor-menu-item" x-show="cellCount > 1" x-cloak x-on:click="{{ $then('clearPickedCells()') }}">
    {{ $t('ClearCells', 'Clear cells') }}
</button>
@foreach([[$t('DeleteRow', 'Delete row'), 'deleteRow()'], [$t('DeleteColumn', 'Delete column'), 'deleteColumn()'], [$t('DeleteTable', 'Delete table'), 'deleteTable()']] as [$label, $action])
    <button type="button" class="nk-editor-menu-item is-danger" x-on:click="{{ $then($action) }}">{{ $label }}</button>
@endforeach
</div>

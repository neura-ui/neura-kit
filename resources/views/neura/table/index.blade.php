@php
    use Illuminate\Support\Arr;

    $rows = $this->data();
    $columns = $this->visibleColumns();
    $sortBy = $this->sortBy ?? '';
    $sortDirection = $this->sortDirection ?? 'asc';
    $columnWidths = $this->columnWidths ?? [];
    $hasPagination = $rows->hasPages();

    $styles = $this->getTableStyles();
    $v = $styles['variant'];
    $r = $styles['rounded'];
    $s = $styles['shadow'];
    $d = $styles['density'];
    $isBordered = $styles['bordered'];

    $wrapperClass = Arr::toCssClasses([
        'w-full',
        $v['wrapper'],
        !$isBordered ? '!border-transparent !ring-0' : '',
        $r['wrapper'],
        $s,
    ]);

    $toolbarClass = Arr::toCssClasses([
        'relative z-20 flex flex-wrap items-center justify-between gap-2',
        $d['toolbar'],
        $v['toolbar'],
        !$isBordered ? '!border-transparent' : '',
        $r['toolbar'],
    ]);

    $theadBgClass = $v['thead'] . ' backdrop-blur-sm';

    $rowClass = Arr::toCssClasses([
        'group last:border-b-0 transition-colors duration-75',
        $v['row'],
        !$isBordered ? '!border-transparent' : '',
    ]);

    $footerClass = Arr::toCssClasses([
        $d['footer'],
        $v['footer'],
        !$isBordered ? '!border-transparent' : '',
        $r['footer'],
    ]);

    $thPadding = $d['th'];
    $tdPadding = $d['td'];
    $textSize = $d['text'];
    $colspan = count($columns) + ($this->hasBulkActions() ? 1 : 0);
@endphp

<div class="{{ $wrapperClass }}">

    <neura::table.parts.toolbar
        :columns="$columns"
        :toolbarClass="$toolbarClass"
    />

    <neura::table.parts.bulk-banner />

    <div class="overflow-x-auto overflow-y-visible">
        <table class="w-full {{ $textSize }} min-w-max">

            <neura::table.parts.header
                :columns="$columns"
                :rows="$rows"
                :sortBy="$sortBy"
                :sortDirection="$sortDirection"
                :columnWidths="$columnWidths"
                :theadBgClass="$theadBgClass"
                :thPadding="$thPadding"
            />

            <tbody>
                @forelse ($rows as $row)
                    <neura::table.parts.row
                        :row="$row"
                        :columns="$columns"
                        :columnWidths="$columnWidths"
                        :rowClass="$rowClass"
                        :tdPadding="$tdPadding"
                    />
                @empty
                    <neura::table.parts.empty :colspan="$colspan" />
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($hasPagination)
        <div class="{{ $footerClass }}">
            {{ $rows->links('neura::table.parts.pagination') }}
        </div>
    @endif
</div>

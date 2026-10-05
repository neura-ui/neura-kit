@props([
    'colspan' => 1,
])

<tr>
    <td colspan="{{ $colspan }}">
        <div class="nk-table-empty">
            <div class="nk-table-empty-mark">
                <neura::icon name="inbox" class="size-5" />
            </div>
            <div class="text-sm">
                {!! $this->emptyStateHtml() !!}
            </div>
        </div>
    </td>
</tr>

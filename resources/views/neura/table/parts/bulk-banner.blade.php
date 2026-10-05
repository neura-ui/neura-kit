@if ($this->hasBulkActions() && !empty($this->selected))
    <div class="nk-table-selection">
        <div class="flex items-center gap-3">
            <span class="font-medium">
                {{ $this->selectedCount }} {{ $this->selectedCount === 1 ? neura_trans('rowSelected') : neura_trans('rowsSelected') }}
            </span>

            @if (!$this->selectAll && $this->selectedCount < $this->totalRows && $this->selectPage)
                <button type="button" wire:click="selectAllRows">
                    {{ neura_trans('selectAllRows', ['count' => $this->totalRows]) }}
                </button>
            @endif

            @if ($this->selectAll)
                <span class="nk-table-muted">
                    {{ neura_trans('allRowsSelected', ['count' => $this->totalRows]) }}
                </span>
            @endif
        </div>

        <button type="button" wire:click="deselectAllRows">
            {{ neura_trans('deselectAll') }}
        </button>
    </div>
@endif

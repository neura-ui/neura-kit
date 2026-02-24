@if ($this->hasBulkActions() && !empty($selected))
    <div class="bg-primary-50/60 dark:bg-primary-500/[0.04] border-b border-primary-100 dark:border-primary-500/10 px-3 py-1.5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-primary-700 dark:text-primary-300">
                    <span class="size-1.5 rounded-full bg-primary-500 shrink-0"></span>
                    <span class="font-medium">
                        {{ $this->selectedCount }} {{ $this->selectedCount === 1 ? neura_trans('rowSelected') : neura_trans('rowsSelected') }}
                    </span>
                </div>

                @if (!$selectAll && $this->selectedCount < $this->totalRows && $selectPage)
                    <button wire:click="selectAllRows"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-200 font-medium underline underline-offset-2">
                        {{ neura_trans('selectAllRows', ['count' => $this->totalRows]) }}
                    </button>
                @endif

                @if ($selectAll)
                    <span class="text-xs text-primary-500 dark:text-primary-400">
                        {{ neura_trans('allRowsSelected', ['count' => $this->totalRows]) }}
                    </span>
                @endif
            </div>

            <button wire:click="deselectAllRows"
                class="text-xs text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200 font-medium transition-colors">
                {{ neura_trans('deselectAll') }}
            </button>
        </div>
    </div>
@endif

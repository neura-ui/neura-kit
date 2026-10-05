@props([
    'columns' => [],
    'toolbarClass' => '',
])

<div class="{{ $toolbarClass }}">
    <div class="flex-1 flex gap-2 items-center min-w-0">
        @if ($this->hasSearchableColumns())
            <div class="nk-table-search">
                <neura::icon name="magnifying-glass" class="size-4 shrink-0" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ neura_trans('search') }}..."
                    aria-label="{{ neura_trans('search') }}"
                />
                @if($search ?? false)
                    <button type="button" wire:click="$set('search', '')" class="nk-table-iconbtn shrink-0" aria-label="Clear search">
                        <neura::icon name="x-mark" class="size-3.5" />
                    </button>
                @endif
            </div>
        @endif
        <div wire:loading class="shrink-0">
            <neura::spinner variant="ring" size="sm" color="current" />
        </div>
    </div>

    <div class="flex items-center gap-0.5 shrink-0">
        @if ($this->hasBulkActions())
            <neura::dropdown position="bottom-end" :disabled="empty($this->selected)">
                <x-slot:button>
                    <button
                        class="nk-table-iconbtn disabled:opacity-30 disabled:pointer-events-none"
                        @if(empty($this->selected)) disabled @endif
                    >
                        <neura::icon name="squares-plus" class="size-4" />
                    </button>
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
                    <button type="button" class="nk-table-iconbtn" aria-label="Columns">
                        <neura::icon name="adjustments-horizontal" class="size-4" />
                    </button>
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

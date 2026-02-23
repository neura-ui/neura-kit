@php
    use Illuminate\Support\Arr;

    $rows = $this->data();
    $columns = $this->visibleColumns();
    $sortBy = $this->sortBy ?? '';
    $sortDirection = $this->sortDirection ?? 'asc';
    $columnWidths = $this->columnWidths ?? [];
    $hasPagination = $rows->hasPages();
@endphp

<div class="w-full rounded-xl border border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-white/[0.02]">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 border-b border-neutral-100 dark:border-white/[0.06] rounded-t-xl bg-white dark:bg-white/[0.02]">
        <div class="flex-1 flex gap-2 items-center min-w-0">
            @if ($this->hasSearchableColumns())
                <div class="relative flex items-center gap-2 flex-1 max-w-xs" x-data="{ focused: false }">
                    <neura::icon name="magnifying-glass" class="size-4 text-neutral-400 dark:text-neutral-500 shrink-0 transition-colors" x-bind:class="focused ? '!text-neutral-600 dark:!text-neutral-300' : ''" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ neura_trans('search') }}..."
                        x-on:focus="focused = true"
                        x-on:blur="focused = false"
                        class="w-full bg-transparent border-none outline-none ring-0 focus:ring-0 focus:outline-none p-0 text-[13px] text-neutral-900 dark:text-neutral-100 placeholder:text-neutral-400 dark:placeholder:text-neutral-500"
                    />
                    @if($search ?? false)
                        <button wire:click="$set('search', '')" class="shrink-0 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300 transition-colors">
                            <neura::icon name="x-mark" class="size-3.5" />
                        </button>
                    @endif
                </div>
            @endif
            <div wire:loading class="shrink-0">
                <neura::icon.loading class="size-4" />
            </div>
        </div>

        <div class="flex items-center gap-0.5 shrink-0">
            @if ($this->hasBulkActions())
                <neura::dropdown position="bottom-end" :disabled="empty($selected)">
                    <x-slot:button>
                        <button
                            class="inline-flex items-center justify-center size-7 rounded-md text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.06] transition-colors duration-100 disabled:opacity-30 disabled:pointer-events-none"
                            @if(empty($selected)) disabled @endif
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
                        <button class="inline-flex items-center justify-center size-7 rounded-md text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.06] transition-colors duration-100">
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

    {{-- Bulk Selection Banner --}}
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

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-max">
            {{-- Header --}}
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-neutral-200 dark:border-white/[0.08]">
                    @if ($this->hasBulkActions() && $rows->total() > 0)
                        <th class="w-10 pl-3 pr-1 py-2 bg-neutral-50/80 dark:bg-white/[0.025] backdrop-blur-sm">
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
                            class="px-3 py-2 text-left text-[11px] font-medium text-neutral-500 dark:text-neutral-400 whitespace-nowrap relative group select-none bg-neutral-50/80 dark:bg-white/[0.025] backdrop-blur-sm {{ $isSortable ? 'cursor-pointer hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors' : '' }}"
                            @if($isSortable)
                                wire:click="sort('{{ $column->key }}')"
                            @endif
                        >
                            <div class="flex items-center gap-1">
                                <span>{{ $column->label }}</span>
                                @if($isSortable)
                                    @if($isCurrentSort && $sortDirection === 'asc')
                                        <neura::icon name="chevron-up" class="size-3 text-neutral-900 dark:text-neutral-100" />
                                    @elseif($isCurrentSort && $sortDirection === 'desc')
                                        <neura::icon name="chevron-down" class="size-3 text-neutral-900 dark:text-neutral-100" />
                                    @else
                                        <neura::icon name="chevron-up-down" class="size-3 text-neutral-400 dark:text-neutral-500 opacity-0 group-hover:opacity-100 transition-opacity duration-100" />
                                    @endif
                                @endif
                            </div>

                            @if($isResizable && !$loop->last)
                                @php $bulkActionsOffset = $this->hasBulkActions() ? 1 : 0; @endphp
                                <div
                                    class="absolute right-0 top-1 bottom-1 w-px cursor-col-resize bg-neutral-200 dark:bg-white/[0.08] hover:bg-primary-400 dark:hover:bg-primary-400 hover:w-0.5 active:bg-primary-500 transition-all"
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
            <tbody>
                @forelse ($rows as $row)
                    <tr class="group border-b border-neutral-100 dark:border-white/[0.04] last:border-b-0 transition-colors duration-75 hover:bg-neutral-50/70 dark:hover:bg-white/[0.02]">
                        @if ($this->hasBulkActions())
                            <td class="pl-3 pr-1 py-1.5">
                                <neura::checkbox.group wire:model.live="selected">
                                    <neura::checkbox value="{{ $row->{$this->getRowKey()} }}" size="sm" />
                                </neura::checkbox.group>
                            </td>
                        @endif

                        @foreach ($columns as $column)
                            @php
                                $tdWidth = $columnWidths[$column->key] ?? $column->width ?? ($column->resizable ?? false ? 150 : null);
                                $isEditable = $column->editable ?? false;
                                $editType = $column->editableType ?? 'text';
                                $cellValue = $row->{$column->key} ?? data_get($row, $column->key);
                                $rowId = $row->{$this->getRowKey()};
                            @endphp
                            <td class="{{ $isEditable ? 'p-0' : 'px-3 py-2' }} whitespace-nowrap"
                                @if($tdWidth) style="width: {{ $tdWidth }}px; min-width: {{ $tdWidth }}px;" @endif
                            >
                                @if ($isEditable && $editType === 'boolean')
                                    <div class="px-3 py-2 cursor-pointer select-none hover:bg-neutral-50 dark:hover:bg-white/[0.02] transition-colors flex items-center justify-between gap-2"
                                        wire:click="updateField({{ Js::from($rowId) }}, '{{ $column->key }}', {{ $cellValue ? 'false' : 'true' }})"
                                    >
                                        <x-dynamic-component
                                            :component="$column->component"
                                            :value="$cellValue"
                                            :row="$row"
                                            :column="$column"
                                            :format="$column->format"
                                            :formatUsing="$column->formatUsing"
                                            :html="$column->html"
                                            :extraAttributes="$column->extraAttributes"
                                        />
                                        <neura::icon name="pencil-square" variant="micro" class="size-3 shrink-0 text-neutral-300 dark:text-neutral-600 opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                @elseif ($isEditable)
                                    <div x-data="{
                                        editing: false,
                                        draft: {{ Js::from((string) ($cellValue ?? '')) }},
                                        original: {{ Js::from((string) ($cellValue ?? '')) }},
                                        save() {
                                            if (this.draft !== this.original) {
                                                $wire.updateField({{ Js::from($rowId) }}, '{{ $column->key }}', this.draft);
                                                this.original = this.draft;
                                            }
                                            this.editing = false;
                                        },
                                        cancel() {
                                            this.draft = this.original;
                                            this.editing = false;
                                        },
                                        start() {
                                            this.editing = true;
                                            this.$nextTick(() => {
                                                this.$refs.editInput?.focus();
                                                if (this.$refs.editInput?.select) this.$refs.editInput.select();
                                            });
                                        }
                                    }">
                                        <div x-show="!editing"
                                            @click="start()"
                                            class="px-3 py-2 cursor-text rounded-sm hover:bg-primary-50/40 dark:hover:bg-primary-500/[0.04] transition-colors flex items-center justify-between gap-2"
                                        >
                                            <x-dynamic-component
                                                :component="$column->component"
                                                :value="$cellValue"
                                                :row="$row"
                                                :column="$column"
                                                :format="$column->format"
                                                :formatUsing="$column->formatUsing"
                                                :html="$column->html"
                                                :extraAttributes="$column->extraAttributes"
                                            />
                                            <neura::icon name="pencil-square" variant="micro" class="size-3 shrink-0 text-neutral-300 dark:text-neutral-600 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>

                                        <div x-show="editing" x-cloak class="px-1.5 py-1">
                                            @if ($editType === 'select')
                                                <select
                                                    x-ref="editInput"
                                                    x-model="draft"
                                                    @change="save()"
                                                    @keydown.escape.prevent="cancel()"
                                                    @blur="save()"
                                                    class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-md px-2 py-1 text-[13px] text-neutral-900 dark:text-neutral-100 outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 dark:focus:border-primary-500/40 transition-shadow"
                                                >
                                                    @foreach ($column->editableOptions ?? [] as $optVal => $optLabel)
                                                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($editType === 'textarea')
                                                <textarea
                                                    x-ref="editInput"
                                                    x-model="draft"
                                                    @keydown.escape.prevent="cancel()"
                                                    @blur="save()"
                                                    rows="2"
                                                    class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-md px-2 py-1 text-[13px] text-neutral-900 dark:text-neutral-100 outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 dark:focus:border-primary-500/40 transition-shadow resize-none"
                                                ></textarea>
                                            @else
                                                <input
                                                    x-ref="editInput"
                                                    type="{{ $editType === 'number' ? 'number' : ($editType === 'date' ? 'date' : 'text') }}"
                                                    x-model="draft"
                                                    @keydown.enter="save()"
                                                    @keydown.escape.prevent="cancel()"
                                                    @blur="save()"
                                                    class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-md px-2 py-1 text-[13px] text-neutral-900 dark:text-neutral-100 outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 dark:focus:border-primary-500/40 transition-shadow"
                                                />
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <x-dynamic-component
                                        :component="$column->component"
                                        :value="$cellValue"
                                        :row="$row"
                                        :column="$column"
                                        :format="$column->format"
                                        :formatUsing="$column->formatUsing"
                                        :html="$column->html"
                                        :extraAttributes="$column->extraAttributes"
                                    />
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + ($this->hasBulkActions() ? 1 : 0) }}">
                            <div class="flex flex-col items-center justify-center py-16 px-6">
                                <div class="size-10 rounded-full bg-neutral-100 dark:bg-white/[0.04] flex items-center justify-center mb-3">
                                    <neura::icon name="inbox" class="size-5 text-neutral-400 dark:text-neutral-500" />
                                </div>
                                <div class="text-xs text-neutral-400 dark:text-neutral-500 text-center">
                                    {!! $this->emptyStateHtml() !!}
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($hasPagination)
        <div class="border-t border-neutral-100 dark:border-white/[0.06] px-3 py-2 rounded-b-xl bg-white dark:bg-white/[0.02]">
            {{ $rows->links('neura::table.pagination') }}
        </div>
    @endif
</div>

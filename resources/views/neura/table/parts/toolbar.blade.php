@props([
    'columns' => [],
    'toolbarClass' => '',
])

<div class="{{ $toolbarClass }}">
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
            <neura::dropdown position="bottom-end" :disabled="empty($this->selected)">
                <x-slot:button>
                    <button
                        class="inline-flex items-center justify-center size-7 rounded-md text-neutral-400 dark:text-neutral-500 hover:text-neutral-600 dark:hover:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-white/[0.06] transition-colors duration-100 disabled:opacity-30 disabled:pointer-events-none"
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

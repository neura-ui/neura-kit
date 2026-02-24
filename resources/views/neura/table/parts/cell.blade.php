@props([
    'column',
    'row',
    'cellValue' => null,
    'rowId' => null,
    'tdPadding' => 'px-3 py-2',
    'tdWidth' => null,
])

@php
    $isEditable = $column->editable ?? false;
    $editType = $column->editableType ?? 'text';
@endphp

<td class="{{ $isEditable ? 'p-0' : $tdPadding }} whitespace-nowrap"
    @if($tdWidth) style="width: {{ $tdWidth }}px; min-width: {{ $tdWidth }}px;" @endif
>
    @if ($isEditable && $editType === 'boolean')
        <div class="{{ $tdPadding }} cursor-pointer select-none hover:bg-neutral-50 dark:hover:bg-white/[0.02] transition-colors flex items-center justify-between gap-2"
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
                class="{{ $tdPadding }} cursor-text rounded-sm hover:bg-primary-50/40 dark:hover:bg-primary-500/[0.04] transition-colors flex items-center justify-between gap-2"
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

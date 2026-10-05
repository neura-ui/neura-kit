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
        <div class="{{ $tdPadding }} cursor-pointer select-none hover:bg-hover transition-colors flex items-center justify-between gap-2"
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
            <neura::icon name="pencil-square" variant="micro" class="size-3 shrink-0 text-fg-disabled opacity-0 group-hover:opacity-100 transition-opacity" />
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
                class="{{ $tdPadding }} cursor-text rounded-sm hover:bg-hover transition-colors flex items-center justify-between gap-2"
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
                <neura::icon name="pencil-square" variant="micro" class="size-3 shrink-0 text-fg-disabled opacity-0 group-hover:opacity-100 transition-opacity" />
            </div>

            <div x-show="editing" x-cloak class="px-1.5 py-1">
                @if ($editType === 'select')
                    <select
                        x-ref="editInput"
                        x-model="draft"
                        @change="save()"
                        @keydown.escape.prevent="cancel()"
                        @blur="save()"
                        class="nk-table-editor"
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
                        class="nk-table-editor resize-none"
                    ></textarea>
                @else
                    <input
                        x-ref="editInput"
                        type="{{ $editType === 'number' ? 'number' : ($editType === 'date' ? 'date' : 'text') }}"
                        x-model="draft"
                        @keydown.enter="save()"
                        @keydown.escape.prevent="cancel()"
                        @blur="save()"
                        class="nk-table-editor"
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

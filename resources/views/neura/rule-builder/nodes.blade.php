@props([
    'nodesExpr' => 'parent.children',
    'parentExpr' => 'parent',
    'depth' => 0,
    'maxDepth' => 3,
    'sizeConfig',
    'roundedClass',
    'controlClass',
    'rowClass',
    'chipClass',
    'railClass',
    'iconClass',
])

@php
    $nextDepth = $depth + 1;
    $pillClass = $controlClass . ' inline-flex items-center gap-1 rounded-md border border-edge/80 bg-surface-raised/80 text-fg hover:bg-hover disabled:opacity-50';
    $ghostPillClass = $controlClass . ' inline-flex items-center gap-1 rounded-md text-fg-secondary hover:bg-hover hover:text-fg disabled:opacity-50';
@endphp

<template x-for="(item, index) in {{ $nodesExpr }}" :key="item.id">
    <div class="relative flex gap-2">
        {{-- Rail --}}
        <div class="{{ $railClass }} relative flex shrink-0 flex-col items-center self-stretch">
            <div
                class="pointer-events-none absolute start-1/2 top-3 bottom-0 w-px -translate-x-1/2 border-s border-dashed border-edge"
                x-show="index < ({{ $nodesExpr }}).length - 1"
                aria-hidden="true"
            ></div>

            <div class="relative z-[1] mt-2 flex size-7 items-center justify-center">
                <span class="text-fg-muted" x-show="item.locked" x-cloak x-bind:title="label('lockedCondition')">
                    <neura::icon name="lock-closed" class="{{ $iconClass }}" />
                </span>
                <button
                    type="button"
                    class="cursor-grab text-fg-muted/70 hover:text-fg active:cursor-grabbing disabled:opacity-40"
                    draggable="true"
                    x-show="!item.locked"
                    x-on:dragstart="onDragStart('when', ({{ $parentExpr }}).id, item.id, $event)"
                    x-on:dragend="onDragEnd()"
                    x-bind:disabled="isDisabled"
                    x-bind:title="label('dragToReorder')"
                >
                    <svg class="{{ $iconClass }}" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <circle cx="5" cy="3.5" r="1.1" />
                        <circle cx="11" cy="3.5" r="1.1" />
                        <circle cx="5" cy="8" r="1.1" />
                        <circle cx="11" cy="8" r="1.1" />
                        <circle cx="5" cy="12.5" r="1.1" />
                        <circle cx="11" cy="12.5" r="1.1" />
                    </svg>
                </button>
            </div>

            <button
                type="button"
                class="absolute start-1/2 bottom-0 z-[1] -translate-x-1/2 translate-y-1/2 inline-flex items-center rounded-md border border-edge bg-surface px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-fg-muted shadow-sm hover:bg-hover hover:text-fg disabled:opacity-50"
                x-show="index < ({{ $nodesExpr }}).length - 1"
                x-cloak
                x-text="({{ $parentExpr }}).combinator === 'or' ? label('or') : label('and')"
                x-on:click="toggleCombinator(({{ $parentExpr }}).id)"
                x-bind:disabled="isDisabled || ({{ $parentExpr }}).locked"
                x-bind:title="label('toggleCombinator')"
            ></button>
        </div>

        <div
            class="min-w-0 flex-1 pb-5"
            x-bind:class="{
                'opacity-50': isDragging(item.id),
                'ring-1 ring-fg/15 rounded-xl': isDragOver(item.id),
            }"
            x-on:dragover="onDragOver('when', ({{ $parentExpr }}).id, item.id, $event)"
            x-on:drop="onDrop('when', ({{ $parentExpr }}).id, item.id, $event)"
        >
            {{-- RULE (x-show avoids empty Alpine x-if shells) --}}
            <div x-show="item.kind === 'rule'" x-cloak>
                <div
                    class="{{ $rowClass }} flex flex-wrap items-center gap-x-1.5 rounded-xl border border-edge/70 bg-surface-raised/60 text-sm text-fg"
                    x-show="item.locked"
                >
                    <span class="font-medium" x-text="fieldConfig(item.field)?.label ?? item.field"></span>
                    <span class="text-fg-muted" x-text="operatorLabel(item.operator)"></span>
                    <span class="font-medium" x-text="item.values.map(v => optionLabel(item.field, v)).join(', ')"></span>
                </div>

                <div
                    class="{{ $rowClass }} flex flex-wrap items-center gap-1.5 rounded-xl border border-edge/70 bg-surface-raised/40"
                    x-show="!item.locked"
                >
                    <div class="relative">
                        <button type="button" class="{{ $pillClass }}" x-on:click="toggleOpen('field-' + item.id)" x-bind:disabled="isDisabled">
                            <span x-text="fieldConfig(item.field)?.label ?? label('selectField')"></span>
                            <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                        </button>
                        <div
                            x-show="open === 'field-' + item.id" x-cloak x-on:click.outside="closeOpen()"
                            class="absolute start-0 top-full z-50 mt-1 min-w-44 overflow-hidden rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                        >
                            <template x-for="field in fieldsList" :key="field.key">
                                <button type="button" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover" x-on:click="setField(item.id, field.key); closeOpen()">
                                    <span x-text="field.label"></span>
                                    <neura::icon name="check" class="ms-auto size-3.5" x-show="item.field === field.key" />
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative">
                        <button type="button" class="{{ $ghostPillClass }}" x-on:click="toggleOpen('op-' + item.id)" x-bind:disabled="isDisabled">
                            <span x-text="operatorLabel(item.operator)"></span>
                            <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                        </button>
                        <div
                            x-show="open === 'op-' + item.id" x-cloak x-on:click.outside="closeOpen()"
                            class="absolute start-0 top-full z-50 mt-1 min-w-44 overflow-hidden rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                        >
                            <template x-for="op in (fieldConfig(item.field)?.operators ?? [])" :key="op">
                                <button type="button" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover" x-on:click="setOperator(item.id, op); closeOpen()">
                                    <span x-text="operatorLabel(op)"></span>
                                    <neura::icon name="check" class="ms-auto size-3.5" x-show="item.operator === op" />
                                </button>
                            </template>
                        </div>
                    </div>

                    <template x-if="needsValue(item.operator) && fieldConfig(item.field) && fieldConfig(item.field).type === 'text'">
                        <input
                            type="text"
                            class="{{ $controlClass }} min-w-28 rounded-md border border-edge/80 bg-surface text-fg placeholder:text-fg-muted focus:outline-none focus:ring-1 focus:ring-fg/15"
                            x-bind:placeholder="fieldConfig(item.field)?.placeholder || label('selectValue')"
                            x-bind:value="item.values[0] ?? ''"
                            x-on:input="setTextValue(item.id, $event.target.value)"
                            x-bind:disabled="isDisabled"
                        />
                    </template>

                    <template x-if="needsValue(item.operator) && fieldConfig(item.field) && fieldConfig(item.field).type === 'select'">
                        <div class="relative">
                            <button type="button" class="{{ $pillClass }}" x-on:click="toggleOpen('val-' + item.id)" x-bind:disabled="isDisabled">
                                <span x-text="item.values.length ? optionLabel(item.field, item.values[0]) : label('selectValue')"></span>
                                <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                            </button>
                            <div
                                x-show="open === 'val-' + item.id" x-cloak x-on:click.outside="closeOpen()"
                                class="absolute start-0 top-full z-50 mt-1 max-h-56 min-w-44 overflow-auto rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                            >
                                <template x-for="opt in (fieldConfig(item.field)?.options ?? [])" :key="String(opt.value)">
                                    <button type="button" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover" x-on:click="setSelectValue(item.id, opt.value); closeOpen()">
                                        <span x-text="opt.label"></span>
                                        <neura::icon name="check" class="ms-auto size-3.5" x-show="item.values.includes(opt.value)" />
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="needsValue(item.operator) && fieldConfig(item.field) && fieldConfig(item.field).type === 'multiselect'">
                        <div class="flex min-w-0 flex-wrap items-center gap-1">
                            <template x-for="val in item.values" :key="String(val)">
                                <span class="{{ $chipClass }} inline-flex items-center rounded-md border border-edge/80 bg-surface text-fg">
                                    <span x-text="optionLabel(item.field, val)"></span>
                                    <button type="button" class="ms-0.5 text-fg-muted hover:text-fg disabled:opacity-40" x-on:click="removeMultiValue(item.id, val)" x-bind:disabled="isDisabled">
                                        <neura::icon name="x-mark" class="size-3" />
                                    </button>
                                </span>
                            </template>
                            <div class="relative">
                                <button
                                    type="button"
                                    class="{{ $chipClass }} inline-flex items-center justify-center rounded-md border border-dashed border-edge text-fg-muted hover:bg-hover hover:text-fg disabled:opacity-40"
                                    x-on:click="toggleOpen('multi-' + item.id)"
                                    x-bind:disabled="isDisabled"
                                >
                                    <neura::icon name="plus" class="size-3" />
                                </button>
                                <div
                                    x-show="open === 'multi-' + item.id" x-cloak x-on:click.outside="closeOpen()"
                                    class="absolute start-0 top-full z-50 mt-1 max-h-56 min-w-44 overflow-auto rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                                >
                                    <template x-for="opt in (fieldConfig(item.field)?.options ?? [])" :key="String(opt.value)">
                                        <button type="button" class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover" x-on:click="toggleMultiValue(item.id, opt.value)">
                                            <span
                                                class="flex size-4 shrink-0 items-center justify-center rounded border border-edge"
                                                x-bind:class="item.values.includes(opt.value) ? 'bg-fg text-surface border-fg' : 'bg-surface'"
                                            >
                                                <neura::icon name="check" class="size-2.5" x-show="item.values.includes(opt.value)" />
                                            </span>
                                            <span x-text="opt.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button
                        type="button"
                        class="ms-auto shrink-0 rounded-md p-1.5 text-fg-muted hover:bg-hover hover:text-fg disabled:opacity-40"
                        x-on:click="removeNode(item.id)"
                        x-bind:disabled="isDisabled"
                        x-bind:title="label('removeRule')"
                    >
                        <neura::icon name="trash" class="{{ $iconClass }}" />
                    </button>
                </div>
            </div>

            {{-- GROUP --}}
            <div x-show="item.kind === 'group'" x-cloak class="rounded-2xl border border-edge/80 bg-surface-inset/80 p-3 {{ $roundedClass }}">
                <div class="mb-3 flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md border border-edge bg-surface px-2 py-1 text-xs font-semibold text-fg hover:bg-hover disabled:opacity-50"
                        x-on:click="toggleCombinator(item.id)"
                        x-bind:disabled="isDisabled || item.locked"
                        x-bind:title="label('toggleCombinator')"
                    >
                        <span x-text="item.combinator === 'or' ? label('or') : label('and')"></span>
                        <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                    </button>
                    <span class="text-xs text-fg-muted" x-text="label('groupLabel', { count: countConditions(item) })"></span>
                    <button
                        type="button"
                        class="ms-auto rounded-md p-1.5 text-fg-muted hover:bg-hover hover:text-fg disabled:opacity-40"
                        x-on:click="removeNode(item.id)"
                        x-bind:disabled="isDisabled || item.locked"
                        x-bind:title="label('removeGroup')"
                    >
                        <neura::icon name="trash" class="{{ $iconClass }}" />
                    </button>
                </div>

                <div class="flex flex-col" x-data="{ parent: item }">
                    @if ($nextDepth < $maxDepth)
                        @include('neura::rule-builder.nodes', [
                            'nodesExpr' => 'parent.children',
                            'parentExpr' => 'parent',
                            'depth' => $nextDepth,
                            'maxDepth' => $maxDepth,
                            'sizeConfig' => $sizeConfig,
                            'roundedClass' => $roundedClass,
                            'controlClass' => $controlClass,
                            'rowClass' => $rowClass,
                            'chipClass' => $chipClass,
                            'railClass' => $railClass,
                            'iconClass' => $iconClass,
                        ])
                    @else
                        <template x-for="leaf in parent.children.filter(c => c.kind === 'rule')" :key="leaf.id">
                            <div class="{{ $rowClass }} mb-2 flex flex-wrap items-center gap-x-1.5 rounded-xl border border-edge/70 bg-surface-raised/40 text-sm">
                                <span class="font-medium" x-text="fieldConfig(leaf.field)?.label ?? leaf.field"></span>
                                <span class="text-fg-muted" x-text="operatorLabel(leaf.operator)"></span>
                                <span class="font-medium" x-text="leaf.values.map(v => optionLabel(leaf.field, v)).join(', ')"></span>
                                <button type="button" class="ms-auto rounded-md p-1.5 text-fg-muted hover:bg-hover hover:text-fg" x-on:click="removeNode(leaf.id)" x-bind:disabled="isDisabled || leaf.locked">
                                    <neura::icon name="trash" class="{{ $iconClass }}" />
                                </button>
                            </div>
                        </template>
                    @endif

                    <div class="ps-7 pt-0.5">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-fg-secondary hover:text-fg disabled:opacity-40"
                            x-on:click="addCondition(parent.id)"
                            x-bind:disabled="isDisabled || parent.locked"
                        >
                            <neura::icon name="plus" class="{{ $iconClass }}" />
                            <span x-text="label('addCondition')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

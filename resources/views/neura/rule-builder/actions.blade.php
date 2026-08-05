@props([
    'branch', // 'ifTrue' | 'ifFalse'
    'dragKind', // 'then-true' | 'then-false'
    'railClass',
    'rowClass',
    'controlClass',
    'iconClass',
])

@php
    $pillClass = $controlClass . ' inline-flex items-center gap-1 rounded-md border border-edge/80 bg-surface-raised/80 text-fg hover:bg-hover disabled:opacity-50';
@endphp

<div class="flex flex-col">
    <template x-for="(action, index) in value.then.{{ $branch }}" :key="action.id">
        <div
            class="relative flex gap-2"
            x-bind:class="{
                'opacity-50': isDragging(action.id),
                'ring-1 ring-fg/15 rounded-xl': isDragOver(action.id),
            }"
            x-on:dragover="onDragOver('{{ $dragKind }}', '{{ $branch }}', action.id, $event)"
            x-on:drop="onDrop('{{ $dragKind }}', '{{ $branch }}', action.id, $event)"
        >
            {{-- Rail --}}
            <div class="{{ $railClass }} relative flex shrink-0 flex-col items-center self-stretch">
                <div
                    class="pointer-events-none absolute start-1/2 top-3 bottom-0 w-px -translate-x-1/2 border-s border-dashed border-edge"
                    x-show="index < value.then.{{ $branch }}.length - 1"
                    aria-hidden="true"
                ></div>

                <div class="relative z-[1] mt-2 flex size-7 items-center justify-center">
                    <button
                        type="button"
                        class="cursor-grab text-fg-muted/70 hover:text-fg active:cursor-grabbing disabled:opacity-40"
                        draggable="true"
                        x-on:dragstart="onDragStart('{{ $dragKind }}', '{{ $branch }}', action.id, $event)"
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

                <span
                    class="absolute start-1/2 bottom-0 z-[1] -translate-x-1/2 translate-y-1/2 inline-flex items-center rounded-md border border-edge bg-surface px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-fg-muted shadow-sm"
                    x-show="index < value.then.{{ $branch }}.length - 1"
                    x-cloak
                    x-text="label('and')"
                ></span>
            </div>

            {{-- Action row --}}
            <div class="{{ $rowClass }} mb-5 flex min-w-0 flex-1 flex-wrap items-center gap-1.5 rounded-xl border border-edge/70 bg-surface-raised/40">
                <div class="relative">
                    <button
                        type="button"
                        class="{{ $pillClass }}"
                        x-on:click="toggleOpen('act-{{ $branch }}-' + action.id)"
                        x-bind:disabled="isDisabled"
                    >
                        <span x-text="actionDef(action.type)?.label ?? label('selectAction')"></span>
                        <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                    </button>
                    <div
                        x-show="open === 'act-{{ $branch }}-' + action.id"
                        x-cloak
                        x-on:click.outside="closeOpen()"
                        class="absolute start-0 top-full z-50 mt-1 min-w-44 overflow-hidden rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                    >
                        <template x-for="def in actions" :key="def.key">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover"
                                x-on:click="setActionType('{{ $branch }}', action.id, def.key); closeOpen()"
                            >
                                <span x-text="def.label"></span>
                                <neura::icon name="check" class="ms-auto size-3.5" x-show="action.type === def.key" />
                            </button>
                        </template>
                    </div>
                </div>

                <template x-for="param in (actionDef(action.type)?.params ?? [])" :key="param.key">
                    <div class="contents">
                        <template x-if="param.type === 'text'">
                            <input
                                type="text"
                                class="{{ $controlClass }} min-w-28 rounded-md border border-edge/80 bg-surface text-fg placeholder:text-fg-muted focus:outline-none focus:ring-1 focus:ring-fg/15"
                                x-bind:placeholder="param.placeholder || param.label"
                                x-bind:value="action.params[param.key] ?? ''"
                                x-on:input="setActionParam('{{ $branch }}', action.id, param.key, $event.target.value)"
                                x-bind:disabled="isDisabled"
                            />
                        </template>
                        <template x-if="param.type === 'select'">
                            <div class="relative">
                                <button
                                    type="button"
                                    class="{{ $pillClass }}"
                                    x-on:click="toggleOpen('param-{{ $branch }}-' + action.id + '-' + param.key)"
                                    x-bind:disabled="isDisabled"
                                >
                                    <span x-text="(param.options || []).find(o => o.value === action.params[param.key])?.label || param.label"></span>
                                    <neura::icon name="chevron-down" class="size-3 text-fg-muted" />
                                </button>
                                <div
                                    x-show="open === 'param-{{ $branch }}-' + action.id + '-' + param.key"
                                    x-cloak
                                    x-on:click.outside="closeOpen()"
                                    class="absolute start-0 top-full z-50 mt-1 min-w-40 overflow-hidden rounded-lg border border-edge bg-surface-raised p-1 shadow-lg"
                                >
                                    <template x-for="opt in (param.options || [])" :key="String(opt.value)">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm text-fg hover:bg-hover"
                                            x-on:click="setActionParam('{{ $branch }}', action.id, param.key, opt.value); closeOpen()"
                                        >
                                            <span x-text="opt.label"></span>
                                            <neura::icon name="check" class="ms-auto size-3.5" x-show="action.params[param.key] === opt.value" />
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <button
                    type="button"
                    class="ms-auto shrink-0 rounded-md p-1.5 text-fg-muted hover:bg-hover hover:text-fg disabled:opacity-40"
                    x-on:click="removeAction('{{ $branch }}', action.id)"
                    x-bind:disabled="isDisabled"
                    x-bind:title="label('removeAction')"
                >
                    <neura::icon name="trash" class="{{ $iconClass }}" />
                </button>
            </div>
        </div>
    </template>

    <div class="ps-7">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm text-fg-secondary hover:text-fg disabled:opacity-40"
            x-on:click="addAction('{{ $branch }}')"
            x-bind:disabled="isDisabled || actions.length === 0"
        >
            <neura::icon name="plus" class="{{ $iconClass }}" />
            <span x-text="label('addAction')"></span>
        </button>
    </div>
</div>

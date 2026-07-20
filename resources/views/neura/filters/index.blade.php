@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'value' => [],
    'fields' => [],
    'label' => null,
    'showSearch' => true,
    'allowMultiple' => true,
    'showClear' => true,
    'disabled' => false,
])

@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;

    $disabled = filled($disabled) && $disabled;
    $showSearch = filled($showSearch) && $showSearch;
    $allowMultiple = filled($allowMultiple) && $allowMultiple;
    $showClear = filled($showClear) && $showClear;
    $label = $label ?? __('filter');

    $operatorLabels = [
        'is' => __('is'),
        'is_not' => __('isNot'),
        'is_any_of' => __('isAnyOf'),
        'is_not_any_of' => __('isNotAnyOf'),
        'includes_all' => __('includesAll'),
        'excludes_all' => __('excludesAll'),
        'contains' => __('contains'),
        'not_contains' => __('notContains'),
        'starts_with' => __('startsWith'),
        'ends_with' => __('endsWith'),
        'empty' => __('isEmpty'),
        'not_empty' => __('isNotEmpty'),
    ];

    $operatorSets = [
        'select' => ['is', 'is_not', 'empty', 'not_empty'],
        'multiselect' => ['is_any_of', 'is_not_any_of', 'includes_all', 'excludes_all', 'empty', 'not_empty'],
        'text' => ['contains', 'not_contains', 'starts_with', 'ends_with', 'is', 'empty', 'not_empty'],
    ];

    $defaultOperators = [
        'select' => 'is',
        'multiselect' => 'is_any_of',
        'text' => 'contains',
    ];

    $normalizedFields = collect($fields)->map(function ($field) use ($operatorSets, $defaultOperators) {
        $type = $field['type'] ?? 'select';

        return [
            'key' => $field['key'],
            'label' => $field['label'] ?? Str::title($field['key']),
            'type' => $type,
            'icon' => $field['icon'] ?? null,
            'placeholder' => $field['placeholder'] ?? null,
            'searchable' => $field['searchable'] ?? true,
            'options' => collect($field['options'] ?? [])->map(fn ($option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'color' => $option['color'] ?? null,
                'icon' => $option['icon'] ?? null,
            ])->values()->all(),
            'operators' => $field['operators'] ?? $operatorSets[$type] ?? $operatorSets['select'],
            'defaultOperator' => $field['defaultOperator'] ?? $defaultOperators[$type] ?? 'is',
        ];
    })->values();

    $fieldConfigs = $normalizedFields->keyBy('key');

    $initialFilters = collect($value ?? [])->map(fn ($filter) => [
        'id' => $filter['id'] ?? 'f' . Str::random(8),
        'field' => $filter['field'],
        'operator' => $filter['operator'] ?? ($fieldConfigs[$filter['field']]['defaultOperator'] ?? 'is'),
        'values' => array_values((array) ($filter['values'] ?? [])),
    ])->values();

    $segmentClass = 'flex h-full items-center gap-1.5 px-2.5 text-sm whitespace-nowrap';
    $panelClass = 'absolute start-0 top-full z-50 mt-1 min-w-44 rounded-md border border-edge bg-surface-raised p-1 shadow-lg';
    $itemClass = 'flex w-full cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-start text-sm text-fg transition-colors duration-100 hover:bg-hover';
    $panelSearchClass = 'mb-1 w-full border-0 border-b border-separator bg-transparent px-2 py-1.5 text-sm text-fg placeholder:text-fg-muted focus:outline-none';
@endphp

<div
    x-data="{
        filters: {{ Js::from($initialFilters) }},
        fieldConfigs: {{ Js::from($fieldConfigs) }},
        fieldsList: {{ Js::from($normalizedFields->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label']])) }},
        operatorLabels: {{ Js::from($operatorLabels) }},
        allowMultiple: {{ Js::from($allowMultiple) }},
        isDisabled: {{ Js::from($disabled) }},
        selectLabel: {{ Js::from(__('select')) }},
        selectedLabel: {{ Js::from(__('selectedCount')) }},
        open: null,
        menuField: null,
        menuSearch: '',
        valueSearch: '',
        highlightedField: null,
        sessionFilterIds: {},

        emit() {
            const snapshot = JSON.parse(JSON.stringify(this.filters));
            this.$dispatch('filters-changed', { filters: snapshot });
            this.$root?._x_model?.set(snapshot);

            const wireModel = this.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
            if (this.$wire && wireModel) {
                this.$wire.set(this.$root.getAttribute(wireModel), snapshot, wireModel.includes('.live'));
            }
        },

        syncFromModel() {
            const model = this.$root?._x_model;
            if (! model) return;

            let value;
            try { value = model.get(); } catch (e) { return; }
            if (value === undefined || value === null) return;

            if (JSON.stringify(value) === JSON.stringify(this.filters)) return;

            this.filters = value.map(f => ({
                id: f.id ?? this.uid(),
                field: f.field,
                operator: f.operator ?? this.fieldConfigs[f.field]?.defaultOperator ?? 'is',
                values: Array.isArray(f.values) ? f.values : [],
            }));
        },

        uid() {
            return 'f' + Math.random().toString(36).slice(2, 10);
        },

        {{-- Add-filter menu --}}
        toggleMenu() {
            if (this.isDisabled) return;
            if (this.open === 'menu') { this.closeAll(); return; }
            this.closeAll();
            this.open = 'menu';
            this.highlightedField = this.visibleFieldKeys[0] ?? null;
            this.$nextTick(() => this.$refs.menuSearchInput?.focus());
        },

        closeAll() {
            this.open = null;
            this.menuField = null;
            this.menuSearch = '';
            this.valueSearch = '';
            this.highlightedField = null;
            this.sessionFilterIds = {};
        },

        fieldSelectable(key) {
            if (! this.allowMultiple && this.filters.some(f => f.field === key)) return false;
            const label = this.fieldConfigs[key]?.label ?? '';
            return ! this.menuSearch || label.toLowerCase().includes(this.menuSearch.toLowerCase());
        },

        get visibleFieldKeys() {
            return this.fieldsList.filter(f => this.fieldSelectable(f.key)).map(f => f.key);
        },

        get anySelectable() {
            return this.fieldsList.some(f => this.allowMultiple || ! this.filters.some(x => x.field === f.key));
        },

        moveHighlight(delta) {
            const keys = this.visibleFieldKeys;
            if (! keys.length) return;
            const index = keys.indexOf(this.highlightedField);
            this.highlightedField = keys[(index + delta + keys.length) % keys.length] ?? keys[0];
        },

        chooseField(key) {
            const config = this.fieldConfigs[key];
            if (! config) return;

            if (config.options.length && (config.type === 'select' || config.type === 'multiselect')) {
                this.menuField = key;
                this.valueSearch = '';
                this.$nextTick(() => this.$refs.menuOptionSearchInput?.focus());
                return;
            }

            const filter = this.addFilter(key);
            this.closeAll();
            this.$nextTick(() => this.$root.querySelector(`[data-filter-input='${filter.id}']`)?.focus());
        },

        addFilter(key, values = []) {
            const config = this.fieldConfigs[key];
            const filter = { id: this.uid(), field: key, operator: config.defaultOperator, values };
            this.filters.push(filter);
            this.emit();
            return filter;
        },

        toggleMenuOption(key, value) {
            const config = this.fieldConfigs[key];

            if (config.type !== 'multiselect') {
                this.addFilter(key, [value]);
                this.closeAll();
                return;
            }

            const sessionId = this.sessionFilterIds[key];
            const filter = this.filters.find(f => f.id === sessionId);

            if (! filter) {
                const created = this.addFilter(key, [value]);
                this.sessionFilterIds[key] = created.id;
                return;
            }

            filter.values = filter.values.includes(value)
                ? filter.values.filter(v => v !== value)
                : [...filter.values, value];
            this.emit();
        },

        menuOptionChecked(key, value) {
            const filter = this.filters.find(f => f.id === this.sessionFilterIds[key]);
            return !! filter?.values.includes(value);
        },

        {{-- Active filter chips --}}
        updateOperator(filter, operator) {
            filter.operator = operator;
            if (operator === 'empty' || operator === 'not_empty') filter.values = [];
            this.open = null;
            this.emit();
        },

        toggleValue(filter, value) {
            const config = this.fieldConfigs[filter.field];

            if (config.type === 'multiselect') {
                filter.values = filter.values.includes(value)
                    ? filter.values.filter(v => v !== value)
                    : [...filter.values, value];
            } else {
                filter.values = [value];
                this.open = null;
            }
            this.emit();
        },

        removeFilter(id) {
            this.filters = this.filters.filter(f => f.id !== id);
            this.emit();
        },

        clearAll() {
            if (! this.filters.length) return;
            this.filters = [];
            this.closeAll();
            this.emit();
        },

        showValue(filter) {
            return filter.operator !== 'empty' && filter.operator !== 'not_empty';
        },

        valueLabel(filter) {
            const config = this.fieldConfigs[filter.field];
            const selected = (config?.options ?? []).filter(o => filter.values.includes(o.value));
            if (! selected.length) return this.selectLabel;
            if (selected.length === 1) return selected[0].label;
            return selected.length + ' ' + this.selectedLabel;
        },

        valueDotColor(filter) {
            const config = this.fieldConfigs[filter.field];
            const selected = (config?.options ?? []).filter(o => filter.values.includes(o.value));
            return selected.length === 1 ? selected[0].color : null;
        },

        optionMatches(label) {
            return ! this.valueSearch || label.toLowerCase().includes(this.valueSearch.toLowerCase());
        },

        togglePanel(id) {
            if (this.isDisabled) return;
            const next = this.open === id ? null : id;
            this.closeAll();
            this.open = next;
        }
    }"
    x-effect="syncFromModel()"
    x-on:keydown.escape="closeAll()"
    {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2' . ($disabled ? ' opacity-50' : '')]) }}
>
    @if ($name)
        <input type="hidden" name="{{ $name }}" x-bind:value="JSON.stringify(filters)" />
    @endif

    {{-- Active filter chips --}}
    <template x-for="filter in filters" :key="filter.id">
        <div class="inline-flex h-8 items-stretch divide-x divide-edge rounded-md border border-edge bg-surface text-sm shadow-xs">
            {{-- Field label --}}
            <div class="{{ $segmentClass }} rounded-s-md text-fg-secondary">
                @foreach ($normalizedFields as $field)
                    @if ($field['icon'])
                        <span x-show="filter.field === '{{ $field['key'] }}'" x-cloak>
                            <neura::icon :name="$field['icon']" variant="micro" class="size-3.5 text-fg-muted" />
                        </span>
                    @endif
                @endforeach
                <span x-text="fieldConfigs[filter.field]?.label ?? filter.field"></span>
            </div>

            {{-- Operator dropdown --}}
            <div class="relative flex items-stretch">
                <button
                    type="button"
                    class="{{ $segmentClass }} cursor-pointer text-fg-muted transition-colors duration-100 hover:bg-hover hover:text-fg"
                    x-bind:disabled="isDisabled"
                    x-on:click="togglePanel('op:' + filter.id)"
                    x-text="operatorLabels[filter.operator] ?? filter.operator"
                ></button>

                <div
                    class="{{ $panelClass }}"
                    x-cloak
                    x-show="open === 'op:' + filter.id"
                    x-transition.opacity.duration.100ms
                    x-on:click.outside="if (open === 'op:' + filter.id) closeAll()"
                >
                    <template x-for="operator in (fieldConfigs[filter.field]?.operators ?? [])" :key="operator">
                        <button
                            type="button"
                            class="{{ $itemClass }} justify-between"
                            x-on:click="updateOperator(filter, operator)"
                        >
                            <span x-text="operatorLabels[operator] ?? operator"></span>
                            <neura::icon name="check" variant="micro" class="size-3.5 text-fg" x-show="filter.operator === operator" />
                        </button>
                    </template>
                </div>
            </div>

            {{-- Value editor --}}
            <template x-if="showValue(filter)">
                <div class="relative flex items-stretch">
                    {{-- Text input --}}
                    <input
                        x-show="fieldConfigs[filter.field]?.type === 'text'"
                        type="text"
                        class="{{ $segmentClass }} w-32 bg-transparent text-fg placeholder:text-fg-muted focus:outline-none"
                        x-bind:data-filter-input="filter.id"
                        x-bind:placeholder="fieldConfigs[filter.field]?.placeholder ?? ''"
                        x-bind:disabled="isDisabled"
                        x-model="filter.values[0]"
                        x-on:input="emit()"
                        x-on:keydown.enter="$event.target.blur()"
                    />

                    {{-- Select / multiselect trigger --}}
                    <button
                        x-show="fieldConfigs[filter.field]?.type !== 'text'"
                        type="button"
                        class="{{ $segmentClass }} cursor-pointer text-fg transition-colors duration-100 hover:bg-hover"
                        x-bind:disabled="isDisabled"
                        x-on:click="togglePanel('val:' + filter.id)"
                    >
                        <span
                            class="size-2 rounded-full"
                            x-show="valueDotColor(filter)"
                            x-bind:style="'background-color:' + valueDotColor(filter)"
                        ></span>
                        <span x-text="valueLabel(filter)"></span>
                    </button>

                    <div
                        class="{{ $panelClass }} w-52"
                        x-cloak
                        x-show="open === 'val:' + filter.id"
                        x-transition.opacity.duration.100ms
                        x-on:click.outside="if (open === 'val:' + filter.id) closeAll()"
                    >
                        @foreach ($normalizedFields as $field)
                            @if (! empty($field['options']))
                                <div x-show="filter.field === '{{ $field['key'] }}'">
                                    @if ($field['searchable'])
                                        <input
                                            type="text"
                                            class="{{ $panelSearchClass }}"
                                            placeholder="{{ __('search') }}..."
                                            x-model="valueSearch"
                                            x-on:click.stop
                                        />
                                    @endif

                                    <div class="max-h-64 overflow-y-auto">
                                        @foreach ($field['options'] as $option)
                                            <button
                                                type="button"
                                                class="{{ $itemClass }}"
                                                x-show="optionMatches({{ Js::from($option['label']) }})"
                                                x-on:click="toggleValue(filter, {{ Js::from($option['value']) }})"
                                            >
                                                @if ($field['type'] === 'multiselect')
                                                    <span
                                                        class="flex size-4 shrink-0 items-center justify-center rounded border transition-colors duration-100"
                                                        x-bind:class="filter.values.includes({{ Js::from($option['value']) }})
                                                            ? 'border-primary-900 bg-primary-900 text-white dark:border-primary-100 dark:bg-primary-100 dark:text-primary-900'
                                                            : 'border-edge'"
                                                    >
                                                        <neura::icon name="check" variant="micro" class="size-3" x-show="filter.values.includes({{ Js::from($option['value']) }})" />
                                                    </span>
                                                @endif

                                                @if ($option['color'])
                                                    <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $option['color'] }}"></span>
                                                @elseif ($option['icon'])
                                                    <neura::icon :name="$option['icon']" variant="micro" class="size-3.5 shrink-0 text-fg-muted" />
                                                @endif

                                                <span class="grow">{{ $option['label'] }}</span>

                                                @if ($field['type'] !== 'multiselect')
                                                    <neura::icon name="check" variant="micro" class="size-3.5 text-fg" x-show="filter.values.includes({{ Js::from($option['value']) }})" />
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </template>

            {{-- Remove --}}
            <button
                type="button"
                class="flex cursor-pointer items-center rounded-e-md px-1.5 text-fg-muted transition-colors duration-100 hover:bg-hover hover:text-fg"
                x-bind:disabled="isDisabled"
                x-bind:aria-label="'Remove ' + (fieldConfigs[filter.field]?.label ?? filter.field) + ' filter'"
                x-on:click="removeFilter(filter.id)"
            >
                <neura::icon name="x-mark" variant="micro" class="size-3.5" />
            </button>
        </div>
    </template>

    {{-- Add-filter trigger + menu --}}
    <div class="relative" x-show="anySelectable">
        @isset($trigger)
            <div x-on:click="toggleMenu()">
                {{ $trigger }}
            </div>
        @else
            <neura::button
                variant="outline"
                size="sm"
                icon="funnel"
                :disabled="$disabled"
                x-on:click="toggleMenu()"
            >
                {{ $label }}
            </neura::button>
        @endisset

        <div
            class="{{ $panelClass }} w-56"
            x-cloak
            x-show="open === 'menu'"
            x-transition.opacity.duration.100ms
            x-on:click.outside="if (open === 'menu') closeAll()"
        >
            {{-- Root: field list --}}
            <div x-show="! menuField">
                @if ($showSearch)
                    <input
                        type="text"
                        class="{{ $panelSearchClass }}"
                        placeholder="{{ __('filter') }}..."
                        x-ref="menuSearchInput"
                        x-model="menuSearch"
                        x-on:input="highlightedField = visibleFieldKeys[0] ?? null"
                        x-on:keydown.arrow-down.prevent="moveHighlight(1)"
                        x-on:keydown.arrow-up.prevent="moveHighlight(-1)"
                        x-on:keydown.enter.prevent="highlightedField && chooseField(highlightedField)"
                    />
                @endif

                @foreach ($normalizedFields as $field)
                    <button
                        type="button"
                        class="{{ $itemClass }}"
                        x-show="fieldSelectable('{{ $field['key'] }}')"
                        x-bind:class="highlightedField === '{{ $field['key'] }}' && 'bg-hover'"
                        x-on:mouseenter="highlightedField = '{{ $field['key'] }}'"
                        x-on:click="chooseField('{{ $field['key'] }}')"
                    >
                        @if ($field['icon'])
                            <neura::icon :name="$field['icon']" variant="micro" class="size-3.5 shrink-0 text-fg-muted" />
                        @endif
                        <span class="grow">{{ $field['label'] }}</span>
                        @if (! empty($field['options']))
                            <neura::icon name="chevron-right" variant="micro" class="size-3.5 text-fg-muted" />
                        @endif
                    </button>
                @endforeach

                <div class="px-2 py-1.5 text-sm text-fg-muted" x-show="! visibleFieldKeys.length">
                    {{ __('noResultsFound') }}
                </div>
            </div>

            {{-- Drill-down: options for the chosen field --}}
            @foreach ($normalizedFields as $field)
                @if (! empty($field['options']))
                    <div x-show="menuField === '{{ $field['key'] }}'" x-cloak>
                        <div class="mb-1 flex items-center gap-1 border-b border-separator pb-1.5">
                            <button
                                type="button"
                                class="flex cursor-pointer items-center rounded p-1 text-fg-muted transition-colors duration-100 hover:bg-hover hover:text-fg"
                                aria-label="Back"
                                x-on:click="menuField = null; valueSearch = ''"
                            >
                                <neura::icon name="chevron-left" variant="micro" class="size-3.5" />
                            </button>
                            <span class="text-sm font-medium text-fg">{{ $field['label'] }}</span>
                        </div>

                        @if ($field['searchable'])
                            <input
                                type="text"
                                class="{{ $panelSearchClass }}"
                                placeholder="{{ __('search') }}..."
                                x-ref="menuOptionSearchInput"
                                x-model="valueSearch"
                            />
                        @endif

                        <div class="max-h-64 overflow-y-auto">
                            @foreach ($field['options'] as $option)
                                <button
                                    type="button"
                                    class="{{ $itemClass }}"
                                    x-show="optionMatches({{ Js::from($option['label']) }})"
                                    x-on:click="toggleMenuOption('{{ $field['key'] }}', {{ Js::from($option['value']) }})"
                                >
                                    @if ($field['type'] === 'multiselect')
                                        <span
                                            class="flex size-4 shrink-0 items-center justify-center rounded border transition-colors duration-100"
                                            x-bind:class="menuOptionChecked('{{ $field['key'] }}', {{ Js::from($option['value']) }})
                                                ? 'border-primary-900 bg-primary-900 text-white dark:border-primary-100 dark:bg-primary-100 dark:text-primary-900'
                                                : 'border-edge'"
                                        >
                                            <neura::icon name="check" variant="micro" class="size-3" x-show="menuOptionChecked('{{ $field['key'] }}', {{ Js::from($option['value']) }})" />
                                        </span>
                                    @endif

                                    @if ($option['color'])
                                        <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $option['color'] }}"></span>
                                    @elseif ($option['icon'])
                                        <neura::icon :name="$option['icon']" variant="micro" class="size-3.5 shrink-0 text-fg-muted" />
                                    @endif

                                    <span class="grow">{{ $option['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Clear all --}}
    @if ($showClear)
        <neura::button
            variant="ghost"
            size="sm"
            x-cloak
            x-show="filters.length > 0"
            x-bind:disabled="isDisabled"
            x-on:click="clearAll()"
        >
            {{ __('clearAll') }}
        </neura::button>
    @endif
</div>

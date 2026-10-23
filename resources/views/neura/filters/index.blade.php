@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'value' => [],
    'fields' => [],
    'label' => null,
    'showSearch' => true,
    'allowMultiple' => true,
    'showClear' => true,
    'disabled' => false,
    'size' => neura_config('filters', 'size'),
    'variant' => null,
    'color' => null,
    'rounded' => neura_config('filters', 'rounded'),
    'shadow' => neura_config('filters', 'shadow'),
])

@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Neura\Kit\Support\PackResolver;

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
        'at_least' => __('atLeast'),
        'at_most' => __('atMost'),
        'empty' => __('isEmpty'),
        'not_empty' => __('isNotEmpty'),
    ];

    $operatorSets = [
        'select' => ['is', 'is_not', 'empty', 'not_empty'],
        'multiselect' => ['is_any_of', 'is_not_any_of', 'includes_all', 'excludes_all', 'empty', 'not_empty'],
        'text' => ['contains', 'not_contains', 'starts_with', 'ends_with', 'is', 'empty', 'not_empty'],
        'number' => ['is', 'is_not', 'at_least', 'at_most', 'empty', 'not_empty'],
        'tags' => ['is_any_of', 'is_not_any_of', 'empty', 'not_empty'],
    ];

    $defaultOperators = [
        'select' => 'is',
        'multiselect' => 'is_any_of',
        'text' => 'contains',
        'number' => 'is',
        'tags' => 'is_any_of',
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
            'operators' => $operators = array_values($field['operators'] ?? $operatorSets[$type] ?? $operatorSets['select']),
            // A field that narrows its operators starts on the first of them, unless its type's
            // default is among them.
            'defaultOperator' => $field['defaultOperator']
                ?? (in_array($defaultOperators[$type] ?? 'is', $operators, true) ? ($defaultOperators[$type] ?? 'is') : ($operators[0] ?? 'is')),
            'hasSwatch' => collect($field['options'] ?? [])->contains(fn ($option) => filled($option['color'] ?? null)),
        ];
    })->values();

    $fieldConfigs = $normalizedFields->keyBy('key');

    $initialFilters = collect($value ?? [])->map(fn ($filter) => [
        'id' => $filter['id'] ?? 'f' . Str::random(8),
        'field' => $filter['field'],
        'operator' => $filter['operator'] ?? ($fieldConfigs[$filter['field']]['defaultOperator'] ?? 'is'),
        'values' => array_values((array) ($filter['values'] ?? [])),
    ])->values();

    // Design-system tokens resolved from config + packs (mirrors button / input).
    $sizeConfig = PackResolver::filtersSize($size);
    $variant = $variant ?: neura_config('filters', 'variant');
    $variantConfig = PackResolver::filtersVariant($variant);
    $roundedClass = PackResolver::rounded($rounded);
    [$roundedStart, $roundedEnd] = PackResolver::roundedSidesLogical($rounded);
    $shadowClass = PackResolver::shadow($variantConfig['shadow'] ?? $shadow);

    // The `color` prop tints the add-filter and clear-all buttons only;
    // the chips keep their structural variant surface.
    $color = $color ?: neura_config('filters', 'color');
    $buttonColor = $color !== null && $color !== 'default' ? $color : null;

    $roundedToken = PackResolver::roundedToken($rounded);
    $roundedValue = match ($roundedToken) {
        'none' => '0px',
        'full' => '9999px',
        default => "var(--radius-{$roundedToken})",
    };

    // Selection indicators share the checkbox pack used by inputs / selects.
    $checkboxColors = PackResolver::inputColor('checkbox');
    $checkboxRounded = PackResolver::rounded(neura_config('checkbox', 'rounded'));
    $checkboxClass = 'flex size-4 shrink-0 items-center justify-center border transition-colors duration-fast ease-standard '
        . $checkboxRounded . ' ' . $checkboxColors['border'] . ' ' . $checkboxColors['checked'];

    // Menu items round one step tighter than their panel, like dropdown / select.
    $itemRadius = 'rounded-[calc(var(--nk-filter-round)-0.25rem)]';

    $segmentClass = 'flex h-full items-center whitespace-nowrap ' . $sizeConfig['segment'];
    $panelClass = 'nk-menu-motion absolute start-0 top-full z-50 mt-1 min-w-44 border border-edge backdrop-blur-xl ring-1 ring-black/[0.03] dark:ring-white/[0.04] p-1 ' . $variantConfig['panel'] . ' ' . $roundedClass . ' ' . $shadowClass;
    $itemClass = 'flex w-full cursor-pointer items-center gap-2 px-2.5 py-1.5 text-start text-[13px] leading-snug text-fg transition-colors duration-fast ease-standard hover:bg-hover ' . $itemRadius;
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
        typedTypes: ['text', 'number'],
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

            if (config.type === 'tags') {
                this.open = 'val:' + filter.id;
                this.$nextTick(() => this.$root.querySelector(`[data-filter-tags='${filter.id}']`)?.focus());
                return;
            }

            this.$nextTick(() => this.$root.querySelector(`[data-filter-input='${filter.id}']`)?.focus());
        },

        isTyped(filter) {
            return this.typedTypes.includes(this.fieldConfigs[filter.field]?.type);
        },

        {{-- Free values (tags fields): typed or pasted, split on commas, semicolons or lines --}}
        addTagValues(filter, raw) {
            const added = String(raw ?? '')
                .split(/[,;\n]/)
                .map(part => part.trim())
                .filter(part => part !== '' && ! filter.values.includes(part));
            if (! added.length) return;
            filter.values = [...filter.values, ...new Set(added)];
            this.emit();
        },

        removeTagValue(filter, value) {
            filter.values = filter.values.filter(v => v !== value);
            this.emit();
        },

        onTagKeydown(filter, event) {
            if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
                event.preventDefault();
                this.addTagValues(filter, event.target.value);
                event.target.value = '';
                return;
            }
            if (event.key === 'Backspace' && event.target.value === '' && filter.values.length) {
                this.removeTagValue(filter, filter.values[filter.values.length - 1]);
            }
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
            if (config?.type === 'tags') {
                if (! filter.values.length) return this.selectLabel;
                if (filter.values.length <= 2) return filter.values.join(', ');
                return filter.values.length + ' ' + this.selectedLabel;
            }
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
    {{ $attributes->merge([
        'class' => 'flex flex-wrap items-center gap-2' . ($disabled ? ' opacity-50' : ''),
        'style' => '--nk-filter-round: ' . $roundedValue,
    ]) }}
>
    @if ($name)
        <input type="hidden" name="{{ $name }}" x-bind:value="JSON.stringify(filters)" />
    @endif

    {{-- Active filter chips --}}
    <template x-for="filter in filters" :key="filter.id">
        <div class="inline-flex {{ $sizeConfig['chip'] }} items-stretch divide-x divide-edge {{ $roundedClass }} {{ $variantConfig['chip'] }} {{ $shadowClass }}">
            {{-- Field label --}}
            <div class="{{ $segmentClass }} {{ $roundedStart }} text-fg-secondary">
                @foreach ($normalizedFields as $field)
                    @if ($field['icon'])
                        <span x-show="filter.field === '{{ $field['key'] }}'" x-cloak>
                            <neura::icon :name="$field['icon']" variant="micro" class="{{ $sizeConfig['icon'] }} text-fg-muted" />
                        </span>
                    @endif
                @endforeach
                <span x-text="fieldConfigs[filter.field]?.label ?? filter.field"></span>
            </div>

            {{-- Operator dropdown --}}
            <div class="relative flex items-stretch">
                <button
                    type="button"
                    class="{{ $segmentClass }} cursor-pointer text-fg-muted transition-colors duration-fast ease-standard hover:bg-hover hover:text-fg"
                    x-bind:disabled="isDisabled"
                    x-on:click="togglePanel('op:' + filter.id)"
                >
                    <span class="inline-grid justify-items-start">
                        @foreach ($normalizedFields as $field)
                            <template x-if="filter.field === '{{ $field['key'] }}'">
                                <span class="col-start-1 row-start-1 inline-grid justify-items-start invisible" aria-hidden="true">
                                    @foreach ($field['operators'] as $operator)
                                        <span class="col-start-1 row-start-1 whitespace-nowrap">{{ $operatorLabels[$operator] ?? $operator }}</span>
                                    @endforeach
                                </span>
                            </template>
                        @endforeach
                        <span class="col-start-1 row-start-1 whitespace-nowrap" x-text="operatorLabels[filter.operator] ?? filter.operator"></span>
                    </span>
                </button>

                <div
                    class="{{ $panelClass }}"
                    x-cloak
                    data-side="bottom"
                    data-align="start"
                    x-show="open === 'op:' + filter.id"
                    x-transition:enter="nk-menu-motion"
                    x-transition:enter-start="nk-menu-from"
                    x-transition:enter-end="nk-menu-to"
                    x-transition:leave="nk-menu-leaving"
                    x-transition:leave-start="nk-menu-to"
                    x-transition:leave-end="nk-menu-out"
                    x-on:click.outside="if (open === 'op:' + filter.id) closeAll()"
                >
                    <template x-for="operator in (fieldConfigs[filter.field]?.operators ?? [])" :key="operator">
                        <button
                            type="button"
                            class="{{ $itemClass }} justify-between"
                            x-on:click="updateOperator(filter, operator)"
                        >
                            <span x-text="operatorLabels[operator] ?? operator"></span>
                            <neura::icon name="check" variant="micro" class="size-3.5 shrink-0 text-fg" x-bind:class="filter.operator === operator ? 'visible' : 'invisible'" />
                        </button>
                    </template>
                </div>
            </div>

            {{-- Value editor. Width stays on the longest label for this field. --}}
            <div class="relative flex items-stretch">
                    {{-- Text input --}}
                    <input
                        x-show="showValue(filter) && isTyped(filter)"
                        x-bind:type="fieldConfigs[filter.field]?.type === 'number' ? 'number' : 'text'"
                        step="any"
                        class="{{ $segmentClass }} {{ $sizeConfig['input'] }} bg-transparent text-fg placeholder:text-fg-muted focus:outline-none"
                        x-bind:data-filter-input="filter.id"
                        x-bind:placeholder="fieldConfigs[filter.field]?.placeholder ?? ''"
                        x-bind:disabled="isDisabled"
                        x-model="filter.values[0]"
                        x-on:input.stop="emit()"
                        x-on:keydown.enter="$event.target.blur()"
                    />
                    <span
                        class="{{ $segmentClass }} {{ $sizeConfig['input'] }} text-fg-muted"
                        x-show="!showValue(filter) && isTyped(filter)"
                        x-cloak
                    >—</span>

                    {{-- Select / multiselect trigger --}}
                    <button
                        x-show="! isTyped(filter)"
                        type="button"
                        class="{{ $segmentClass }} cursor-pointer text-fg transition-colors duration-fast ease-standard hover:bg-hover disabled:cursor-default disabled:hover:bg-transparent"
                        x-bind:disabled="isDisabled || !showValue(filter)"
                        x-on:click="if (showValue(filter)) togglePanel('val:' + filter.id)"
                    >
                        <span class="inline-grid justify-items-start">
                            @foreach ($normalizedFields as $field)
                                @if (! in_array($field['type'], ['text', 'number'], true))
                                    <template x-if="filter.field === '{{ $field['key'] }}'">
                                    <span class="col-start-1 row-start-1 inline-grid justify-items-start invisible" aria-hidden="true">
                                        @php
                                            $valueMeasures = collect($field['options'])->pluck('label')
                                                ->push(__('select'))
                                                ->when($field['type'] === 'multiselect', fn ($labels) => $labels->push(count($field['options']).' '.__('selectedCount')));
                                        @endphp
                                        @foreach ($valueMeasures as $measure)
                                            <span class="col-start-1 row-start-1 inline-flex items-center whitespace-nowrap {{ $sizeConfig['gap'] }}">
                                                @if ($field['hasSwatch'])
                                                    <span class="size-2 shrink-0"></span>
                                                @endif
                                                <span>{{ $measure }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                    </template>
                                @endif
                            @endforeach
                            <span class="col-start-1 row-start-1 inline-flex items-center whitespace-nowrap {{ $sizeConfig['gap'] }}">
                                <template x-if="fieldConfigs[filter.field]?.hasSwatch">
                                    <span
                                        class="size-2 shrink-0 rounded-full"
                                        x-bind:class="valueDotColor(filter) ? '' : 'invisible'"
                                        x-bind:style="valueDotColor(filter) ? 'background-color:' + valueDotColor(filter) : ''"
                                    ></span>
                                </template>
                                <span x-text="showValue(filter) ? valueLabel(filter) : '—'"></span>
                            </span>
                        </span>
                    </button>

                    <div
                        class="{{ $panelClass }} w-52"
                        x-cloak
                        data-side="bottom"
                        data-align="start"
                        x-show="open === 'val:' + filter.id"
                        x-transition:enter="nk-menu-motion"
                        x-transition:enter-start="nk-menu-from"
                        x-transition:enter-end="nk-menu-to"
                        x-transition:leave="nk-menu-leaving"
                        x-transition:leave-start="nk-menu-to"
                        x-transition:leave-end="nk-menu-out"
                        x-on:click.outside="if (open === 'val:' + filter.id) closeAll()"
                    >
                        @foreach ($normalizedFields as $field)
                            @if ($field['type'] === 'tags')
                                <div x-show="filter.field === '{{ $field['key'] }}'">
                                    <input
                                        x-on:input.stop
                                        type="text"
                                        class="{{ $panelSearchClass }}"
                                        placeholder="{{ $field['placeholder'] ?? __('addValue') }}"
                                        x-bind:data-filter-tags="filter.id"
                                        x-on:click.stop
                                        x-on:keydown="onTagKeydown(filter, $event)"
                                        x-on:paste.prevent="addTagValues(filter, $event.clipboardData.getData('text'))"
                                    />

                                    <div class="max-h-64 overflow-y-auto">
                                        <template x-for="value in filter.values" :key="String(value)">
                                            <div class="{{ $itemClass }} cursor-default justify-between hover:bg-transparent">
                                                <span class="min-w-0 truncate" x-text="value"></span>
                                                <button
                                                    type="button"
                                                    class="flex shrink-0 cursor-pointer items-center rounded p-0.5 text-fg-muted transition-colors duration-fast ease-standard hover:bg-hover hover:text-fg"
                                                    x-bind:aria-label="'Remove ' + value"
                                                    x-on:click.stop="removeTagValue(filter, value)"
                                                >
                                                    <neura::icon name="x-mark" variant="micro" class="size-3.5" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            @elseif (! empty($field['options']))
                                <div x-show="filter.field === '{{ $field['key'] }}'">
                                    @if ($field['searchable'])
                                        <input
                                            x-on:input.stop
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
                                                        class="{{ $checkboxClass }}"
                                                        x-bind:data-checked="filter.values.includes({{ Js::from($option['value']) }}) ? true : null"
                                                    >
                                                        <neura::icon name="check" variant="micro" class="size-2.5 text-white" x-show="filter.values.includes({{ Js::from($option['value']) }})" />
                                                    </span>
                                                @endif

                                                @if ($option['color'])
                                                    <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $option['color'] }}"></span>
                                                @elseif ($option['icon'])
                                                    <neura::icon :name="$option['icon']" variant="micro" class="size-3.5 shrink-0 text-fg-muted" />
                                                @endif

                                                <span class="grow">{{ $option['label'] }}</span>

                                                @if ($field['type'] !== 'multiselect')
                                                    <neura::icon name="check" variant="micro" class="size-3.5 shrink-0 text-fg" x-bind:class="filter.values.includes({{ Js::from($option['value']) }}) ? 'visible' : 'invisible'" />
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            {{-- Remove --}}
            <button
                type="button"
                class="flex cursor-pointer items-center {{ $roundedEnd }} {{ $sizeConfig['remove'] }} text-fg-muted transition-colors duration-fast ease-standard hover:bg-hover hover:text-fg"
                x-bind:disabled="isDisabled"
                x-bind:aria-label="'Remove ' + (fieldConfigs[filter.field]?.label ?? filter.field) + ' filter'"
                x-on:click="removeFilter(filter.id)"
            >
                <neura::icon name="x-mark" variant="micro" class="{{ $sizeConfig['icon'] }}" />
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
                :color="$buttonColor"
                :size="$size"
                :rounded="$rounded"
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
            data-side="bottom"
            data-align="start"
            x-show="open === 'menu'"
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
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
                        x-on:input.stop="highlightedField = visibleFieldKeys[0] ?? null"
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
                                class="flex cursor-pointer items-center rounded p-1 text-fg-muted transition-colors duration-fast ease-standard hover:bg-hover hover:text-fg"
                                aria-label="Back"
                                x-on:click="menuField = null; valueSearch = ''"
                            >
                                <neura::icon name="chevron-left" variant="micro" class="size-3.5" />
                            </button>
                            <span class="text-sm font-medium text-fg">{{ $field['label'] }}</span>
                        </div>

                        @if ($field['searchable'])
                            <input
                                x-on:input.stop
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
                                            class="{{ $checkboxClass }}"
                                            x-bind:data-checked="menuOptionChecked('{{ $field['key'] }}', {{ Js::from($option['value']) }}) ? true : null"
                                        >
                                            <neura::icon name="check" variant="micro" class="size-2.5 text-white" x-show="menuOptionChecked('{{ $field['key'] }}', {{ Js::from($option['value']) }})" />
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
            :color="$buttonColor"
            :size="$size"
            :rounded="$rounded"
            x-cloak
            x-show="filters.length > 0"
            x-bind:disabled="isDisabled"
            x-on:click="clearAll()"
        >
            {{ __('clearAll') }}
        </neura::button>
    @endif
</div>

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'value' => null,
    'fields' => [],
    'actions' => [],
    'maxDepth' => null,
    'disabled' => false,
    'size' => neura_config('rule-builder', 'size'),
    'rounded' => neura_config('rule-builder', 'rounded'),
    'shadow' => neura_config('rule-builder', 'shadow'),
])

@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;
    use Neura\Kit\Support\PackResolver;

    $disabled = filled($disabled) && $disabled;
    $maxDepth = (int) ($maxDepth ?? neura_config('rule-builder', 'max-depth') ?? 3);
    if ($maxDepth < 1) {
        $maxDepth = 3;
    }

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
        'greater_than' => __('greaterThan'),
        'less_than' => __('lessThan'),
        'empty' => __('isEmpty'),
        'not_empty' => __('isNotEmpty'),
    ];

    $operatorSets = [
        'select' => ['is', 'is_not', 'empty', 'not_empty'],
        'multiselect' => ['is_any_of', 'is_not_any_of', 'includes_all', 'excludes_all', 'empty', 'not_empty'],
        'text' => ['contains', 'not_contains', 'starts_with', 'ends_with', 'is', 'empty', 'not_empty'],
        'number' => ['is', 'is_not', 'greater_than', 'less_than', 'empty', 'not_empty'],
        'date' => ['is', 'is_not', 'greater_than', 'less_than', 'empty', 'not_empty'],
    ];

    $defaultOperators = [
        'select' => 'is',
        'multiselect' => 'is_any_of',
        'text' => 'contains',
        'number' => 'is',
        'date' => 'is',
    ];

    $normalizedFields = collect($fields)->map(function ($field) use ($operatorSets, $defaultOperators) {
        $type = $field['type'] ?? 'select';

        return [
            'key' => $field['key'],
            'label' => $field['label'] ?? Str::title($field['key']),
            'type' => $type,
            'icon' => $field['icon'] ?? null,
            'placeholder' => $field['placeholder'] ?? null,
            'options' => collect($field['options'] ?? [])->map(fn ($option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'color' => $option['color'] ?? null,
                'icon' => $option['icon'] ?? null,
            ])->values()->all(),
            'operators' => $field['operators'] ?? $operatorSets[$type] ?? $operatorSets['select'],
            'defaultOperator' => $field['defaultOperator'] ?? $defaultOperators[$type] ?? 'is',
        ];
    })->values()->all();

    $normalizedActions = collect($actions)->map(function ($action) {
        return [
            'key' => $action['key'],
            'label' => $action['label'] ?? Str::title($action['key']),
            'params' => collect($action['params'] ?? [])->map(fn ($param) => [
                'key' => $param['key'],
                'label' => $param['label'] ?? Str::title($param['key']),
                'type' => $param['type'] ?? 'text',
                'placeholder' => $param['placeholder'] ?? null,
                'options' => collect($param['options'] ?? [])->map(fn ($option) => [
                    'value' => $option['value'],
                    'label' => $option['label'],
                ])->values()->all(),
            ])->values()->all(),
        ];
    })->values()->all();

    $labels = [
        'when' => __('when'),
        'then' => __('then'),
        'and' => __('and'),
        'or' => __('or'),
        'addRule' => __('addRule'),
        'addGroup' => __('addGroup'),
        'addCondition' => __('addCondition'),
        'addAction' => __('addAction'),
        'ifTrue' => __('ifTrue'),
        'ifFalse' => __('ifFalse'),
        'groupLabel' => __('groupLabel'),
        'lockedCondition' => __('lockedCondition'),
        'selectField' => __('selectField'),
        'selectOperator' => __('selectOperator'),
        'selectValue' => __('selectValue'),
        'selectAction' => __('selectAction'),
        'dragToReorder' => __('dragToReorder'),
        'removeRule' => __('removeRule'),
        'removeGroup' => __('removeGroup'),
        'removeAction' => __('removeAction'),
        'toggleCombinator' => __('toggleCombinator'),
    ];

    $sizeConfig = PackResolver::ruleBuilderSize($size);
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = PackResolver::shadow($shadow);

    $rowClass = $sizeConfig['row'];
    $controlClass = $sizeConfig['control'];
    $chipClass = $sizeConfig['chip'];
    $railClass = $sizeConfig['rail'];
    $iconClass = $sizeConfig['icon'];

    $config = [
        'value' => $value,
        'fields' => $normalizedFields,
        'actions' => $normalizedActions,
        'maxDepth' => $maxDepth,
        'disabled' => $disabled,
        'labels' => $labels,
        'operatorLabels' => $operatorLabels,
    ];
@endphp

<div
    {{ $attributes->merge([
        'class' => 'nk-rule-builder w-full text-fg',
    ]) }}
    x-data="neuraRuleBuilder({{ Js::from($config) }})"
    x-effect="syncFromModel()"
>
    @if ($name && ! $attributes->whereStartsWith('wire:model')->isNotEmpty() && ! $attributes->whereStartsWith('x-model')->isNotEmpty())
        <input type="hidden" name="{{ $name }}" x-bind:value="JSON.stringify(value)" />
    @endif

    {{-- WHEN --}}
    <section class="nk-rule-builder-when">
        <header class="mb-3">
            <h3 class="text-sm font-semibold tracking-tight text-fg" x-text="label('when')"></h3>
        </header>

        <div class="flex flex-col" x-data="{ parent: value.when }">
            @include('neura::rule-builder.nodes', [
                'nodesExpr' => 'parent.children',
                'parentExpr' => 'parent',
                'depth' => 0,
                'maxDepth' => $maxDepth,
                'sizeConfig' => $sizeConfig,
                'roundedClass' => $roundedClass,
                'controlClass' => $controlClass,
                'rowClass' => $rowClass,
                'chipClass' => $chipClass,
                'railClass' => $railClass,
                'iconClass' => $iconClass,
            ])

            <div class="flex flex-wrap items-center gap-2 ps-7 pt-1">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-edge bg-surface-raised px-2.5 py-1.5 text-sm text-fg hover:bg-hover disabled:opacity-40"
                    x-on:click="addRule(value.when.id)"
                    x-bind:disabled="isDisabled || value.when.locked"
                >
                    <neura::icon name="plus" class="{{ $iconClass }}" />
                    <span x-text="label('addRule')"></span>
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-edge bg-surface-raised px-2.5 py-1.5 text-sm text-fg hover:bg-hover disabled:opacity-40"
                    x-on:click="addGroup(value.when.id)"
                    x-bind:disabled="isDisabled || value.when.locked || !canAddGroup(value.when.id)"
                >
                    <neura::icon name="plus" class="{{ $iconClass }}" />
                    <span x-text="label('addGroup')"></span>
                </button>
            </div>
        </div>
    </section>

    {{-- THEN --}}
    <section class="nk-rule-builder-then mt-8 border-t border-separator pt-6">
        <header class="mb-4">
            <h3 class="text-sm font-semibold tracking-tight text-fg" x-text="label('then')"></h3>
        </header>

        <div class="flex flex-col gap-6">
            {{-- If true --}}
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        <neura::icon name="check" class="size-3.5" />
                    </span>
                    <span class="text-sm font-medium text-fg" x-text="label('ifTrue')"></span>
                </div>

                @include('neura::rule-builder.actions', [
                    'branch' => 'ifTrue',
                    'dragKind' => 'then-true',
                    'railClass' => $railClass,
                    'rowClass' => $rowClass,
                    'controlClass' => $controlClass,
                    'iconClass' => $iconClass,
                ])
            </div>

            {{-- If false --}}
            <div>
                <div class="mb-3 flex items-center gap-2">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400">
                        <neura::icon name="x-mark" class="size-3.5" />
                    </span>
                    <span class="text-sm font-medium text-fg" x-text="label('ifFalse')"></span>
                </div>

                @include('neura::rule-builder.actions', [
                    'branch' => 'ifFalse',
                    'dragKind' => 'then-false',
                    'railClass' => $railClass,
                    'rowClass' => $rowClass,
                    'controlClass' => $controlClass,
                    'iconClass' => $iconClass,
                ])
            </div>
        </div>
    </section>
</div>

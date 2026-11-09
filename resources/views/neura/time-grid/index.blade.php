@props([
    'name' => null,
    'value' => null,
    'days' => null,
    'firstDay' => null,
    'start' => null,
    'end' => null,
    'step' => 60,
    'orientation' => 'auto',
    'selection' => 'rectangle',
    'levels' => null,
    'disabled' => [],
    'readonly' => false,
    'hourCycle' => null,
    'locale' => 'en',
    'format' => 'ranges',
    'clearable' => true,
    'size' => null,
    'rounded' => null,
    'color' => null,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    $readonly = filled($readonly) && $readonly;
    $clearable = filled($clearable) && $clearable;
    // `disabled` alone locks the whole grid (its value stays visible); a list blocks those ranges.
    $disabled = $disabled === true ? true : (is_array($disabled) ? $disabled : []);
    $locked = $disabled === true;
    // A bound value arrives through x-modelable; keeping it out of the markup keeps the
    // root's x-data and key steady while the value changes.
    $bound = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();

    $layout = Layout::make(is_array($days) ? $days : null, $firstDay, $start, $end, $step, $orientation, $locale);
    $cycle = Layout::hourCycle($hourCycle, $locale);
    $byRows = $layout['orientation'] === 'rows';

    // Size, radius and colour come from config('neura-kit.time-grid'), like the other form components.
    $sizes = PackResolver::timeGridSize($size ?? neura_config('time-grid', 'size'));
    $roundedClass = PackResolver::rounded($rounded ?? neura_config('time-grid', 'rounded'));
    $color = $color ?? neura_config('time-grid', 'color') ?? 'primary';

    // One brush by default; further levels without a colour take these in turn.
    $fallbackColors = ['warning', 'success', 'danger', 'sky'];
    $levelList = [];
    foreach (is_array($levels) && $levels !== [] ? $levels : ['on' => neura_trans('timeGridSelected', null, 'Selected')] as $key => $level) {
        $index = count($levelList);
        $levelColor = is_array($level) ? ($level['color'] ?? null) : null;
        $levelList[] = [
            'key' => (string) $key,
            'label' => (string) (is_array($level) ? ($level['label'] ?? $key) : $level),
            'color' => Layout::cssColor($levelColor ?? ($index === 0 ? $color : $fallbackColors[($index - 1) % count($fallbackColors)])),
        ];
    }

    $phrases = [
        'add' => neura_trans('timeGridAdd', null, 'Add'),
        'erase' => neura_trans('timeGridErase', null, 'Erase'),
        'clear' => neura_trans('timeGridClear', null, 'Clear all'),
        'level' => neura_trans('timeGridLevel', null, 'Level'),
    ];

    $config = [
        'value' => $bound ? [] : ($value ?? []),
        'columns' => $layout['columns'],
        'keyName' => $layout['weekdays'] ? 'day' : 'column',
        'slots' => $layout['slots'],
        'step' => $layout['step'],
        'start' => $layout['start'],
        'end' => $layout['end'],
        'orientation' => $layout['orientation'],
        'selection' => $selection === 'paint' ? 'paint' : 'rectangle',
        'levels' => $levelList,
        'disabled' => $disabled,
        'readonly' => $readonly,
        'format' => $format === 'days' ? 'days' : 'ranges',
        'hourCycle' => $cycle,
        'name' => $name,
        'phrases' => $phrases,
    ];

    // Hours running down the grid shrink with the step, so a day of quarter hours stays readable.
    $scale = $byRows ? 1 : [60 => 1, 30 => 0.65, 15 => 0.45][$layout['step']];
    $style = "--nk-tg-cell: {$sizes['cell']}; --nk-tg-scale: {$scale};";
    foreach ($levelList as $index => $level) {
        $style .= " --nk-tg-level-{$index}: {$level['color']};";
    }

    $slotCount = count($layout['slots']);
    $template = $byRows
        ? "minmax(2.75rem, max-content) repeat({$slotCount}, minmax(var(--nk-tg-cell), 1fr))"
        : 'minmax(3.5rem, max-content) repeat('.count($layout['columns']).', minmax(2.5rem, 1fr))';

    $cellLabel = fn (array $column, int $minutes) => $column['name'].', '
        .Layout::hourLabel($minutes, $cycle, false).' – '.Layout::hourLabel($minutes + $layout['step'], $cycle, false);
    $showToolbar = ! $readonly && ! $locked && (count($levelList) > 1 || $clearable);

    // The script reads its config once, so any change to it from the server (an unbound value,
    // a layout, a string) swaps the whole component for a fresh one. A bound value is not in it.
    $key = $attributes->get('wire:key');
    $key = ($key ? $key.'-' : 'time-grid-').md5(json_encode([$config, $sizes, $roundedClass, $clearable]));
    $invalid = $name && $errors->has($name);
@endphp

{{--
    A week (or any list of columns) of time cells, bound with wire:model / x-model.

        format="ranges"   [{ day: 'mon', start: '09:00', end: '12:00' }, …]   (column: … for a free list)
        format="days"     { mon: [{ start: '09:00', end: '12:00' }], tue: [], … }

    The cells are drawn here, empty; the script paints the value into them. Everything inside
    is wire:ignore, so a parent render leaves the painting alone, and the root is keyed by the
    props, so a new layout from the server rebuilds the component.
--}}
<div
    x-data="neuraTimeGrid({{ Js::from($config) }})"
    x-modelable="value"
    {{ $attributes->except('wire:key')->class(['nk-time-grid w-full'])->merge(['style' => $style]) }}
    wire:key="{{ $key }}"
    data-slot="time-grid"
    {{-- On the root, which Livewire still morphs, so a validation error shows and clears --}}
    @if ($invalid) data-invalid @endif
>
    <div wire:ignore class="flex flex-col gap-2">
        @if ($showToolbar)
            <div data-tg-toolbar class="flex flex-wrap items-center gap-2">
                @if (count($levelList) > 1)
                    <div role="group" aria-label="{{ $phrases['level'] }}" class="flex flex-wrap gap-1.5">
                        @foreach ($levelList as $index => $level)
                            <button
                                type="button"
                                data-tg-level="{{ $level['key'] }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                                x-bind:aria-pressed="brush === @js($level['key'])"
                                x-on:click="setBrush(@js($level['key']))"
                                class="nk-time-grid-chip"
                            >
                                <span class="nk-time-grid-swatch" style="background: var(--nk-tg-level-{{ $index }})"></span>
                                {{ $level['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <p data-tg-status aria-live="polite" x-text="status" class="min-h-4 grow text-xs text-fg-muted"></p>

                @if ($clearable)
                    <neura::button type="button" variant="ghost" size="sm" icon="trash" data-tg-clear x-on:click="clear()">
                        {{ $phrases['clear'] }}
                    </neura::button>
                @endif
            </div>
        @endif

        <div @class([
            'nk-time-grid-frame overflow-x-auto border border-edge bg-surface-raised p-2',
            $roundedClass,
        ])>
            <div
                role="grid"
                aria-multiselectable="true"
                @if ($readonly) aria-readonly="true" @endif
                @if ($locked) aria-disabled="true" @endif
                data-tg-grid
                data-orientation="{{ $layout['orientation'] }}"
                class="nk-time-grid-cells grid {{ $sizes['gap'] }} {{ $sizes['label'] }}"
                style="grid-template-columns: {{ $template }}"
            >
                @if ($byRows)
                    <div role="row" class="contents">
                        <div role="columnheader" class="nk-time-grid-corner"></div>
                        @foreach ($layout['slots'] as $si => $minutes)
                            <div role="columnheader" data-tg-slot="{{ $si }}" class="nk-time-grid-head justify-center" title="{{ Layout::hourLabel($minutes, $cycle, false) }}">
                                @if ($minutes % 60 === 0 && ($slotCount <= 24 || intdiv($minutes, 60) % 2 === 0))
                                    {{ Layout::hourLabel($minutes, $cycle, true) }}
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @foreach ($layout['columns'] as $ci => $column)
                        <div role="row" class="contents">
                            <div role="rowheader" data-tg-column="{{ $ci }}" class="nk-time-grid-head" title="{{ $column['name'] }}">{{ $column['label'] }}</div>
                            @foreach ($layout['slots'] as $si => $minutes)
                                <div role="gridcell" data-ci="{{ $ci }}" data-si="{{ $si }}" data-state="free" tabindex="{{ $ci === 0 && $si === 0 ? 0 : -1 }}" aria-selected="false" aria-label="{{ $cellLabel($column, $minutes) }}"></div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <div role="row" class="contents">
                        <div role="columnheader" class="nk-time-grid-corner"></div>
                        @foreach ($layout['columns'] as $ci => $column)
                            <div role="columnheader" data-tg-column="{{ $ci }}" class="nk-time-grid-head justify-center" title="{{ $column['name'] }}">{{ $column['label'] }}</div>
                        @endforeach
                    </div>

                    @foreach ($layout['slots'] as $si => $minutes)
                        <div role="row" class="contents">
                            <div role="rowheader" data-tg-slot="{{ $si }}" class="nk-time-grid-head" title="{{ Layout::hourLabel($minutes, $cycle, false) }}">
                                @if ($minutes % 60 === 0)
                                    {{ Layout::hourLabel($minutes, $cycle, false) }}
                                @endif
                            </div>
                            @foreach ($layout['columns'] as $ci => $column)
                                <div role="gridcell" data-ci="{{ $ci }}" data-si="{{ $si }}" data-state="free" tabindex="{{ $ci === 0 && $si === 0 ? 0 : -1 }}" aria-selected="false" aria-label="{{ $cellLabel($column, $minutes) }}"></div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        @if ($name)
            <template x-for="field in fields" :key="field.name">
                <input type="hidden" x-bind:name="field.name" x-bind:value="field.value" />
            </template>
        @endif
    </div>
</div>

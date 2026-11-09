@props([
    'name' => null,
    'value' => null,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'steps' => null,
    'range' => false,
    'minGap' => 0,
    'marks' => null,
    'bubble' => 'active',
    'input' => false,
    'prefix' => '',
    'suffix' => '',
    'decimals' => null,
    'histogram' => null,
    'label' => null,
    'description' => null,
    'showValue' => true,
    'live' => false,
    'disabled' => false,
    'locale' => null,
    'size' => null,
    'rounded' => null,
    'color' => null,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    $range = filled($range) && $range;
    $disabled = filled($disabled) && $disabled;
    $live = filled($live) && $live;
    $showValue = filled($showValue) && $showValue;
    $locale = $locale ?: app()->getLocale();
    $bubble = in_array($bubble, ['always', 'active', 'none'], true) ? $bubble : 'active';
    // A bound value arrives through x-modelable; keeping it out of the markup keeps the key steady.
    $bound = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();

    // A list of allowed values (0,10 / 0,25 / 0,5 / 1 / 2 / 5) spaced evenly, or a linear scale.
    $steps = is_array($steps) && $steps !== [] ? array_values(array_map('floatval', $steps)) : null;
    if ($steps) {
        sort($steps);
    }
    $min = $steps ? $steps[0] : (float) $min;
    $max = $steps ? $steps[count($steps) - 1] : max((float) $max, (float) $min);
    $step = (float) $step > 0 ? (float) $step : 1.0;
    $input = filled($input) && $input && ! $steps;

    // Size, radius and colour come from config('neura-kit.slider'), like the other form components.
    $sizes = PackResolver::sliderSize($size ?? neura_config('slider', 'size'));
    $radius = PackResolver::radiusValue($rounded ?? neura_config('slider', 'rounded'));
    // The number fields are inputs, so they take the inputs' corners.
    $fieldRounded = PackResolver::rounded(neura_config('input', 'rounded'));
    $tone = Layout::cssColor((string) ($color ?? neura_config('slider', 'color') ?: 'primary'));

    $decimalsOf = fn (float $v) => strlen(rtrim(substr(strrchr(rtrim(sprintf('%.10F', $v), '0'), '.') ?: '.', 1), '0'));
    $precision = $decimals !== null && $decimals !== ''
        ? max(0, (int) $decimals)
        : max(array_map($decimalsOf, $steps ?? [$min, $step]));
    $formatter = class_exists(\NumberFormatter::class) ? new \NumberFormatter($locale, \NumberFormatter::DECIMAL) : null;
    $formatter?->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
    $formatter?->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);
    $format = fn (float $v) => $prefix.($formatter ? $formatter->format($v) : number_format($v, $precision)).$suffix;
    $positionOf = function (float $v) use ($steps, $min, $max) {
        if ($steps) {
            $index = array_search(min(array_map(fn ($s) => [abs($s - $v), $s], $steps))[1], $steps, true);

            return count($steps) < 2 ? 0 : $index / (count($steps) - 1);
        }

        return $max == $min ? 0 : (min(max($v, $min), $max) - $min) / ($max - $min);
    };

    // Marks: true puts one on every step (labelled when there are few), a list labels its
    // values, value => label gives labels of their own.
    $markList = [];
    if ($marks === true || $marks === 'true') {
        $values = $steps ?? (($max - $min) / $step <= 40 ? range($min, $max, $step) : []);
        foreach ($values as $v) {
            $markList[] = ['value' => (float) $v, 'label' => count($values) <= 12 ? $format((float) $v) : null];
        }
    } elseif (is_array($marks)) {
        foreach ($marks as $key => $mark) {
            $markList[] = array_is_list($marks)
                ? ['value' => (float) $mark, 'label' => $format((float) $mark)]
                : ['value' => (float) $key, 'label' => (string) $mark];
        }
    }
    foreach ($markList as $i => $mark) {
        $markList[$i]['at'] = round($positionOf($mark['value']), 6);
    }
    $labelled = array_values(array_filter($markList, fn ($mark) => $mark['label'] !== null));

    // Bars behind the track: an audience, a price distribution. Heights as a share of the tallest.
    $bars = [];
    if (is_array($histogram) && $histogram !== []) {
        $tallest = max(array_map('floatval', $histogram)) ?: 1;
        $count = count($histogram);
        foreach (array_values($histogram) as $i => $bar) {
            $bars[] = ['height' => round(max(0, (float) $bar) / $tallest * 100, 2), 'at' => round(($i + 0.5) / $count, 6)];
        }
    }

    $thumbs = $range ? [0, 1] : [0];
    $thumbLabels = $range
        ? [neura_trans('sliderMinimum', null, 'Minimum'), neura_trans('sliderMaximum', null, 'Maximum')]
        : [null];

    $config = [
        'value' => $bound ? null : $value,
        'min' => $min,
        'max' => $max,
        'step' => $step,
        'steps' => $steps,
        'range' => $range,
        'minGap' => (float) $minGap,
        'live' => $live,
        'disabled' => $disabled,
        'locale' => $locale,
        'decimals' => $precision,
        'prefix' => (string) $prefix,
        'suffix' => (string) $suffix,
    ];

    $style = "--nk-slider-track: {$sizes['track']}; --nk-slider-thumb: {$sizes['thumb']}; --nk-slider-radius: {$radius}; --nk-slider-tone: {$tone};";

    // The script reads its config once, so a change to it from the server swaps the slider for a fresh one.
    $key = $attributes->get('wire:key');
    $key = ($key ? $key.'-' : 'slider-').md5(json_encode([$config, $sizes, $style, $markList, $bars, $label, $input, $bubble]));
    $invalid = $name && $errors->has($name);
    $slotMin = $steps ? 0 : $min;
    $slotMax = $steps ? count($steps) - 1 : $max;
    $slotStep = $steps ? 1 : $step;
@endphp

{{--
    A value, or a range with `range`, on a linear scale or a list of `steps`, bound with
    wire:model / x-model.

        default    250
        range      [25, 44]
        steps      one of the listed values (2, not its index)

    The rail and the marks are drawn here; the thumbs are native range inputs inside a
    wire:ignore body, so a parent render leaves a thumb where it is.
--}}
<div
    x-data="neuraSlider({{ Js::from($config) }})"
    x-modelable="value"
    x-id="['nk-slider']"
    {{ $attributes->except('wire:key')->class(['nk-slider', $sizes['text']])->merge(['style' => $style]) }}
    wire:key="{{ $key }}"
    data-slot="slider"
    data-bubble="{{ $bubble }}"
    @if ($range) data-range @endif
    @if ($disabled) data-disabled @endif
    {{-- On the root, which Livewire still morphs, so a validation error shows and clears --}}
    @if ($invalid) data-invalid @endif
>
    <div wire:ignore class="nk-slider-body" x-bind:style="vars">
        @if ($label || $input)
            <div class="nk-slider-header">
                @if ($label)
                    <label class="nk-slider-label" x-bind:for="$id('nk-slider', 0)">{{ $label }}</label>
                @endif

                @if ($input)
                    <div class="nk-slider-fields">
                        @foreach ($thumbs as $thumb)
                            @if ($thumb === 1)
                                <span class="nk-slider-dash" aria-hidden="true">–</span>
                            @endif
                            <span @class(['nk-slider-field', $sizes['field'], $fieldRounded])>
                                @if ($prefix !== '')
                                    <span class="nk-slider-affix" aria-hidden="true">{{ trim($prefix) }}</span>
                                @endif
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    class="nk-slider-number"
                                    aria-label="{{ trim(($label ?? '').' '.($thumbLabels[$thumb] ?? '')) ?: neura_trans('sliderValue', null, 'Value') }}"
                                    x-bind:value="ends[{{ $thumb }}]"
                                    x-on:change="onField({{ $thumb }}, $event)"
                                    x-on:keydown.enter.prevent="onField({{ $thumb }}, $event)"
                                    @disabled($disabled)
                                />
                                @if ($suffix !== '')
                                    <span class="nk-slider-affix" aria-hidden="true">{{ trim($suffix) }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @elseif ($showValue)
                    <output class="nk-slider-output" x-bind:for="$id('nk-slider', 0)" x-text="valueText"></output>
                @endif
            </div>
        @endif

        @if ($bars)
            <div class="nk-slider-histogram" aria-hidden="true">
                @foreach ($bars as $bar)
                    <span style="height: {{ $bar['height'] }}%" x-bind:data-on="within({{ $bar['at'] }}) ? '' : null"></span>
                @endforeach
            </div>
        @endif

        <div
            class="nk-slider-track"
            x-on:pointerdown="trackDown($event)"
            x-on:pointermove="trackMove($event)"
            x-on:pointerup="trackUp($event)"
            x-on:pointercancel="trackUp($event)"
            x-on:lostpointercapture="trackUp($event)"
        >
            <span class="nk-slider-rail" aria-hidden="true"></span>
            <span class="nk-slider-fill" aria-hidden="true"></span>

            @foreach ($markList as $mark)
                <span class="nk-slider-tick" aria-hidden="true" style="--nk-slider-at: {{ $mark['at'] }}" x-bind:data-on="within({{ $mark['at'] }}) ? '' : null"></span>
            @endforeach

            @foreach ($thumbs as $thumb)
                @if ($bubble !== 'none')
                    <span
                        class="nk-slider-bubble"
                        aria-hidden="true"
                        x-bind:style="at({{ $thumb }})"
                        x-bind:data-active="active === {{ $thumb }} ? '' : null"
                        x-text="text({{ $thumb }})"
                    ></span>
                @endif

                <input
                    type="range"
                    class="nk-slider-input"
                    x-bind:id="$id('nk-slider', {{ $thumb }})"
                    min="{{ $slotMin }}"
                    max="{{ $slotMax }}"
                    step="{{ $slotStep }}"
                    x-bind:value="slot({{ $thumb }})"
                    x-bind:aria-valuetext="text({{ $thumb }})"
                    x-bind:data-top="top({{ $thumb }}) ? '' : null"
                    @if ($thumbLabels[$thumb] !== null)
                        aria-label="{{ trim(($label ?? '').' – '.$thumbLabels[$thumb], ' –') }}"
                    @elseif (! $label)
                        aria-label="{{ neura_trans('sliderValue', null, 'Value') }}"
                    @endif
                    @if ($description) x-bind:aria-describedby="$id('nk-slider', 'description')" @endif
                    x-on:input="onInput({{ $thumb }}, $event)"
                    x-on:change="commit()"
                    x-on:focus="active = {{ $thumb }}"
                    x-on:blur="active = -1"
                    @disabled($disabled)
                />
            @endforeach
        </div>

        @if ($labelled)
            <div class="nk-slider-marks" aria-hidden="true">
                @foreach ($labelled as $mark)
                    <span
                        style="--nk-slider-at: {{ $mark['at'] }}"
                        @if ($mark['at'] <= 0) data-edge="start" @elseif ($mark['at'] >= 1) data-edge="end" @endif
                        x-bind:data-on="within({{ $mark['at'] }}) ? '' : null"
                    >{{ $mark['label'] }}</span>
                @endforeach
            </div>
        @endif

        @if ($description)
            <p class="nk-slider-description" x-bind:id="$id('nk-slider', 'description')">{{ $description }}</p>
        @endif

        @if ($name)
            @foreach ($thumbs as $thumb)
                <input type="hidden" name="{{ $range ? $name.'['.$thumb.']' : $name }}" x-bind:value="ends[{{ $thumb }}]" />
            @endforeach
        @endif
    </div>
</div>

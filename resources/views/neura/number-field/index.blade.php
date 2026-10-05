@props([
    'label' => 'Amount',
    'value' => 0,
    'min' => 0,
    'max' => null,
    'step' => 1,
    'largeStep' => null,
    'prefix' => '',
    'suffix' => '',
    'suffixOne' => '',
    'suffixMany' => '',
    'description' => null,
    'scrub' => false,
    'limitHint' => true,
    'decimals' => null,
    'name' => null,
    'id' => null,
    'size' => null,
    'variant' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'disabled' => false,
])

@php
    use Neura\Kit\Support\PackResolver;

    $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $size = $size ?? neura_config('number-field', 'size') ?? 'md';
    $variant = $variant ?? neura_config('number-field', 'variant') ?? 'outline';
    $color = $color ?? neura_config('number-field', 'color') ?? 'primary';
    $rounded = $rounded ?? neura_config('number-field', 'rounded');
    $shadow = $shadow ?? neura_config('number-field', 'shadow');

    if (str_contains((string) $variant, '-')) {
        [$maybeColor, $maybeVariant] = explode('-', (string) $variant, 2);
        if (in_array($maybeVariant, $variantStyles, true)) {
            $color = $color ?: $maybeColor;
            $variant = $maybeVariant;
        }
    }

    $size = in_array($size, $sizes, true) ? $size : 'md';
    $variant = in_array($variant, $variantStyles, true) ? $variant : 'outline';
    $color = strtolower((string) $color);
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : 'primary';
    $roundedToken = PackResolver::roundedToken($rounded);
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = in_array($variant, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-tone: var(--color-{$color}-600); --nk-tone-strong: var(--color-{$color}-900); --nk-tone-soft: var(--color-{$color}-100);"
        : '';

    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $attrValue) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $attrValue;
            break;
        }
    }

    $fieldId = $id ?: 'nk-num-'.substr(md5(uniqid((string) $label, true)), 0, 8);
    $stepText = (string) $step;
    $precision = $decimals !== null
        ? max(0, (int) $decimals)
        : (str_contains($stepText, '.') ? strlen(substr(strrchr($stepText, '.'), 1)) : 0);
    $initial = (float) $value;
    $initialNumber = number_format($initial, $precision, '.', '');
    $initialSuffix = ($suffixOne !== '' || $suffixMany !== '')
        ? (abs($initial) === 1.0 ? (string) $suffixOne : ((string) ($suffixMany !== '' ? $suffixMany : $suffixOne)))
        : (string) $suffix;
    $fieldName = $name ?: $wireModel;
    $limitHintOn = filter_var($limitHint, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $limitHintOn = $limitHintOn === null ? true : $limitHintOn;

    $config = [
        'value' => (float) $initialNumber,
        'min' => (float) $min,
        'max' => $max === null || $max === '' ? null : (float) $max,
        'step' => (float) $step === 0.0 ? 1 : (float) $step,
        'largeStep' => $largeStep === null || $largeStep === '' ? null : (float) $largeStep,
        'prefix' => (string) $prefix,
        'suffix' => (string) $suffix,
        'suffixOne' => (string) $suffixOne,
        'suffixMany' => (string) $suffixMany,
        'scrub' => (bool) $scrub,
        'disabled' => (bool) $disabled,
        'limitHint' => $limitHintOn,
        'decimals' => $decimals === null || $decimals === '' ? null : (int) $decimals,
        'wireProperty' => $wireModel,
        'fieldId' => $fieldId,
        'hintId' => $fieldId.'-hint',
        'descriptionId' => filled($description) ? $fieldId.'-desc' : null,
    ];

    $root = [
        'data-size' => $size,
        'data-variant' => $variant,
        'data-color' => $color !== '' ? $color : 'neutral',
        'data-rounded' => $roundedToken,
        'x-data' => 'neuraNumberField('.json_encode($config).')',
        'x-modelable' => 'value',
        'x-bind:data-editing' => "editing ? '' : null",
        'x-bind:data-invalid' => "invalid ? '' : null",
        'x-bind:data-disabled' => "disabled ? '' : null",
        'x-on:keydown' => 'onKey($event)',
    ];

    $userStyle = trim((string) $attributes->get('style'));
    $style = trim($toneStyle.' '.$userStyle);
    if ($style !== '') {
        $root['style'] = $style;
    }

    $rootAttributes = $attributes
        ->whereDoesntStartWith('wire:model')
        ->except('style')
        ->merge($root)
        ->class(['nk-num']);
@endphp

<div {{ $rootAttributes }}>
    <div class="nk-num-label-row">
        <label
            class="nk-num-label"
            for="{{ $fieldId }}"
            x-bind:class="scrub ? 'nk-num-scrub' : null"
            x-on:pointerdown="scrubStart($event)"
        >{{ $label }}</label>
        <span class="nk-num-hint" id="{{ $fieldId }}-hint" x-bind:hidden="hint === ''" x-text="hint"></span>
    </div>

    <div @class(['nk-num-shell', $roundedClass, $shadowClass])>
        <button
            type="button"
            class="nk-num-step"
            x-ref="dec"
            aria-label="Decrease {{ $label }}"
            x-bind:disabled="disabled"
            x-bind:aria-disabled="atMin() ? 'true' : 'false'"
            x-on:pointerdown="hold($event, -1)"
            x-on:pointerup="release()"
            x-on:pointercancel="release()"
            x-on:keydown.enter.prevent="stepBy(-1, step)"
            x-on:keydown.space.prevent="stepBy(-1, step)"
        >
            <neura::icon name="minus" />
        </button>

        <div class="nk-num-value">
            @if ($prefix !== '')
                <span class="nk-num-affix">{{ $prefix }}</span>
            @endif
            <span class="nk-num-figure" x-ref="figure">
                <span class="nk-num-sizer" x-text="figureText()">{{ $initialNumber }}</span>
                <span class="nk-num-reels" x-ref="reels" aria-hidden="true"></span>
                <input
                    x-ref="input"
                    id="{{ $fieldId }}"
                    class="nk-num-input"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    spellcheck="false"
                    size="1"
                    role="spinbutton"
                    value="{{ $initialNumber }}"
                    x-bind:aria-valuenow="value"
                    x-bind:aria-valuemin="min"
                    x-bind:aria-valuemax="max === null ? null : max"
                    x-bind:aria-valuetext="valueText()"
                    x-bind:aria-invalid="invalid ? 'true' : 'false'"
                    x-bind:aria-describedby="describedBy()"
                    @disabled($disabled)
                    x-on:input="onInput($event)"
                    x-on:focus="onFocus()"
                    x-on:blur="commitDraft()"
                />
            </span>
            @if ($suffix !== '' || $suffixOne !== '' || $suffixMany !== '')
                <span class="nk-num-affix" x-text="suffixLabel()">{{ $initialSuffix }}</span>
            @endif
        </div>

        <button
            type="button"
            class="nk-num-step"
            x-ref="inc"
            aria-label="Increase {{ $label }}"
            x-bind:disabled="disabled"
            x-bind:aria-disabled="atMax() ? 'true' : 'false'"
            x-on:pointerdown="hold($event, 1)"
            x-on:pointerup="release()"
            x-on:pointercancel="release()"
            x-on:keydown.enter.prevent="stepBy(1, step)"
            x-on:keydown.space.prevent="stepBy(1, step)"
        >
            <neura::icon name="plus" />
        </button>
    </div>

    @if (filled($description))
        <p class="nk-num-description" id="{{ $fieldId }}-desc">{{ $description }}</p>
    @endif

    @if ($fieldName)
        <input type="hidden" name="{{ $fieldName }}" value="{{ $initialNumber }}" x-bind:value="value" />
    @endif

    <span class="nk-num-live" aria-live="polite" x-text="live"></span>
</div>

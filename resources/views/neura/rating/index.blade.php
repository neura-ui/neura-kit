@props([
    'name' => null,
    'value' => null,
    'max' => 5,
    'readonly' => false,
    'half' => false,
    'clearable' => true,
    'labels' => null,
    'showValue' => false,
    'count' => null,
    'label' => null,
    'icon' => null,
    'disabled' => false,
    'locale' => null,
    'size' => null,
    'color' => null,
])

@php
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    $readonly = filled($readonly) && $readonly;
    $half = filled($half) && $half;
    $clearable = filled($clearable) && $clearable;
    $showValue = filled($showValue) && $showValue;
    $disabled = filled($disabled) && $disabled;
    $locale = $locale ?: app()->getLocale();
    $max = max(1, min(10, (int) $max));
    $bound = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();

    // Size, colour and icon come from config('neura-kit.rating').
    $sizes = PackResolver::ratingSize($size ?? neura_config('rating', 'size'));
    $tone = Layout::cssColor((string) ($color ?? neura_config('rating', 'color') ?: 'amber'));
    $icon = $icon ?? neura_config('rating', 'icon') ?: 'star';
    $paths = [
        'star' => 'M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z',
        'heart' => 'M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z',
        'circle' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
    ];
    $path = $paths[$icon] ?? $paths['star'];

    // Words for each note: true gives the built-in five, a list gives your own.
    $labelList = $labels === true || $labels === 'true'
        ? array_map(fn ($n) => neura_trans('ratingLabel'.$n, null, ['Terrible', 'Bad', 'Okay', 'Good', 'Excellent'][$n - 1]), range(1, 5))
        : (is_array($labels) ? array_values(array_map('strval', $labels)) : []);

    $number = is_numeric($value) ? (float) $value : 0.0;
    $number = max(0, min($max, $number));
    $formatter = class_exists(\NumberFormatter::class) ? new \NumberFormatter($locale, \NumberFormatter::DECIMAL) : null;
    $formatter?->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 1);
    $format = fn (float $v) => $formatter ? $formatter->format($v) : rtrim(rtrim(number_format($v, 1), '0'), '.');
    $outOf = fn (float $v) => neura_trans('ratingOutOf', ['value' => $format($v), 'max' => $max], '{value} out of {max}');
    $countText = $count !== null && $count !== ''
        ? neura_trans('ratingReviews', ['count' => $format((float) (int) $count)], '{count} reviews')
        : null;

    $style = "--nk-rating-icon: {$sizes['icon']}; --nk-rating-tone: {$tone};";
    $fillOf = fn (int $i, float $v) => round(max(0, min(1, $v - ($i - 1))) * 100, 2).'%';

    $config = [
        'value' => $bound ? null : $number,
        'max' => $max,
        'half' => $half,
        'clearable' => $clearable,
        'disabled' => $disabled,
        'labels' => $labelList,
        'showValue' => $showValue,
        'locale' => $locale,
        'outOf' => neura_trans('ratingOutOf', null, '{value} out of {max}'),
    ];
    $key = $attributes->get('wire:key');
    $key = ($key ? $key.'-' : 'rating-').md5(json_encode([$config, $style, $path, $label]));
    $groupLabel = $label ?: neura_trans('ratingTitle', null, 'Rating');
    $invalid = $name && $errors->has($name);
@endphp

{{--
    A note out of `max`, bound with wire:model / x-model: 3, or 3.5 with `half`; 0 is no note.
    `readonly` draws any value, 4.3 included, without a script.
--}}
@if ($readonly)
    <div {{ $attributes->class(['nk-rating', $sizes['text']])->merge(['style' => $style]) }} data-slot="rating" data-readonly>
        <span class="nk-rating-stars {{ $sizes['gap'] }}" role="img" aria-label="{{ $outOf($number) }}">
            @for ($i = 1; $i <= $max; $i++)
                <span class="nk-rating-star" style="--nk-rating-fill: {{ $fillOf($i, $number) }}" aria-hidden="true">
                    <svg class="nk-rating-base" viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}" /></svg>
                    <span class="nk-rating-fill"><svg viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}" /></svg></span>
                </span>
            @endfor
        </span>
        @if ($showValue)
            <span class="nk-rating-value">{{ $format($number) }}</span>
        @endif
        @if ($countText)
            <span class="nk-rating-caption">{{ $countText }}</span>
        @endif
    </div>
@else
    <div
        x-data="neuraRating({{ Js::from($config) }})"
        x-modelable="value"
        {{ $attributes->except('wire:key')->class(['nk-rating', $sizes['text']])->merge(['style' => $style]) }}
        wire:key="{{ $key }}"
        data-slot="rating"
        @if ($disabled) data-disabled @endif
        @if ($invalid) data-invalid @endif
    >
        <div wire:ignore class="nk-rating-body">
            <div
                class="nk-rating-stars {{ $sizes['gap'] }}"
                role="slider"
                tabindex="{{ $disabled ? -1 : 0 }}"
                aria-label="{{ $groupLabel }}"
                aria-valuemin="0"
                aria-valuemax="{{ $max }}"
                aria-valuenow="{{ $number }}"
                aria-valuetext="{{ $outOf($number) }}"
                x-bind:aria-valuenow="value || 0"
                x-bind:aria-valuetext="valueText"
                @if ($disabled) aria-disabled="true" @endif
                x-on:keydown="onKey($event)"
                x-on:pointerleave="hover = null"
            >
                @for ($i = 1; $i <= $max; $i++)
                    <span
                        class="nk-rating-star"
                        style="--nk-rating-fill: {{ $fillOf($i, $number) }}"
                        x-bind:style="fill({{ $i }})"
                        x-on:pointermove="preview({{ $i }}, $event)"
                        x-on:click="pick({{ $i }}, $event)"
                        aria-hidden="true"
                    >
                        <svg class="nk-rating-base" viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}" /></svg>
                        <span class="nk-rating-fill"><svg viewBox="0 0 24 24" fill="currentColor"><path d="{{ $path }}" /></svg></span>
                    </span>
                @endfor
            </div>

            @if ($labelList || $showValue)
                <span class="nk-rating-caption" aria-hidden="true" x-text="caption"></span>
            @endif
            @if ($countText)
                <span class="nk-rating-caption">{{ $countText }}</span>
            @endif

            @if ($name)
                <input type="hidden" name="{{ $name }}" x-bind:value="value || 0" />
            @endif
        </div>
    </div>
@endif

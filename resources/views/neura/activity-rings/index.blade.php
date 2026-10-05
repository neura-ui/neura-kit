@props([
    'metrics' => [],
    'days' => [],
    'day' => null,
    'focused' => null,
    'label' => 'Activity',
])

@php
    use Neura\Kit\Support\PackResolver;

    $normalizedMetrics = collect($metrics)->take(4)->map(function ($metric) {
        return [
            'id' => (string) ($metric['id'] ?? ''),
            'label' => (string) ($metric['label'] ?? ''),
            'unit' => (string) ($metric['unit'] ?? ''),
            'goal' => (float) ($metric['goal'] ?? 0),
            'color' => isset($metric['color']) && $metric['color'] !== '' ? (string) $metric['color'] : null,
        ];
    })->filter(fn ($metric) => $metric['id'] !== '')->values()->all();

    $normalizedDays = collect($days)->map(function ($item) {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'label' => (string) ($item['label'] ?? ''),
            'short' => (string) ($item['short'] ?? ''),
            'values' => (array) ($item['values'] ?? []),
        ];
    })->filter(fn ($item) => $item['id'] !== '')->values()->all();

    $roundedClass = PackResolver::rounded(neura_config('activity-rings', 'rounded'));

    $config = [
        'metrics' => $normalizedMetrics,
        'days' => $normalizedDays,
        'day' => $day,
        'focused' => $focused,
        'label' => (string) $label,
        'phrases' => [
            'ofGoals' => neura_trans('activityRingsOfGoals', null, 'of goals'),
            'amount' => neura_trans('activityRingsAmount', null, '{value} / {goal} {unit}'),
            'spoken' => neura_trans('activityRingsSpoken', null, '{label}, {value} of {goal} {unit}, {percent} percent'),
            'week' => neura_trans('activityRingsWeek', null, 'Days'),
        ],
    ];
@endphp

<div
    {{ $attributes->class('nk-rings') }}
    x-data="neuraActivityRings({{ Js::from($config) }})"
>
    <div class="nk-rings-body">
        <div class="nk-rings-stage">
            <svg class="nk-rings-svg" viewBox="0 0 220 220" x-ref="face" role="img" x-bind:aria-label="settledLabel()">
                <g data-rings></g>
            </svg>
            <div class="nk-rings-center" aria-hidden="true">
                <p class="nk-rings-score" x-text="centerPercent() + '%'"></p>
                <p class="nk-rings-of" x-text="phrases.ofGoals"></p>
            </div>
        </div>

        <div class="nk-rings-legend">
            <template x-for="(metric, index) in metrics" :key="metric.id">
                <button
                    type="button"
                    class="nk-rings-row {{ $roundedClass }}"
                    x-bind:class="isDim(metric.id) ? 'is-dim' : ''"
                    x-bind:aria-pressed="focusedId === metric.id ? 'true' : 'false'"
                    x-bind:aria-label="spoken(metric, index)"
                    x-on:click="toggleFocus(metric.id)"
                    x-on:mouseenter="hoverId = metric.id"
                    x-on:mouseleave="hoverId = null"
                >
                    <span class="nk-rings-swatch" x-bind:style="'background:' + colorOf(metric, index)"></span>
                    <span class="nk-rings-name" x-text="metric.label"></span>
                    <span class="nk-rings-pct" x-text="percentOf(index) + '%'"></span>
                    <span class="nk-rings-meta" x-text="amountLabel(metric, index)"></span>
                </button>
            </template>
        </div>
    </div>

    <div
        class="nk-rings-week"
        role="radiogroup"
        aria-label="{{ neura_trans('activityRingsWeek', null, 'Days') }}"
        x-on:keydown="onWeekKey($event)"
    >
        <template x-for="item in days" :key="item.id">
            <button
                type="button"
                class="nk-rings-day {{ $roundedClass }}"
                role="radio"
                data-day
                x-bind:aria-checked="item.id === dayId ? 'true' : 'false'"
                x-bind:tabindex="item.id === dayId ? 0 : -1"
                x-bind:aria-label="item.label"
                x-on:click="selectDay(item.id)"
            >
                <span x-text="item.short"></span>
            </button>
        </template>
    </div>

    <p class="nk-rings-caption" x-text="caption()"></p>
    <p class="nk-rings-status" role="status" x-text="announcement"></p>
</div>

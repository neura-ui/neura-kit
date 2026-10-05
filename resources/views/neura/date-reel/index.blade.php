@props([
    'mode' => 'datetime',
    'value' => null,
    'today' => null,
    'min' => null,
    'max' => null,
    'minuteStep' => 5,
    'hourCycle' => 12,
    'presets' => null,
    'title' => null,
    'confirm' => false,
    'intro' => true,
    'speed' => 1,
    'paused' => false,
    'label' => null,
    'name' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $reelMode = in_array($mode, ['datetime', 'date', 'time'], true) ? $mode : 'datetime';
    $cycle = (int) $hourCycle === 24 ? 24 : 12;
    $step = in_array((int) $minuteStep, [1, 5, 10, 15, 30], true) ? (int) $minuteStep : 5;

    $parts = function ($raw) {
        if ($raw === null || $raw === '') {
            return null;
        }
        $date = date_create((string) $raw);
        if (! $date) {
            return null;
        }

        return [
            'y' => (int) $date->format('Y'),
            'm' => (int) $date->format('n') - 1,
            'd' => (int) $date->format('j'),
            'h' => (int) $date->format('G'),
            'min' => (int) $date->format('i'),
        ];
    };

    $presetConfig = null;
    if ($presets === false) {
        $presetConfig = false;
    } elseif (is_array($presets)) {
        $presetConfig = collect($presets)->map(function ($preset) use ($parts) {
            $when = $parts($preset['value'] ?? null);
            if (! $when || empty($preset['label'])) {
                return null;
            }

            return [
                'label' => (string) $preset['label'],
                'value' => sprintf('%04d-%02d-%02dT%02d:%02d', $when['y'], $when['m'] + 1, $when['d'], $when['h'], $when['min']),
            ];
        })->filter()->values()->all();
    }

    $roundedClass = PackResolver::rounded(neura_config('date-reel', 'rounded'));

    $phrases = [
        'today' => neura_trans('dateReelToday', null, 'Today'),
        'tomorrow' => neura_trans('dateReelTomorrow', null, 'Tomorrow'),
        'yesterday' => neura_trans('dateReelYesterday', null, 'Yesterday'),
        'inDays' => neura_trans('dateReelInDays', null, 'In {count} days'),
        'daysAgo' => neura_trans('dateReelDaysAgo', null, '{count} days ago'),
        'at' => neura_trans('dateReelAt', null, 'at'),
        'thisEvening' => neura_trans('dateReelThisEvening', null, 'This evening'),
        'schedule' => neura_trans('dateReelSchedule', null, 'Schedule'),
        'scheduling' => neura_trans('dateReelScheduling', null, 'Scheduling'),
        'scheduled' => neura_trans('dateReelScheduled', null, 'Scheduled'),
        'day' => neura_trans('dateReelDay', null, 'Day'),
        'month' => neura_trans('dateReelMonth', null, 'Month'),
        'date' => neura_trans('dateReelDate', null, 'Date'),
        'year' => neura_trans('dateReelYear', null, 'Year'),
        'hour' => neura_trans('dateReelHour', null, 'Hour'),
        'minute' => neura_trans('dateReelMinute', null, 'Minute'),
        'period' => neura_trans('dateReelPeriod', null, 'AM or PM'),
        'presets' => neura_trans('dateReelPresets', null, 'Quick picks'),
        'label' => neura_trans('dateReelLabel', null, 'Date and time'),
    ];

    $config = [
        'mode' => $reelMode,
        'value' => $parts($value),
        'today' => $parts($today),
        'min' => $parts($min),
        'max' => $parts($max),
        'minuteStep' => $step,
        'hourCycle' => $cycle,
        'presets' => $presetConfig,
        'title' => $title ? (string) $title : '',
        'confirm' => (bool) $confirm,
        'intro' => filter_var($intro, FILTER_VALIDATE_BOOLEAN),
        'speed' => (float) $speed,
        'paused' => (bool) $paused,
        'label' => $label ? (string) $label : $phrases['label'],
        'locale' => str_replace('_', '-', app()->getLocale()),
        'phrases' => $phrases,
    ];
@endphp

<div
    {{ $attributes->class('nk-date-reel') }}
    x-data="neuraDateReel({{ Js::from($config) }})"
    x-modelable="iso"
    x-bind:class="dragging ? 'is-dragging' : ''"
>
    <p class="nk-reel-title" x-show="title" x-text="title"></p>
    <p class="nk-reel-summary" aria-hidden="true" x-text="summary"></p>

    <div class="nk-reel-stage" x-ref="stage"></div>

    <div class="nk-reel-foot" x-show="presets.length || showConfirm">
        <div class="nk-reel-presets" role="group" x-bind:aria-label="phrases.presets" x-show="presets.length">
            <template x-for="preset in presets" :key="preset.label">
                <button
                    type="button"
                    class="nk-reel-chip {{ $roundedClass }}"
                    x-on:click="pick(preset.value)"
                    x-bind:aria-pressed="matches(preset.value) ? 'true' : 'false'"
                    x-text="preset.label"
                ></button>
            </template>
        </div>

        <button
            type="button"
            class="nk-reel-confirm {{ $roundedClass }}"
            x-show="showConfirm"
            x-on:click="confirmPick()"
            x-bind:disabled="phase === 'pending'"
            x-bind:aria-busy="phase === 'pending' ? 'true' : 'false'"
        >
            <span class="nk-reel-confirm-grid">
                <span x-text="confirmText()">{{ $phrases['schedule'] }}</span>
                <span class="nk-reel-sizer" aria-hidden="true">{{ $phrases['schedule'] }}</span>
                <span class="nk-reel-sizer" aria-hidden="true">{{ $phrases['scheduling'] }}</span>
                <span class="nk-reel-sizer" aria-hidden="true">{{ $phrases['scheduled'] }}</span>
            </span>
        </button>
    </div>

    <input type="hidden" x-ref="input" @if ($name) name="{{ $name }}" @endif>
    <p class="nk-date-reel-status" role="status" x-text="announcement"></p>
</div>

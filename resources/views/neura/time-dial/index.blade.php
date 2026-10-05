@props([
    'cities' => [],
    'value' => null,
    'homeId' => null,
    'min' => null,
    'max' => null,
    'weekday' => 1,
    'hourCycle' => 12,
    'workingHours' => [540, 1080],
    'label' => 'Time',
    'name' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $normalized = collect($cities)->take(5)->map(function ($city) {
        return [
            'id' => (string) ($city['id'] ?? ''),
            'name' => (string) ($city['name'] ?? ''),
            'code' => strtoupper((string) ($city['code'] ?? '')),
            'offset' => (int) ($city['offset'] ?? 0),
        ];
    })->filter(fn ($city) => $city['id'] !== '')->values()->all();

    $cycle = (int) $hourCycle === 24 ? 24 : 12;
    $hours = array_values(array_map('intval', (array) $workingHours));
    $startHour = $hours[0] ?? 540;
    $endHour = $hours[1] ?? 1080;
    $initial = $value === null ? 0 : (int) $value;
    $home = $homeId ?: ($normalized[0]['id'] ?? null);

    $roundedClass = PackResolver::rounded(neura_config('time-dial', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('time-dial', 'shadow'));

    $hourLabels = $cycle === 24
        ? [0 => '00', 6 => '06', 12 => '12', 18 => '18']
        : [0 => '12 AM', 6 => '6 AM', 12 => 'Noon', 18 => '6 PM'];

    $ticks = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $rad = (($hour - 12) / 24) * 2 * M_PI - M_PI / 2;
        $ticks[] = [
            'major' => $hour % 6 === 0,
            'cx' => 100 + 78 * cos($rad),
            'cy' => 100 + 78 * sin($rad),
        ];
    }

    $marks = [];
    foreach ($hourLabels as $hour => $text) {
        $rad = (($hour - 12) / 24) * 2 * M_PI - M_PI / 2;
        $marks[] = [
            'text' => $text,
            'x' => 100 + 93 * cos($rad),
            'y' => 100 + 93 * sin($rad),
        ];
    }

    $config = [
        'cities' => $normalized,
        'value' => $initial,
        'homeId' => $home,
        'min' => $min === null ? null : (int) $min,
        'max' => $max === null ? null : (int) $max,
        'weekday' => (int) $weekday,
        'hourCycle' => $cycle,
        'workingHours' => [$startHour, $endHour],
        'label' => (string) $label,
        'phrases' => [
            'behind' => neura_trans('timeDialHoursBehind', null, '{hours} h behind'),
            'ahead' => neura_trans('timeDialHoursAhead', null, '{hours} h ahead'),
            'home' => neura_trans('timeDialHome', null, 'Home'),
            'nextDay' => neura_trans('timeDialNextDay', null, 'next day'),
            'previousDay' => neura_trans('timeDialPreviousDay', null, 'previous day'),
            'atWork' => neura_trans('timeDialAtWork', null, '{count} of {total} offices at work'),
        ],
    ];
@endphp

<div
    {{ $attributes->class('nk-time-dial') }}
    x-data="neuraTimeDial({{ Js::from($config) }})"
    x-modelable="value"
    x-bind:class="dragging ? 'is-dragging' : ''"
>
    <div class="nk-time-dial-body">
        <div
            class="nk-dial-stage"
            x-ref="dial"
            role="slider"
            tabindex="0"
            aria-label="{{ $label }}"
            aria-valuemin="0"
            aria-valuemax="1439"
            x-bind:aria-valuenow="homeMinutes"
            x-bind:aria-valuetext="valueText"
            x-on:pointerdown="onPointerDown($event)"
            x-on:pointermove="onPointerMove($event)"
            x-on:pointerup="onPointerUp()"
            x-on:pointercancel="onPointerUp()"
            x-on:keydown="onKey($event)"
            x-on:blur="clearPointerFocus()"
            x-on:selectstart.prevent
        >
            <span class="nk-dial-disc {{ $shadowClass }}" aria-hidden="true"></span>
            <svg class="nk-dial-svg" viewBox="0 0 200 200" aria-hidden="true">
                <g class="nk-dial-ring">
                    <circle class="nk-dial-face" cx="100" cy="100" r="78"></circle>
                    <path class="nk-dial-band" x-bind:d="bandPath"></path>
                    <circle class="nk-dial-track" cx="100" cy="100" r="78"></circle>
                    @foreach ($ticks as $tick)
                        <circle
                            class="nk-dial-tick {{ $tick['major'] ? 'is-major' : '' }}"
                            cx="{{ round($tick['cx'], 2) }}"
                            cy="{{ round($tick['cy'], 2) }}"
                            r="{{ $tick['major'] ? 1.35 : 0.85 }}"
                        ></circle>
                    @endforeach
                    @foreach ($marks as $mark)
                        <text class="nk-dial-hour" x="{{ round($mark['x'], 2) }}" y="{{ round($mark['y'], 2) }}">{{ $mark['text'] }}</text>
                    @endforeach
                </g>
                <circle class="nk-dial-hole" cx="100" cy="100" r="46"></circle>
                <g class="nk-dial-needle" x-bind:style="needleStyle">
                    <line x1="100" y1="54" x2="100" y2="28"></line>
                </g>
            </svg>

            <div class="nk-dial-readout" aria-hidden="true">
                <p class="nk-dial-cityname" x-text="home?.name"></p>
                <p class="nk-dial-time" x-text="formatTime(homeLocal)"></p>
                <p class="nk-dial-day" x-text="home ? weekdayName(home) : ''"></p>
            </div>

            <template x-for="city in cities" :key="city.id">
                <span
                    class="nk-dial-pin"
                    x-show="city.id !== homeId"
                    x-bind:class="isWorking(city) ? 'is-on' : ''"
                    x-bind:style="cityStyle(city)"
                    x-text="city.code"
                ></span>
            </template>
            <span class="nk-dial-cursor" aria-hidden="true" x-bind:style="cursorStyle()">
                <svg x-show="home && isWorking(home)" viewBox="0 0 16 16" aria-hidden="true">
                    <circle cx="8" cy="8" r="2.1" fill="currentColor"></circle>
                    <g fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round">
                        <path d="M8 1.6v1.7M8 12.7v1.7M1.6 8h1.7M12.7 8h1.7M3.4 3.4l1.2 1.2M11.4 11.4l1.2 1.2M12.6 3.4l-1.2 1.2M4.6 11.4l-1.2 1.2"></path>
                    </g>
                </svg>
                <svg x-show="home && !isWorking(home)" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill="currentColor" d="M9.2 2.2a5.1 5.1 0 1 0 4.4 7.6 4.2 4.2 0 1 1-4.4-7.6z"></path>
                </svg>
            </span>
        </div>

        <div class="nk-dial-panel">
            <ul class="nk-dial-list">
                <template x-for="city in cities" :key="city.id">
                    <li>
                        <button
                            type="button"
                            class="nk-dial-row {{ $roundedClass }}"
                            x-on:click="setHome(city.id)"
                            x-bind:aria-pressed="city.id === homeId ? 'true' : 'false'"
                        >
                            <svg class="nk-dial-sky" x-show="isWorking(city)" x-bind:class="isWorking(city) ? 'is-on' : ''" viewBox="0 0 16 16" aria-hidden="true">
                                <circle cx="8" cy="8" r="2.1" fill="currentColor"></circle>
                                <g fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round">
                                    <path d="M8 1.6v1.7M8 12.7v1.7M1.6 8h1.7M12.7 8h1.7M3.4 3.4l1.2 1.2M11.4 11.4l1.2 1.2M12.6 3.4l-1.2 1.2M4.6 11.4l-1.2 1.2"></path>
                                </g>
                            </svg>
                            <svg class="nk-dial-sky" x-show="!isWorking(city)" viewBox="0 0 16 16" aria-hidden="true">
                                <path fill="currentColor" d="M9.2 2.2a5.1 5.1 0 1 0 4.4 7.6 4.2 4.2 0 1 1-4.4-7.6z"></path>
                            </svg>
                            <span class="nk-dial-copy">
                                <span class="nk-dial-name" x-text="city.name"></span>
                                <span class="nk-dial-meta" x-text="cityMeta(city)"></span>
                            </span>
                            <span class="nk-dial-shift" x-show="dayNote(city)" x-text="dayNote(city)"></span>
                            <span class="nk-dial-clock" x-text="formatTime(localMinutes(city))"></span>
                        </button>
                    </li>
                </template>
            </ul>

            <div class="nk-dial-foot">
                <p class="nk-dial-count" aria-hidden="true" x-text="atWorkText()"></p>
                <div class="nk-dial-actions">
                    <button type="button" class="nk-dial-action is-primary" x-on:click="findOverlap()">
                        {{ neura_trans('timeDialFindOverlap', null, 'Find overlap') }}
                    </button>
                    <button type="button" class="nk-dial-action" x-on:click="reset()">
                        {{ neura_trans('reset', null, 'Reset') }}
                    </button>
                </div>
            </div>

            <p class="nk-dial-hint">{{ neura_trans('timeDialHint', null, 'Drag around the dial or use the arrow keys in 15 minute steps. Page Up and Page Down move by an hour.') }}</p>
        </div>
    </div>

    <input type="hidden" x-ref="input" @if ($name) name="{{ $name }}" @endif value="{{ $initial }}">
    <p class="nk-time-dial-status" role="status" x-text="announcement"></p>
</div>

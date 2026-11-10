@props([
    'name' => null,
    'value' => null,
    'start' => null,
    'frequencies' => null,
    'natural' => null,
    'suggestions' => null,
    'time' => true,
    'ends' => true,
    'optional' => false,
    'preview' => null,
    'summary' => true,
    'showRule' => false,
    'maxInterval' => 99,
    'hourCycle' => null,
    'locale' => null,
    'disabled' => false,
    'size' => null,
    'rounded' => null,
])

@php
    use Carbon\Carbon;
    use Illuminate\Support\Js;
    use Neura\Kit\Support\PackResolver;
    use Neura\Kit\Support\TimeGrid\Layout;

    $locale = $locale ?: app()->getLocale();
    $time = filled($time) && $time;
    $ends = filled($ends) && $ends;
    $optional = filled($optional) && $optional;
    $summary = filled($summary) && $summary;
    $showRule = filled($showRule) && $showRule;
    $disabled = filled($disabled) && $disabled;
    $natural = (bool) ($natural ?? neura_config('recurrence', 'natural'));
    $preview = max(0, min(20, (int) ($preview ?? neura_config('recurrence', 'preview') ?? 5)));
    $bound = $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();

    // Size and corners come from config('neura-kit.recurrence'), like the other form components.
    $sizes = PackResolver::recurrenceSize($size ?? neura_config('recurrence', 'size'));
    $roundedClass = PackResolver::rounded($rounded ?? neura_config('recurrence', 'rounded'));
    $radius = PackResolver::radiusValue($rounded ?? neura_config('recurrence', 'rounded'));
    [$selectedBg, $selectedText] = PackResolver::selectionClasses();
    $selected = $selectedBg.' '.$selectedText;
    $field = PackResolver::inputColor('select');

    $allFrequencies = ['daily', 'weekly', 'monthly', 'yearly'];
    $frequencies = is_array($frequencies) && $frequencies !== []
        ? array_values(array_intersect($allFrequencies, $frequencies))
        : $allFrequencies;

    $startDate = rescue(fn () => $start ? Carbon::parse($start) : Carbon::today(), Carbon::today(), false)->startOfDay();
    $fromSunday = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
    $weekStart = array_search(Layout::localeFirstDay($locale), $fromSunday, true) ?: 0;

    // Weekday toggles from the locale's first day; 1 November 2026 is a Sunday.
    $weekdays = [];
    for ($i = 0; $i < 7; $i++) {
        $day = ($weekStart + $i) % 7;
        $long = Carbon::create(2026, 11, 1 + $day)->locale($locale)->isoFormat('dddd');
        $weekdays[] = ['day' => $day, 'long' => $long, 'short' => mb_strtoupper(mb_substr($long, 0, 1))];
    }
    $months = array_map(fn ($m) => Carbon::create(2026, $m, 1)->locale($locale)->isoFormat('MMMM'), range(1, 12));

    $t = fn (string $key, string $fallback) => neura_trans($key, null, $fallback);
    $phrases = [
        'noRepeat' => $t('recurrenceNoRepeat', 'Does not repeat'),
        'every_daily' => $t('recurrenceEveryDaily', 'Every'),
        'every_weekly' => $t('recurrenceEveryWeekly', 'Every'),
        'every_monthly' => $t('recurrenceEveryMonthly', 'Every'),
        'every_yearly' => $t('recurrenceEveryYearly', 'Every'),
        'daily_one' => $t('recurrenceUnitDay', 'day'),
        'daily_many' => $t('recurrenceUnitDays', 'days'),
        'weekly_one' => $t('recurrenceUnitWeek', 'week'),
        'weekly_many' => $t('recurrenceUnitWeeks', 'weeks'),
        'monthly_one' => $t('recurrenceUnitMonth', 'month'),
        'monthly_many' => $t('recurrenceUnitMonths', 'months'),
        'yearly_one' => $t('recurrenceUnitYear', 'year'),
        'yearly_many' => $t('recurrenceUnitYears', 'years'),
    ];
    $frequencyLabels = [
        'daily' => $t('recurrenceDaily', 'Day'),
        'weekly' => $t('recurrenceWeekly', 'Week'),
        'monthly' => $t('recurrenceMonthly', 'Month'),
        'yearly' => $t('recurrenceYearly', 'Year'),
    ];
    $nthLabels = [
        1 => $t('recurrenceNth1', 'first'),
        2 => $t('recurrenceNth2', 'second'),
        3 => $t('recurrenceNth3', 'third'),
        4 => $t('recurrenceNth4', 'fourth'),
        -1 => $t('recurrenceNthLast', 'last'),
    ];

    // Ready-made sentences under the field; false hides them, a list replaces them.
    $suggestionList = $suggestions === false
        ? []
        : (is_array($suggestions) ? array_values($suggestions) : array_map(
            fn ($n) => $t('recurrenceSuggestion'.$n, [
                'every Monday and Thursday at 9am',
                'every 2 weeks on Tuesday',
                'the first Monday of the month at 10am',
                'weekdays at 8:30am until December 31',
            ][$n - 1]),
            range(1, 4),
        ));

    $config = [
        'value' => $bound ? null : $value,
        'start' => $startDate->format('Y-m-d'),
        'locale' => $locale,
        'weekStart' => $weekStart,
        'time' => $time,
        'optional' => $optional,
        'preview' => $preview,
        'maxInterval' => max(1, (int) $maxInterval),
        'phrases' => $phrases,
    ];

    $control = $sizes['control'];
    $select = "nk-recurrence-select {$control} {$roundedClass} {$field['border']} {$field['focus']}";
    $key = $attributes->get('wire:key');
    $key = ($key ? $key.'-' : 'recurrence-').md5(json_encode([$config, $sizes, $roundedClass, $frequencies, $natural, $suggestionList, $ends, $summary, $showRule, $disabled]));
    $invalid = $name && $errors->has($name);
    $hasAside = $summary || $preview > 0 || $showRule;
@endphp

{{--
    A repetition, bound with wire:model / x-model as an RRULE string (RFC 5545), without
    DTSTART: the series starts on `start` (today by default).

        FREQ=WEEKLY;BYDAY=MO,TH;BYHOUR=9;BYMINUTE=0
        FREQ=MONTHLY;BYDAY=1MO;UNTIL=20261231T235959
        FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=6;COUNT=5

    An empty value takes the default rule (weekly, on the start's weekday) unless `optional`
    adds a "Does not repeat" choice. Everything inside is wire:ignore and the root is keyed by
    the props, so a parent render leaves the editor alone and new props rebuild it.
--}}
<div
    x-data="neuraRecurrence({{ Js::from($config) }})"
    x-modelable="value"
    x-id="['nk-recurrence']"
    {{ $attributes->except('wire:key')->class(['nk-recurrence', $sizes['text']])->merge(['style' => '--nk-recurrence-radius: '.$radius.';']) }}
    wire:key="{{ $key }}"
    data-slot="recurrence"
    @if ($disabled) data-disabled @endif
    {{-- On the root, which Livewire still morphs, so a validation error shows and clears --}}
    @if ($invalid) data-invalid @endif
>
    <div wire:ignore @class(['nk-recurrence-body', $roundedClass, 'has-aside' => $hasAside]) @if ($disabled) inert @endif>
        <div class="nk-recurrence-editor">
            @if ($natural)
                <div class="nk-recurrence-natural">
                    <label @class(['nk-recurrence-search', $control, $roundedClass, $field['border']])>
                        <svg class="nk-recurrence-search-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 6.5A5.5 5.5 0 1 0 12 11M13.5 3v3.5H10" /></svg>
                        <span class="sr-only">{{ $t('recurrenceNaturalLabel', 'Describe the repetition') }}</span>
                        <input
                            type="text"
                            autocomplete="off"
                            spellcheck="false"
                            x-model="text"
                            x-on:keydown.enter.prevent="applyNatural()"
                            x-on:keydown.escape.stop="text = ''"
                            placeholder="{{ $t('recurrenceNaturalPlaceholder', 'e.g. every Monday and Thursday at 9am') }}"
                            x-bind:aria-describedby="$id('nk-recurrence', 'hint')"
                        />
                    </label>
                    <div class="nk-recurrence-hint" x-bind:id="$id('nk-recurrence', 'hint')" aria-live="polite">
                        @if ($suggestionList)
                            <div class="nk-recurrence-suggestions" x-show="!text.trim()">
                                @foreach ($suggestionList as $suggestion)
                                    <button type="button" @class(['nk-recurrence-chip', $roundedClass]) x-on:click="suggest(@js($suggestion))">{{ $suggestion }}</button>
                                @endforeach
                            </div>
                        @endif
                        <div class="nk-recurrence-understood" x-show="text.trim() && parsed" x-cloak>
                            <button type="button" @class(['nk-recurrence-apply', $roundedClass, $selected]) x-on:click="applyNatural()">{{ $t('recurrenceApply', 'Apply') }}</button>
                            <span x-text="naturalPreview"></span>
                        </div>
                        <p class="nk-recurrence-unknown" x-show="text.trim() && !parsed" x-cloak>{{ $t('recurrenceUnknown', 'Not understood. Try “every 2 weeks on Tuesday”.') }}</p>
                    </div>
                </div>
            @endif

            <div class="nk-recurrence-row">
                <span class="nk-recurrence-label" x-bind:id="$id('nk-recurrence', 'repeat')">{{ $t('recurrenceRepeat', 'Repeat') }}</span>
                <div @class(['nk-recurrence-segmented', $roundedClass]) role="group" x-bind:aria-labelledby="$id('nk-recurrence', 'repeat')">
                    @if ($optional)
                        <button type="button" class="{{ $control }}" aria-pressed="false" x-bind:aria-pressed="!active" x-on:click="setNone()">{{ $t('recurrenceNone', 'None') }}</button>
                    @endif
                    @foreach ($frequencies as $frequency)
                        <button type="button" class="{{ $control }}" aria-pressed="false" x-bind:aria-pressed="is(@js($frequency))" x-on:click="setFreq(@js($frequency))">{{ $frequencyLabels[$frequency] }}</button>
                    @endforeach
                </div>
            </div>

            <div class="nk-recurrence-rows" x-show="active">
                <div class="nk-recurrence-row">
                    <span class="nk-recurrence-label" x-text="everyLabel"></span>
                    <div class="nk-recurrence-inline">
                        <div @class(['nk-recurrence-stepper', $control, $roundedClass, $field['border']])>
                            <button type="button" aria-label="{{ $t('recurrenceFewer', 'Fewer') }}" x-on:click="stepInterval(-1)" x-bind:disabled="rule.interval <= 1">−</button>
                            <span x-text="rule.interval" aria-live="polite"></span>
                            <button type="button" aria-label="{{ $t('recurrenceMore', 'More') }}" x-on:click="stepInterval(1)">+</button>
                        </div>
                        <span class="nk-recurrence-muted" x-text="unitLabel"></span>
                    </div>
                </div>

                @if (in_array('weekly', $frequencies, true))
                    <div class="nk-recurrence-row" x-show="rule.freq === 'weekly'">
                        <span class="nk-recurrence-label" x-bind:id="$id('nk-recurrence', 'days')">{{ $t('recurrenceDays', 'On') }}</span>
                        <div class="nk-recurrence-days" role="group" x-bind:aria-labelledby="$id('nk-recurrence', 'days')">
                            @foreach ($weekdays as $weekday)
                                <button
                                    type="button"
                                    @class(['nk-recurrence-day', $sizes['day'], $roundedClass])
                                    aria-label="{{ $weekday['long'] }}"
                                    aria-pressed="false"
                                    x-bind:aria-pressed="hasDay({{ $weekday['day'] }})"
                                    x-bind:class="hasDay({{ $weekday['day'] }}) && @js($selected)"
                                    x-on:click="toggleDay({{ $weekday['day'] }})"
                                >{{ $weekday['short'] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (in_array('monthly', $frequencies, true))
                    <div class="nk-recurrence-row" x-show="rule.freq === 'monthly'">
                        <span class="nk-recurrence-label">{{ $t('recurrenceMonthDay', 'Day of the month') }}</span>
                        <div class="nk-recurrence-options">
                            <div class="nk-recurrence-inline">
                                <button type="button" @class(['nk-recurrence-pill', $control, $roundedClass]) aria-pressed="false" x-bind:aria-pressed="rule.monthMode === 'day'" x-bind:class="rule.monthMode === 'day' && @js($selected)" x-on:click="setMonthMode('day')">{{ $t('recurrenceOnThe', 'On the') }}</button>
                                <select class="{{ $select }}" x-model.number="rule.monthDay" x-on:change="setMonthMode('day')" aria-label="{{ $t('recurrenceMonthDay', 'Day of the month') }}">
                                    @for ($d = 1; $d <= 31; $d++)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endfor
                                    <option value="-1">{{ $t('recurrenceLastDay', 'last day') }}</option>
                                </select>
                            </div>
                            <div class="nk-recurrence-inline">
                                <button type="button" @class(['nk-recurrence-pill', $control, $roundedClass]) aria-pressed="false" x-bind:aria-pressed="rule.monthMode === 'nth'" x-bind:class="rule.monthMode === 'nth' && @js($selected)" x-on:click="setMonthMode('nth')">{{ $t('recurrenceOnThe', 'On the') }}</button>
                                <select class="{{ $select }}" x-model.number="rule.nth" x-on:change="setMonthMode('nth')" aria-label="{{ $t('recurrenceRank', 'Week of the month') }}">
                                    @foreach ($nthLabels as $nth => $nthLabel)
                                        <option value="{{ $nth }}">{{ $nthLabel }}</option>
                                    @endforeach
                                </select>
                                <select class="{{ $select }}" x-model.number="rule.nthDay" x-on:change="setMonthMode('nth')" aria-label="{{ $t('recurrenceWeekday', 'Weekday') }}">
                                    @foreach ($weekdays as $weekday)
                                        <option value="{{ $weekday['day'] }}">{{ $weekday['long'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                @if (in_array('yearly', $frequencies, true))
                    <div class="nk-recurrence-row" x-show="rule.freq === 'yearly'">
                        <span class="nk-recurrence-label">{{ $t('recurrenceDate', 'Date') }}</span>
                        <div class="nk-recurrence-inline">
                            <select class="{{ $select }}" x-model.number="rule.yearDay" aria-label="{{ $t('recurrenceDay', 'Day') }}">
                                @for ($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endfor
                            </select>
                            <select class="{{ $select }}" x-model.number="rule.month" aria-label="{{ $t('recurrenceMonth', 'Month') }}">
                                @foreach ($months as $index => $month)
                                    <option value="{{ $index + 1 }}">{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                @if ($time)
                    <div class="nk-recurrence-row">
                        <span class="nk-recurrence-label">{{ $t('recurrenceTime', 'Time') }}</span>
                        <div class="nk-recurrence-picker">
                            <neura::time-picker x-model="rule.time" :locale="$locale" :hour-cycle="$hourCycle" :size="$sizes['picker']" :rounded="$rounded" />
                        </div>
                    </div>
                @endif

                @if ($ends)
                    <div class="nk-recurrence-row">
                        <span class="nk-recurrence-label" x-bind:id="$id('nk-recurrence', 'ends')">{{ $t('recurrenceEnds', 'Ends') }}</span>
                        <div class="nk-recurrence-options" role="group" x-bind:aria-labelledby="$id('nk-recurrence', 'ends')">
                            <div class="nk-recurrence-inline">
                                <button type="button" @class(['nk-recurrence-pill', $control, $roundedClass]) aria-pressed="false" x-bind:aria-pressed="rule.end === 'never'" x-bind:class="rule.end === 'never' && @js($selected)" x-on:click="setEnd('never')">{{ $t('recurrenceNever', 'Never') }}</button>
                            </div>
                            <div class="nk-recurrence-inline" x-bind:data-idle="rule.end !== 'until'">
                                <button type="button" @class(['nk-recurrence-pill', $control, $roundedClass]) aria-pressed="false" x-bind:aria-pressed="rule.end === 'until'" x-bind:class="rule.end === 'until' && @js($selected)" x-on:click="setEnd('until')">{{ $t('recurrenceOn', 'On') }}</button>
                                <div class="nk-recurrence-picker nk-recurrence-until">
                                    <neura::date-picker
                                        x-model="rule.until"
                                        :locale="$locale"
                                        :first-day-of-week="$weekStart"
                                        :min-date="$startDate->format('Y-m-d')"
                                        :placeholder="$t('recurrenceUntilPlaceholder', 'Pick a date')"
                                    />
                                </div>
                            </div>
                            <div class="nk-recurrence-inline" x-bind:data-idle="rule.end !== 'count'">
                                <button type="button" @class(['nk-recurrence-pill', $control, $roundedClass]) aria-pressed="false" x-bind:aria-pressed="rule.end === 'count'" x-bind:class="rule.end === 'count' && @js($selected)" x-on:click="setEnd('count')">{{ $t('recurrenceAfter', 'After') }}</button>
                                <div @class(['nk-recurrence-stepper', $control, $roundedClass, $field['border']])>
                                    <button type="button" aria-label="{{ $t('recurrenceFewer', 'Fewer') }}" x-on:click="stepCount(-1)" x-bind:disabled="rule.count <= 1">−</button>
                                    <span x-text="rule.count" aria-live="polite"></span>
                                    <button type="button" aria-label="{{ $t('recurrenceMore', 'More') }}" x-on:click="stepCount(1)">+</button>
                                </div>
                                <span class="nk-recurrence-muted">{{ $t('recurrenceTimes', 'times') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if ($hasAside)
            <div class="nk-recurrence-aside">
                @if ($summary)
                    <div class="nk-recurrence-block">
                        <span class="nk-recurrence-caption">{{ $t('recurrenceSummary', 'Summary') }}</span>
                        <p class="nk-recurrence-summary" x-text="summary" aria-live="polite"></p>
                    </div>
                @endif

                @if ($preview > 0)
                    <div class="nk-recurrence-block" x-show="active">
                        <span class="nk-recurrence-caption">{{ $t('recurrenceNext', 'Next dates') }}</span>
                        <ol class="nk-recurrence-dates">
                            <template x-for="item in upcoming" x-bind:key="item.key">
                                <li>
                                    <span class="nk-recurrence-weekday" x-text="item.weekday"></span>
                                    <span class="nk-recurrence-date" x-text="item.date"></span>
                                    <span class="nk-recurrence-time" x-text="item.time"></span>
                                </li>
                            </template>
                        </ol>
                        <p class="nk-recurrence-muted" x-show="!upcoming.length" x-cloak>{{ $t('recurrenceNoDates', 'No upcoming date with this rule.') }}</p>
                    </div>
                @endif

                @if ($showRule)
                    <div class="nk-recurrence-block" x-show="active">
                        <span class="nk-recurrence-caption">{{ $t('recurrenceRule', 'Rule') }}</span>
                        <code class="nk-recurrence-code" x-text="rrule"></code>
                    </div>
                @endif
            </div>
        @endif

        @if ($name)
            <input type="hidden" name="{{ $name }}" x-bind:value="value" />
        @endif
    </div>
</div>

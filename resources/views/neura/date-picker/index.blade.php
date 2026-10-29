@props([
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'minDate' => null,
    'maxDate' => null,
    'disabled' => false,
    'disabledDates' => [],
    'locale' => 'en',
    'firstDayOfWeek' => 0,
    'multiple' => false,
    'range' => false,
    'time' => false,
    'hourCycle' => null,
    'minuteStep' => 5,
    'defaultTime' => null,
])

@php
    // A time goes with a single date only: range and multiple keep date-only values.
    $withTime = filled($time) && $time && ! $range && ! $multiple;

    $placeholder ??= $withTime
        ? neura_trans('datePickerSelectDateTime', null, 'Select date and time')
        : 'Select date';

    if ($range) {
        $initialValue = $value ?? ['start' => null, 'end' => null];
    } elseif ($multiple) {
        $initialValue = $value ?? [];
    } else {
        $initialValue = $value ?? '';
    }
@endphp

{{--
    Bind via x-modelable + native wire:model / x-model (do not use @entangle or $wire.set).
    displayValue must parse YYYY-MM-DD as a local calendar date — new Date('YYYY-MM-DD') is UTC
    and shifts the day in non-UTC timezones.

    With `time`, the value is a local YYYY-MM-DDTHH:mm string (the date reel's format). It is
    split into pickedDate / pickedTime for the calendar and the time column, and put back
    together only when the user picks something, so an incoming date-only value is never
    rewritten on load.
--}}
<div
    x-data="{
        value: @js($initialValue),
        range: {{ $range ? 'true' : 'false' }},
        multiple: {{ $multiple ? 'true' : 'false' }},
        locale: '{{ $locale }}',
        open: false,
        withTime: @js($withTime),
        pickedDate: '',
        pickedTime: '',
        hourCycle: @js(in_array((int) $hourCycle, [12, 24], true) ? (int) $hourCycle : null),
        minuteStep: @js(in_array((int) $minuteStep, [1, 5, 10, 15, 30], true) ? (int) $minuteStep : 5),
        defaultTime: @js($defaultTime),
        earliest: @js($minDate),
        latest: @js($maxDate),

        init() {
            if (! this.withTime) return;

            if (! this.hourCycle) {
                const resolved = new Intl.DateTimeFormat(this.locale, { hour: 'numeric' }).resolvedOptions().hourCycle;
                this.hourCycle = resolved === 'h11' || resolved === 'h12' ? 12 : 24;
            }

            this.split(this.value);
            this.$watch('value', (value) => this.split(value));
        },

        {{-- YYYY-MM-DD and HH:mm out of 2026-10-27, 2026-10-27T14:30 or 2026-10-27 14:30:00 --}}
        dateTimeParts(value) {
            const match = String(value ?? '').match(/^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}):(\d{2}))?/);
            if (! match) return { date: '', time: '' };
            return { date: match[1], time: match[2] !== undefined ? match[2] + ':' + match[3] : '' };
        },

        split(value) {
            const parts = this.dateTimeParts(value);
            if (this.pickedDate !== parts.date) this.pickedDate = parts.date;
            if (this.pickedTime !== parts.time) this.pickedTime = parts.time;
        },

        {{-- A min / max that carries a time also bounds the hours of its own day --}}
        timeBounds() {
            const min = this.dateTimeParts(this.earliest);
            const max = this.dateTimeParts(this.latest);
            return {
                min: min.time && min.date === this.pickedDate ? min.time : null,
                max: max.time && max.date === this.pickedDate ? max.time : null,
            };
        },

        compose(date, time) {
            let value = date + 'T' + time;

            const min = this.dateTimeParts(this.earliest);
            const max = this.dateTimeParts(this.latest);
            if (min.time && value < min.date + 'T' + min.time) value = min.date + 'T' + min.time;
            if (max.time && value > max.date + 'T' + max.time) value = max.date + 'T' + max.time;

            this.value = value;
            this.split(value);
        },

        onDatePicked(date) {
            if (! date) {
                this.value = '';
                this.split('');
                return;
            }
            this.compose(date, this.pickedTime || this.startTime());
        },

        onTimePicked(time) {
            this.compose(this.pickedDate || this.startDate(), time);
        },

        {{-- Picking a time first lands on today, kept inside min / max --}}
        startDate() {
            const now = new Date();
            let date = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            const min = this.dateTimeParts(this.earliest).date;
            const max = this.dateTimeParts(this.latest).date;
            if (min && date < min) date = min;
            if (max && date > max) date = max;
            return date;
        },

        {{-- Picking a date first takes default-time, else now rounded up to the minute step --}}
        startTime() {
            const preset = this.dateTimeParts('2000-01-01T' + (this.defaultTime ?? '')).time;
            if (preset) return preset;

            const now = new Date();
            const step = this.minuteStep;
            const total = Math.min(Math.ceil((now.getHours() * 60 + now.getMinutes()) / step) * step, 24 * 60 - step);
            return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
        },

        formatDateTimeLabel(date, time) {
            const d = String(date).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            const t = String(time).match(/^(\d{2}):(\d{2})$/);
            if (! d || ! t) return this.formatDateLabel(date);

            return new Date(Number(d[1]), Number(d[2]) - 1, Number(d[3]), Number(t[1]), Number(t[2])).toLocaleString(this.locale, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: this.hourCycle === 12 ? 'numeric' : '2-digit',
                minute: '2-digit',
                hourCycle: this.hourCycle === 12 ? 'h12' : 'h23',
            });
        },

        toDateStr(value) {
            if (! value || typeof value !== 'string') return value;
            const m = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
            return m ? (m[1] + '-' + m[2] + '-' + m[3]) : value;
        },

        formatDateLabel(dateStr) {
            if (! dateStr) return '';
            const normalized = this.toDateStr(dateStr);
            const m = String(normalized).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (! m) {
                const fallback = new Date(dateStr);
                return isNaN(fallback) ? dateStr : fallback.toLocaleDateString(this.locale, { year: 'numeric', month: 'short', day: 'numeric' });
            }
            const date = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
            return date.toLocaleDateString(this.locale, { year: 'numeric', month: 'short', day: 'numeric' });
        },

        get displayValue() {
            if (! this.value) return '';

            if (this.range) {
                if (typeof this.value === 'object') {
                    const start = this.value.start ? this.formatDateLabel(this.value.start) : '';
                    const end = this.value.end ? this.formatDateLabel(this.value.end) : '';
                    if (start && end) return start + ' - ' + end;
                    if (start) return start;
                }
                return '';
            }

            if (this.multiple) {
                if (Array.isArray(this.value)) {
                    return this.value.map(d => this.formatDateLabel(d)).join(', ');
                }
                return '';
            }

            if (this.withTime) {
                return this.pickedDate ? this.formatDateTimeLabel(this.pickedDate, this.pickedTime) : '';
            }

            return this.formatDateLabel(this.value);
        },

        toggle() {
            this.open = ! this.open;
        },

        hide() {
            this.open = false;
        }
    }"
    x-modelable="value"
    {{ $attributes->whereDoesntStartWith(['class'])->merge(['class' => 'w-full relative']) }}
    @click.away="hide()"
    @keydown.escape="hide()"
>
    @if ($withTime && $name)
        <input type="hidden" name="{{ $name }}" x-bind:value="value" />
    @endif

    <neura::input
        readonly
        @click.stop="toggle()"
        x-bind:value="displayValue"
        :placeholder="$placeholder"
        :disabled="$disabled"
        right-icon="calendar"
        class="cursor-pointer"
        :invalid="$name && $errors->has($name)"
    />

    <div
        x-show="open"
        x-transition:enter="nk-menu-motion"
        x-transition:enter-start="nk-menu-from"
        x-transition:enter-end="nk-menu-to"
        x-transition:leave="nk-menu-leaving"
        x-transition:leave-start="nk-menu-to"
        x-transition:leave-end="nk-menu-out"
        data-side="bottom"
        data-align="start"
        class="nk-menu-motion absolute z-50 mt-2 top-full left-0 w-max shadow-xl rounded-box"
        x-cloak
        @click.stop
    >
        @if ($withTime)
            {{-- The popup stays open while the time is chosen; click away or Escape closes it --}}
            <neura::calendar
                x-model="pickedDate"
                :name="false"
                :min-date="$minDate"
                :max-date="$maxDate"
                :disabled="$disabled"
                :disabled-dates="$disabledDates"
                :locale="$locale"
                :first-day-of-week="$firstDayOfWeek"
                @date-selected="onDatePicked($event.detail.date)"
                @time-selected="onTimePicked($event.detail.time)"
            >
                <x-slot:aside>
                    <neura::time-picker.columns
                        x-model="pickedTime"
                        x-effect="bounds = timeBounds(); disabled = {{ $disabled ? 'true' : 'false' }}"
                        :hour-cycle="$hourCycle"
                        :minute-step="$minuteStep"
                        :locale="$locale"
                    />
                </x-slot:aside>
            </neura::calendar>
        @else
            <neura::calendar
                x-model="value"
                :min-date="$minDate"
                :max-date="$maxDate"
                :disabled="$disabled"
                :disabled-dates="$disabledDates"
                :locale="$locale"
                :first-day-of-week="$firstDayOfWeek"
                :multiple="$multiple"
                :range="$range"
                @date-selected="if(!range && !multiple) hide()"
            />
        @endif
    </div>
</div>

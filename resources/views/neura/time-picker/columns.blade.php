@props([
    'hourCycle' => null,
    'minuteStep' => 5,
    'locale' => 'en',
    'label' => null,
    'size' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    // null follows the locale: 12 hours with AM / PM in en-US, 24 hours in fr.
    $cycle = in_array((int) $hourCycle, [12, 24], true) ? (int) $hourCycle : null;
    $step = in_array((int) $minuteStep, [1, 5, 10, 15, 30], true) ? (int) $minuteStep : 5;

    [$selectedBg, $selectedText] = PackResolver::selectionClasses();
    $sizes = PackResolver::timePickerSize($size);

    $labels = [
        'time' => $label ?: neura_trans('timePickerTime', null, 'Time'),
        'hour' => neura_trans('timePickerHour', null, 'Hour'),
        'minute' => neura_trans('timePickerMinute', null, 'Minute'),
        'period' => neura_trans('timePickerPeriod', null, 'AM or PM'),
    ];

    $columnClasses = 'relative flex flex-col gap-1 overflow-y-auto overscroll-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden '.$sizes['column'];
    $optionClasses = 'inline-flex w-full shrink-0 cursor-pointer items-center justify-center rounded-md tabular-nums transition-colors duration-fast ease-standard focus-visible:ring-2 focus-visible:ring-edge-focus focus-visible:outline-none disabled:pointer-events-none '.$sizes['option'];
@endphp

{{--
    Time columns shared by the date picker and the time picker: hours,
    minutes and, on a 12-hour clock, AM / PM. The value is a 24-hour HH:mm
    string, bound with x-model through x-modelable or set with x-effect.
    A choice made here fires `time-selected`. `bounds` (earliest / latest
    HH:mm) and `disabled` are set by the picker that holds the columns.
--}}
<div
    x-data="{
        time: '',
        bounds: { min: null, max: null },
        disabled: false,
        cycle: @js($cycle),
        step: @js($step),
        locale: @js($locale),
        periods: ['AM', 'PM'],
        settled: false,

        init() {
            if (! this.cycle) {
                const resolved = new Intl.DateTimeFormat(this.locale, { hour: 'numeric' }).resolvedOptions().hourCycle;
                this.cycle = resolved === 'h11' || resolved === 'h12' ? 12 : 24;
            }

            const period = new Intl.DateTimeFormat(this.locale, { hour: 'numeric', hourCycle: 'h12' });
            this.periods = [1, 13].map((hour) =>
                period.formatToParts(new Date(2000, 0, 1, hour)).find((part) => part.type === 'dayPeriod')?.value
                    ?? (hour < 12 ? 'AM' : 'PM'));

            {{--
                A change from outside (the picker's default time) brings the selection into view.
                A click here leaves the lists where they are. setTimeout rather than $nextTick:
                Alpine holds next ticks while the popup's transition runs.
            --}}
            this.$watch('time', () => {
                if (this.settled) {
                    this.settled = false;
                    return;
                }
                setTimeout(() => this.reveal());
            });

            {{-- The column opens hidden, so it is centred once it has a size --}}
            if (typeof ResizeObserver !== 'undefined') {
                new ResizeObserver(() => this.reveal()).observe(this.$el);
            }
        },

        pad(value) {
            return String(value).padStart(2, '0');
        },

        toMinutes(value) {
            const match = /^(\d{1,2}):(\d{2})/.exec(value || '');
            return match ? Number(match[1]) * 60 + Number(match[2]) : null;
        },

        get hour() {
            const total = this.toMinutes(this.time);
            return total === null ? null : Math.floor(total / 60);
        },

        get minute() {
            const total = this.toMinutes(this.time);
            return total === null ? null : total % 60;
        },

        get shown() {
            const total = this.toMinutes(this.time);
            if (total === null) return '';

            return new Date(2000, 0, 1, Math.floor(total / 60), total % 60).toLocaleTimeString(this.locale, {
                hour: this.cycle === 12 ? 'numeric' : '2-digit',
                minute: '2-digit',
                hourCycle: this.cycle === 12 ? 'h12' : 'h23',
            });
        },

        {{-- Whether any minute from `from` to `to` (minutes since midnight) is inside the bounds --}}
        reachable(from, to) {
            const min = this.toMinutes(this.bounds?.min);
            const max = this.toMinutes(this.bounds?.max);
            return (min === null || to >= min) && (max === null || from <= max);
        },

        {{-- The one option of a column that takes Tab: the selected one, else the first enabled --}}
        anchor(options) {
            const target = options.find((option) => option.selected) ?? options.find((option) => ! option.disabled);
            return options.map((option) => ({ ...option, tabindex: option === target ? 0 : -1 }));
        },

        get hourOptions() {
            const offset = this.cycle === 12 && (this.hour ?? 0) >= 12 ? 12 : 0;
            const hours = Array.from({ length: this.cycle === 12 ? 12 : 24 }, (_, index) => index + offset);

            return this.anchor(hours.map((hour) => ({
                value: hour,
                label: this.cycle === 12 ? String(hour % 12 || 12) : this.pad(hour),
                selected: this.hour === hour,
                disabled: ! this.reachable(hour * 60, hour * 60 + 59),
            })));
        },

        get minuteOptions() {
            const minutes = [];
            for (let minute = 0; minute < 60; minute += this.step) minutes.push(minute);

            {{-- A value off the step (14:37 with a 5 minute step) stays listed and selected --}}
            if (this.minute !== null && ! minutes.includes(this.minute)) {
                minutes.push(this.minute);
                minutes.sort((a, b) => a - b);
            }

            return this.anchor(minutes.map((minute) => ({
                value: minute,
                label: this.pad(minute),
                selected: this.minute === minute,
                disabled: this.hour !== null && ! this.reachable(this.hour * 60 + minute, this.hour * 60 + minute),
            })));
        },

        get periodOptions() {
            return this.anchor([0, 12].map((offset, index) => ({
                value: offset,
                label: this.periods[index],
                selected: this.hour !== null && (this.hour >= 12) === (offset === 12),
                disabled: ! this.reachable(offset * 60, offset * 60 + 719),
            })));
        },

        optionsFor(kind) {
            if (kind === 'hour') return this.hourOptions;
            if (kind === 'minute') return this.minuteOptions;
            return this.periodOptions;
        },

        pick(kind, value) {
            if (this.disabled) return;

            let hour = this.hour;
            let minute = this.minute;

            if (kind === 'hour') hour = value;
            if (kind === 'minute') minute = value;
            if (kind === 'period') hour = hour === null ? value : (hour % 12) + value;

            if (hour === null) hour = new Date().getHours();
            if (minute === null) minute = 0;

            let total = hour * 60 + minute;
            const min = this.toMinutes(this.bounds?.min);
            const max = this.toMinutes(this.bounds?.max);
            if (min !== null && total < min) total = min;
            if (max !== null && total > max) total = max;

            const time = this.pad(Math.floor(total / 60)) + ':' + this.pad(total % 60);

            if (time !== this.time) {
                this.settled = true;
                this.time = time;
            }

            this.$dispatch('time-selected', { time });
        },

        onKey(event, kind) {
            if (! ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();

            const column = event.currentTarget;
            const options = this.optionsFor(kind).filter((option) => ! option.disabled);
            if (options.length === 0) return;

            const current = options.findIndex((option) => option.selected);
            let next = 0;

            if (event.key === 'End') {
                next = options.length - 1;
            } else if (event.key !== 'Home' && current !== -1) {
                next = Math.min(options.length - 1, Math.max(0, current + (event.key === 'ArrowDown' ? 1 : -1)));
            }

            this.pick(kind, options[next].value);

            this.$nextTick(() => {
                const selected = column.querySelector('[aria-selected=true]');
                selected?.focus({ preventScroll: true });
                selected?.scrollIntoView({ block: 'nearest' });
            });
        },

        reveal() {
            for (const column of this.$el.querySelectorAll('[role=listbox]')) {
                const selected = column.querySelector('[aria-selected=true]');
                if (! column.clientHeight || ! selected) continue;

                column.scrollTop = selected.offsetTop - (column.clientHeight - selected.offsetHeight) / 2;
            }
        },
    }"
    x-modelable="time"
    {{ $attributes->class('flex min-h-0 grow basis-0 flex-col max-sm:h-64 max-sm:basis-auto') }}
    role="group"
    aria-label="{{ $labels['time'] }}"
    data-slot="time-columns"
>
    {{-- Lines up with the calendar's month caption --}}
    <div class="flex h-8 shrink-0 items-center justify-center gap-1.5 text-sm font-medium text-fg">
        @if ($label)
            <span class="font-normal text-fg-muted">{{ $label }}</span>
        @else
            <neura::icon name="clock" class="size-4 text-fg-muted" />
        @endif
        <span class="tabular-nums" x-text="shown || '--:--'"></span>
    </div>

    {{-- A 0px basis: the columns fill the calendar's height without adding to it --}}
    <div class="mt-3 flex min-h-0 grow basis-0 justify-center gap-1">
        <div
            role="listbox"
            aria-label="{{ $labels['hour'] }}"
            class="{{ $columnClasses }}"
            x-on:keydown="onKey($event, 'hour')"
        >
            <template x-for="option in hourOptions" :key="option.value">
                <button
                    type="button"
                    role="option"
                    class="{{ $optionClasses }}"
                    x-bind:class="option.selected ? @js($selectedBg.' '.$selectedText) : (option.disabled ? 'text-fg-disabled opacity-50' : 'text-fg hover:bg-hover')"
                    x-bind:aria-selected="option.selected ? 'true' : 'false'"
                    x-bind:disabled="disabled || option.disabled"
                    x-bind:tabindex="option.tabindex"
                    x-on:click="pick('hour', option.value)"
                    x-text="option.label"
                ></button>
            </template>
        </div>

        <div
            role="listbox"
            aria-label="{{ $labels['minute'] }}"
            class="{{ $columnClasses }}"
            x-on:keydown="onKey($event, 'minute')"
        >
            <template x-for="option in minuteOptions" :key="option.value">
                <button
                    type="button"
                    role="option"
                    class="{{ $optionClasses }}"
                    x-bind:class="option.selected ? @js($selectedBg.' '.$selectedText) : (option.disabled ? 'text-fg-disabled opacity-50' : 'text-fg hover:bg-hover')"
                    x-bind:aria-selected="option.selected ? 'true' : 'false'"
                    x-bind:disabled="disabled || option.disabled"
                    x-bind:tabindex="option.tabindex"
                    x-on:click="pick('minute', option.value)"
                    x-text="option.label"
                ></button>
            </template>
        </div>

        <template x-if="cycle === 12">
            <div
                role="listbox"
                aria-label="{{ $labels['period'] }}"
                class="{{ $columnClasses }}"
                x-on:keydown="onKey($event, 'period')"
            >
                <template x-for="option in periodOptions" :key="option.value">
                    <button
                        type="button"
                        role="option"
                        class="{{ $optionClasses }}"
                        x-bind:class="option.selected ? @js($selectedBg.' '.$selectedText) : (option.disabled ? 'text-fg-disabled opacity-50' : 'text-fg hover:bg-hover')"
                        x-bind:aria-selected="option.selected ? 'true' : 'false'"
                        x-bind:disabled="disabled || option.disabled"
                        x-bind:tabindex="option.tabindex"
                        x-on:click="pick('period', option.value)"
                        x-text="option.label"
                    ></button>
                </template>
            </div>
        </template>
    </div>
</div>

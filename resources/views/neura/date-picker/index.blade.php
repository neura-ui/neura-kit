@props([
    'name' => null,
    'value' => null,
    'placeholder' => 'Select date',
    'minDate' => null,
    'maxDate' => null,
    'disabled' => false,
    'disabledDates' => [],
    'locale' => 'en',
    'firstDayOfWeek' => 0,
    'multiple' => false,
    'range' => false,
])

@php
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
--}}
<div
    x-data="{
        value: @js($initialValue),
        range: {{ $range ? 'true' : 'false' }},
        multiple: {{ $multiple ? 'true' : 'false' }},
        locale: '{{ $locale }}',
        open: false,

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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute z-50 mt-2 top-full left-0 w-auto shadow-xl rounded-box"
        x-cloak
        @click.stop
    >
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
    </div>
</div>

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
    $wireModel = $attributes->wire('model');
@endphp

<div
    x-data="{
        value: @if($wireModel->value()) @entangle($wireModel) @else {{ json_encode($value) }} @endif,
        range: {{ $range ? 'true' : 'false' }},
        multiple: {{ $multiple ? 'true' : 'false' }},
        locale: '{{ $locale }}',
        open: false,

        get displayValue() {
            if (!this.value) return '';
            
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            
            const format = (dateStr) => {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return isNaN(date) ? dateStr : date.toLocaleDateString(this.locale, options);
            };

            if (this.range) {
                if (typeof this.value === 'object') {
                    const start = this.value.start ? format(this.value.start) : '';
                    const end = this.value.end ? format(this.value.end) : '';
                    if (start && end) return `${start} - ${end}`;
                    if (start) return start;
                }
                return '';
            }

            if (this.multiple) {
                if (Array.isArray(this.value)) {
                    return this.value.map(d => format(d)).join(', ');
                }
                return '';
            }

            return format(this.value);
        },

        toggle() {
            if (this.open) {
                this.open = false;
            } else {
                this.open = true;
            }
        },

        hide() {
            this.open = false;
        }
    }"
    x-modelable="value"
    {{ $attributes->whereDoesntStartWith(['wire:model', 'class']) }}
    class="w-full relative"
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
        :invalid="$errors->has($name)"
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

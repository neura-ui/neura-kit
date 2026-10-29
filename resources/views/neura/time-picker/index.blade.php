@props([
    'name' => null,
    'value' => null,
    'placeholder' => null,
    'disabled' => false,
    'locale' => 'en',
    'hourCycle' => null,
    'minuteStep' => 5,
    'min' => null,
    'max' => null,
    'range' => false,
    'multiple' => false,
    'addLabel' => null,
    'size' => null,
    'rounded' => null,
    'shadow' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $range = filled($range) && $range;
    $multiple = filled($multiple) && $multiple;
    $disabled = filled($disabled) && $disabled;
    $cycle = in_array((int) $hourCycle, [12, 24], true) ? (int) $hourCycle : null;

    // Size and style come from config('neura-kit.time-picker'), like the other form components.
    $size = $size ?? neura_config('time-picker', 'size') ?? 'md';
    $rounded = $rounded ?? neura_config('time-picker', 'rounded');
    $shadow = $shadow ?? neura_config('time-picker', 'shadow');
    $sizes = PackResolver::timePickerSize($size);

    $placeholder ??= $range
        ? neura_trans('timePickerSelectRange', null, 'Select a time range')
        : neura_trans('timePickerSelectTime', null, 'Select time');
    $addLabel ??= $range
        ? neura_trans('timePickerAddSlot', null, 'Add a time slot')
        : neura_trans('timePickerAddTime', null, 'Add a time');
    $fromLabel = neura_trans('timePickerFrom', null, 'From');
    $toLabel = neura_trans('timePickerTo', null, 'To');
    $removeLabel = neura_trans('timePickerRemove', null, 'Remove');

    $initialValue = $value ?? ($multiple ? [] : ($range ? ['start' => null, 'end' => null] : ''));
    $invalid = $name && $errors->has($name);

    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = PackResolver::shadow($shadow);
@endphp

{{--
    A time, a From – To range, or several of either, bound with wire:model / x-model.

        default          '09:00'
        range            { start: '09:00', end: '18:00' }
        multiple         ['08:00', '20:00']
        range multiple   [{ start: '09:00', end: '12:00' }, { start: '14:00', end: '18:00' }]

    Times are 24-hour HH:mm whatever the clock shown; HH:mm:ss coming in is read as HH:mm.
    In range mode the other keys of a slot (a weekday, an id) are kept, so a row object can be
    bound as it is. Values are written back only when the user picks something.
--}}
<div
    x-data="{
        value: @js($initialValue),
        range: @js($range),
        multiple: @js($multiple),
        locked: @js($disabled),
        locale: @js($locale),
        hourCycle: @js($cycle),
        earliest: @js($min),
        latest: @js($max),
        rows: [],
        editing: null,
        fresh: null,
        nextId: 0,

        init() {
            if (! this.hourCycle) {
                const resolved = new Intl.DateTimeFormat(this.locale, { hour: 'numeric' }).resolvedOptions().hourCycle;
                this.hourCycle = resolved === 'h11' || resolved === 'h12' ? 12 : 24;
            }

            this.earliest = this.clock(this.earliest) || null;
            this.latest = this.clock(this.latest) || null;
            this.rows = this.toRows(this.value);

            {{-- Our own writes come back through x-model unchanged; only a value changed from outside rebuilds the rows --}}
            this.$watch('value', (value) => {
                if (JSON.stringify(value) === JSON.stringify(this.fromRows())) return;
                this.rows = this.toRows(value);
            });
        },

        {{-- HH:mm out of 9:00, 09:00 or 09:00:00 --}}
        clock(value) {
            const match = /^(\d{1,2}):(\d{2})/.exec(String(value ?? ''));
            return match ? match[1].padStart(2, '0') + ':' + match[2] : '';
        },

        toMinutes(value) {
            const match = /^(\d{2}):(\d{2})$/.exec(value || '');
            return match ? Number(match[1]) * 60 + Number(match[2]) : null;
        },

        fromMinutes(total) {
            return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
        },

        makeRow(item) {
            if (this.range) {
                const data = item && typeof item === 'object' ? item : {};
                return { id: this.nextId++, start: this.clock(data.start), end: this.clock(data.end), data };
            }
            return { id: this.nextId++, start: this.clock(item), end: '', data: {} };
        },

        toRows(value) {
            if (this.multiple) return (Array.isArray(value) ? value : []).map((item) => this.makeRow(item));
            return [this.makeRow(value)];
        },

        fromRows() {
            const slot = (row) => ({ ...row.data, start: row.start || null, end: row.end || null });
            if (this.multiple) return this.rows.map((row) => this.range ? slot(row) : row.start);
            return this.range ? slot(this.rows[0]) : (this.rows[0]?.start ?? '');
        },

        commit() {
            const value = this.fromRows();
            if (JSON.stringify(value) !== JSON.stringify(this.value)) this.value = value;
        },

        format(value) {
            const total = this.toMinutes(value);
            if (total === null) return '';

            return new Date(2000, 0, 1, Math.floor(total / 60), total % 60).toLocaleTimeString(this.locale, {
                hour: this.hourCycle === 12 ? 'numeric' : '2-digit',
                minute: '2-digit',
                hourCycle: this.hourCycle === 12 ? 'h12' : 'h23',
            });
        },

        describe(row) {
            if (! this.range) return this.format(row.start);
            if (! row.start && ! row.end) return '';
            return (this.format(row.start) || '--:--') + ' – ' + (this.format(row.end) || '--:--');
        },

        {{-- An end never comes before its own start --}}
        boundsFor(row, part) {
            return {
                min: part === 'end' && row.start ? row.start : this.earliest,
                max: this.latest,
            };
        },

        {{-- Moving the start of a full range keeps its length, like a calendar event --}}
        pickStart(row, time) {
            if (this.range && row.start && row.end) {
                const length = this.toMinutes(row.end) - this.toMinutes(row.start);
                if (length >= 0) {
                    const ceiling = this.toMinutes(this.latest) ?? 23 * 60 + 59;
                    row.end = this.fromMinutes(Math.min(this.toMinutes(time) + length, ceiling));
                }
            }
            row.start = time;
            this.commit();
        },

        pickEnd(row, time) {
            row.end = time;
            this.commit();
        },

        open(row) {
            if (this.locked) return;
            if (this.editing === row.id) {
                this.close();
                return;
            }
            this.close();
            this.editing = row.id;
        },

        close() {
            if (this.editing === null) return;

            {{-- A slot added and closed without a time goes away again --}}
            const row = this.rows.find((item) => item.id === this.editing);
            if (row && row.id === this.fresh && ! row.start && ! row.end) {
                this.rows = this.rows.filter((item) => item.id !== row.id);
            }

            this.fresh = null;
            this.editing = null;
        },

        add() {
            if (this.locked) return;
            this.close();

            const row = this.makeRow(this.range ? {} : '');
            this.rows.push(row);
            this.fresh = row.id;
            this.editing = row.id;
        },

        remove(row) {
            if (this.locked) return;
            if (this.editing === row.id) {
                this.editing = null;
                this.fresh = null;
            }
            this.rows = this.rows.filter((item) => item.id !== row.id);
            this.commit();
        },
    }"
    x-modelable="value"
    {{ $attributes->whereDoesntStartWith(['class'])->merge(['class' => 'w-full']) }}
    x-on:click.outside="close()"
    x-on:keydown.escape="close()"
    data-slot="time-picker"
>
    @if ($name)
        @if ($multiple)
            <template x-for="(row, index) in rows" :key="row.id">
                <div hidden>
                    @if ($range)
                        <input type="hidden" x-bind:name="@js($name) + '[' + index + '][start]'" x-bind:value="row.start" />
                        <input type="hidden" x-bind:name="@js($name) + '[' + index + '][end]'" x-bind:value="row.end" />
                    @else
                        <input type="hidden" name="{{ $name }}[]" x-bind:value="row.start" />
                    @endif
                </div>
            </template>
        @elseif ($range)
            <input type="hidden" name="{{ $name }}[start]" x-bind:value="rows[0]?.start ?? ''" />
            <input type="hidden" name="{{ $name }}[end]" x-bind:value="rows[0]?.end ?? ''" />
        @else
            <input type="hidden" name="{{ $name }}" x-bind:value="rows[0]?.start ?? ''" />
        @endif
    @endif

    <div class="flex flex-col gap-2">
        <template x-for="row in rows" :key="row.id">
            <div class="relative flex items-center gap-2" data-slot="time-picker-row">
                <div class="min-w-0 flex-1">
                    <neura::input
                        readonly
                        x-bind:value="describe(row)"
                        x-on:click="open(row)"
                        x-on:keydown.enter.prevent="open(row)"
                        x-on:keydown.down.prevent="open(row)"
                        :placeholder="$placeholder"
                        :disabled="$disabled"
                        :invalid="$invalid"
                        :size="$sizes['field']"
                        :rounded="$rounded"
                        :shadow="$shadow"
                        right-icon="clock"
                        class="cursor-pointer"
                    />
                </div>

                {{-- Right after the field, so the open motion grows out of it --}}
                <div
                    x-show="editing === row.id"
                    x-transition:enter="nk-menu-motion"
                    x-transition:enter-start="nk-menu-from"
                    x-transition:enter-end="nk-menu-to"
                    x-transition:leave="nk-menu-leaving"
                    x-transition:leave-start="nk-menu-to"
                    x-transition:leave-end="nk-menu-out"
                    data-side="bottom"
                    data-align="start"
                    {{-- Anchored to the field: below it, or above when the window has no room left below --}}
                    x-anchor.bottom-start.offset.8="$el.previousElementSibling"
                    class="nk-menu-motion z-50 w-max shadow-xl rounded-box"
                    x-cloak
                >
                    <div @class([
                        'flex bg-surface-raised border border-edge p-3',
                        $roundedClass,
                        $shadowClass,
                    ])>
                        <div class="flex flex-col {{ $sizes['list'] }}">
                            <neura::time-picker.columns
                                x-effect="time = row.start; bounds = boundsFor(row, 'start'); disabled = locked"
                                x-on:time-selected.stop="pickStart(row, $event.detail.time)"
                                :label="$range ? $fromLabel : null"
                                :hour-cycle="$hourCycle"
                                :minute-step="$minuteStep"
                                :locale="$locale"
                                :size="$size"
                            />
                        </div>

                        @if ($range)
                            <div class="ms-3 flex flex-col border-s border-separator ps-3 {{ $sizes['list'] }}">
                                <neura::time-picker.columns
                                    x-effect="time = row.end; bounds = boundsFor(row, 'end'); disabled = locked"
                                    x-on:time-selected.stop="pickEnd(row, $event.detail.time)"
                                    :label="$toLabel"
                                    :hour-cycle="$hourCycle"
                                    :minute-step="$minuteStep"
                                    :locale="$locale"
                                    :size="$size"
                                />
                            </div>
                        @endif
                    </div>
                </div>

                @if ($multiple)
                    <neura::button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                        x-on:click="remove(row)"
                        :disabled="$disabled"
                        aria-label="{{ $removeLabel }}"
                    />
                @endif
            </div>
        </template>

        @if ($multiple)
            <div>
                <neura::button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="plus"
                    x-on:click="add()"
                    :disabled="$disabled"
                >
                    {{ $addLabel }}
                </neura::button>
            </div>
        @endif
    </div>
</div>

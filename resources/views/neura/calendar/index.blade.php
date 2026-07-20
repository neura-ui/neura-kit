@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'value' => null,
    'minDate' => null,
    'maxDate' => null,
    'disabled' => false,
    'disabledDates' => [],
    'disabledDaysOfWeek' => [],
    'locale' => 'en',
    'firstDayOfWeek' => 0,
    'multiple' => false,
    'range' => false,
    'months' => 1,
    'captionLayout' => 'label',
    'showOutsideDays' => true,
    'showToday' => false,
    'showReset' => false,
    'fromYear' => null,
    'toYear' => null,
    'presets' => [],
    'dayLabels' => [],
    'events' => [],
])

@php
    use Neura\Kit\Support\PackResolver;

    $disabled = filled($disabled) && $disabled;
    $multiple = filled($multiple) && $multiple;
    $range = filled($range) && $range;
    $showOutsideDays = filled($showOutsideDays) && $showOutsideDays;
    $showToday = filled($showToday) && $showToday;
    $showReset = filled($showReset) && $showReset;

    if ($range) {
        $value = $value ?? ['start' => null, 'end' => null];
    } elseif ($multiple) {
        $value = $value ?? [];
    } else {
        $value = $value ?? '';
    }

    $monthsCount = max(1, min(4, (int) $months));
    $captionLayout = in_array($captionLayout, ['dropdown', 'dropdowns'], true) && $monthsCount === 1
        ? 'dropdown'
        : 'label';

    $hasPresets = filled($presets);
    $hasDayLabels = filled($dayLabels);
    $hasEvents = filled($events);
    $cellSizeClass = $hasDayLabels ? 'h-12 w-12 flex-col gap-0.5' : 'size-9';
    $cellWidthClass = $hasDayLabels ? 'w-12' : 'w-9';

    $calendarRoundedClass = PackResolver::rounded(neura_config('popup', 'rounded'));
    $calendarShadowClass = PackResolver::shadow(neura_config('popup', 'shadow'));

    $selectedColor = PackResolver::buttonColor('primary', 'dark');
    $daySelectedClasses = trim(
        str_replace(' shadow-sm', '', $selectedColor['base'] ?? 'bg-primary-900 text-white dark:bg-primary-100 dark:text-primary-900')
        . ' ' . ($selectedColor['hover'] ?? 'hover:bg-primary-800 dark:hover:bg-primary-200')
    );
@endphp

<div
    x-data="{
        selectedDate: {{ json_encode($value) }},
        isMultiple: {{ json_encode($multiple) }},
        isRange: {{ json_encode($range) }},
        isDisabled: {{ json_encode($disabled) }},
        minDate: {{ $minDate ? json_encode($minDate) : 'null' }},
        maxDate: {{ $maxDate ? json_encode($maxDate) : 'null' }},
        disabledDates: {{ json_encode($disabledDates) }},
        disabledDaysOfWeek: {{ json_encode(array_map('intval', (array) $disabledDaysOfWeek)) }},
        locale: {{ json_encode($locale) }},
        firstDayOfWeek: {{ json_encode((int) $firstDayOfWeek) }},
        monthsCount: {{ json_encode($monthsCount) }},
        showOutsideDays: {{ json_encode($showOutsideDays) }},
        selectedClasses: {{ json_encode($daySelectedClasses) }},
        presets: {{ json_encode((object) $presets) }},
        dayLabels: {{ json_encode((object) $dayLabels) }},
        events: {{ json_encode((object) $events) }},
        currentMonth: null,
        currentYear: null,
        hoveredDate: null,
        focusableDate: null,
        monthGrids: [],
        weekDays: [],
        monthNames: [],
        yearOptions: [],
        ready: false,

        init() {
            let anchor = this.parseDate(this.anchorDate()) ?? new Date();

            {{-- Without a selection, keep the initial view inside the allowed bounds --}}
            if (! this.anchorDate()) {
                const min = this.parseDate(this.minDate);
                const max = this.parseDate(this.maxDate);
                if (min && anchor < min) anchor = min;
                if (max && anchor > max) anchor = max;
            }

            this.currentMonth = anchor.getMonth();
            this.currentYear = anchor.getFullYear();

            this.initWeekDays();
            this.initMonthNames();
            this.initYearOptions();
            this.buildMonths();
            this.ready = true;

            this.$watch('currentMonth', () => this.buildMonths());
            this.$watch('currentYear', () => this.buildMonths());

            this.$watch('selectedDate', (value) => {
                this.buildMonths();
                this.$root?._x_model?.set(value);

                let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
                if (this.$wire && wireModel) {
                    let prop = this.$root.getAttribute(wireModel);
                    this.$wire.set(prop, value, wireModel?.includes('.live'));
                }
            });
        },

        syncFromModel() {
            const model = this.$root?._x_model;
            if (!model) return;

            let value;
            try { value = model.get(); } catch (e) { return; }
            if (value === undefined) return;

            if (this.isRange) value = value ?? { start: null, end: null };
            else if (this.isMultiple) value = value ?? [];
            else value = value ?? '';

            if (JSON.stringify(value) === JSON.stringify(this.selectedDate)) return;

            this.selectedDate = value;
            const focus = this.parseDate(this.anchorDate());
            if (focus) this.showMonth(focus.getFullYear(), focus.getMonth());
        },

        anchorDate() {
            if (this.isRange) return this.selectedDate?.start ?? null;
            if (this.isMultiple) return this.selectedDate?.[0] ?? null;
            return this.selectedDate || null;
        },

        initWeekDays() {
            this.weekDays = [];
            for (let i = 0; i < 7; i++) {
                {{-- 2023-01-01 is a Sunday; offset by firstDayOfWeek to localize headers --}}
                const day = new Date(2023, 0, 1 + ((this.firstDayOfWeek + i) % 7));
                this.weekDays.push(day.toLocaleDateString(this.locale, { weekday: 'short' }));
            }
        },

        initMonthNames() {
            this.monthNames = [];
            for (let m = 0; m < 12; m++) {
                this.monthNames.push(new Date(2023, m, 1).toLocaleDateString(this.locale, { month: 'short' }));
            }
        },

        initYearOptions() {
            const today = new Date();
            const from = {{ $fromYear ? json_encode((int) $fromYear) : 'null' }}
                ?? (this.minDate ? this.parseDate(this.minDate)?.getFullYear() : null)
                ?? today.getFullYear() - 100;
            const to = {{ $toYear ? json_encode((int) $toYear) : 'null' }}
                ?? (this.maxDate ? this.parseDate(this.maxDate)?.getFullYear() : null)
                ?? today.getFullYear() + 10;

            this.yearOptions = [];
            for (let y = from; y <= to; y++) this.yearOptions.push(y);
        },

        buildMonths() {
            this.monthGrids = [];
            for (let i = 0; i < this.monthsCount; i++) {
                const base = new Date(this.currentYear, this.currentMonth + i, 1);
                this.monthGrids.push(this.buildMonth(base.getFullYear(), base.getMonth()));
            }
            this.focusableDate = this.computeFocusableDate();
        },

        buildMonth(year, month) {
            const firstDay = new Date(year, month, 1);
            const startOffset = (firstDay.getDay() - this.firstDayOfWeek + 7) % 7;
            const cursor = new Date(year, month, 1 - startOffset);

            const weeks = [];
            for (let w = 0; w < 6; w++) {
                const week = [];
                for (let i = 0; i < 7; i++) {
                    week.push(this.buildDay(cursor, month));
                    cursor.setDate(cursor.getDate() + 1);
                }
                weeks.push(week);
            }

            return {
                month,
                year,
                label: firstDay.toLocaleDateString(this.locale, { month: 'long', year: 'numeric' }),
                weeks,
            };
        },

        buildDay(date, displayMonth) {
            const year = date.getFullYear();
            const month = date.getMonth();
            const day = date.getDate();
            const dateStr = this.formatDate(year, month, day);

            let isSelected = false;
            let isRangeStart = false;
            let isRangeEnd = false;

            if (this.isRange) {
                isRangeStart = !! this.selectedDate.start && dateStr === this.selectedDate.start;
                isRangeEnd = !! this.selectedDate.end && dateStr === this.selectedDate.end;
                isSelected = isRangeStart || isRangeEnd;
            } else if (this.isMultiple) {
                isSelected = this.selectedDate.includes(dateStr);
            } else {
                isSelected = this.selectedDate === dateStr;
            }

            return {
                date: day,
                month,
                year,
                dateStr,
                inMonth: month === displayMonth,
                isToday: this.isToday(year, month, day),
                isSelected,
                isRangeStart,
                isRangeEnd,
                isDisabled: this.isDateDisabled(dateStr, date.getDay()),
                label: date.toLocaleDateString(this.locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
            };
        },

        computeFocusableDate() {
            const visible = [];
            this.monthGrids.forEach(grid => grid.weeks.forEach(week => week.forEach(day => {
                if (day.inMonth) visible.push(day);
            })));

            return (visible.find(d => d.isSelected)
                ?? visible.find(d => d.isToday)
                ?? visible.find(d => ! d.isDisabled)
                ?? visible[0])?.dateStr ?? null;
        },

        bandFor(day) {
            if (! this.isRange || ! day.inMonth) return null;

            const start = this.selectedDate.start;
            const end = this.selectedDate.end;

            if (start && end && start !== end) {
                if (day.dateStr === start) return 'start';
                if (day.dateStr === end) return 'end';
                if (day.dateStr > start && day.dateStr < end) return 'middle';
                return null;
            }

            if (start && ! end && this.hoveredDate && this.hoveredDate !== start) {
                const [a, b] = this.hoveredDate < start ? [this.hoveredDate, start] : [start, this.hoveredDate];
                if (day.dateStr === a) return 'start';
                if (day.dateStr === b) return 'end';
                if (day.dateStr > a && day.dateStr < b) return 'middle';
            }

            return null;
        },

        cellClasses(day, index) {
            const band = this.bandFor(day);
            if (! band) return '';

            {{-- Range endpoints get a half-width band behind the pill so the
                 track visually connects while the pill keeps its rounding. --}}
            if (band === 'start') return 'after:absolute after:inset-y-0 after:right-0 after:w-1/2 after:bg-hover';
            if (band === 'end') return 'after:absolute after:inset-y-0 after:left-0 after:w-1/2 after:bg-hover';

            const classes = ['bg-hover'];
            if (index % 7 === 0) classes.push('rounded-l-md');
            if (index % 7 === 6) classes.push('rounded-r-md');

            return classes.join(' ');
        },

        dayClasses(day) {
            if (! day.inMonth && ! this.showOutsideDays) return 'invisible pointer-events-none';

            const band = this.bandFor(day);
            const classes = [];

            if (day.isDisabled) {
                classes.push('rounded-md text-fg-disabled opacity-50');
            } else if (day.isSelected && day.inMonth) {
                classes.push('rounded-md', this.selectedClasses);
            } else if (band === 'middle') {
                classes.push('rounded-none text-fg hover:bg-active');
            } else if (band === 'start' || band === 'end') {
                classes.push('rounded-md text-fg hover:bg-active');
            } else if (! day.inMonth) {
                classes.push('rounded-md text-fg-muted hover:bg-hover');
            } else if (day.isToday) {
                classes.push('rounded-md bg-active text-fg font-medium');
            } else {
                classes.push('rounded-md text-fg hover:bg-hover');
            }

            if (day.isToday && day.isSelected) classes.push('font-medium');

            return classes.join(' ');
        },

        selectDate(day) {
            if (this.isDisabled || day.isDisabled) return;
            if (! day.inMonth && ! this.showOutsideDays) return;

            if (! day.inMonth) {
                this.showMonth(day.year, day.month);
            }

            const dateStr = day.dateStr;

            if (this.isRange) {
                if (! this.selectedDate.start || (this.selectedDate.start && this.selectedDate.end)) {
                    this.selectedDate = { start: dateStr, end: null };
                } else if (dateStr >= this.selectedDate.start) {
                    this.selectedDate = { start: this.selectedDate.start, end: dateStr };
                    this.hoveredDate = null;
                } else {
                    this.selectedDate = { start: dateStr, end: this.selectedDate.start };
                    this.hoveredDate = null;
                }
            } else if (this.isMultiple) {
                this.selectedDate = this.selectedDate.includes(dateStr)
                    ? this.selectedDate.filter(d => d !== dateStr)
                    : [...this.selectedDate, dateStr];
            } else {
                this.selectedDate = dateStr;
            }

            this.emitSelection();
        },

        emitSelection() {
            this.$dispatch('date-selected', {
                date: this.selectedDate,
                dates: this.isRange
                    ? [this.selectedDate.start, this.selectedDate.end].filter(Boolean)
                    : (this.isMultiple ? this.selectedDate : (this.selectedDate ? [this.selectedDate] : [])),
            });
        },

        onDayHover(day) {
            if (this.isRange && this.selectedDate.start && ! this.selectedDate.end) {
                this.hoveredDate = day.dateStr;
            }
        },

        hasEventsOn(dateStr) {
            const list = this.events[dateStr];
            return Array.isArray(list) ? list.length > 0 : !! list;
        },

        eventsFor(dateStr) {
            if (! dateStr) return [];
            const list = this.events[dateStr];
            return Array.isArray(list) ? list : [];
        },

        get selectedEvents() {
            const anchor = this.isRange
                ? this.selectedDate?.start
                : (this.isMultiple ? this.selectedDate?.[0] : this.selectedDate);
            return this.eventsFor(anchor);
        },

        normalizePreset(value) {
            if (this.isRange) {
                if (Array.isArray(value)) return { start: value[0] ?? null, end: value[1] ?? null };
                return { start: value?.start ?? null, end: value?.end ?? null };
            }
            if (this.isMultiple) return Array.isArray(value) ? value : [value];
            return value;
        },

        applyPreset(value) {
            if (this.isDisabled) return;

            const normalized = this.normalizePreset(value);
            this.selectedDate = normalized;
            this.hoveredDate = null;

            const anchorStr = this.isRange
                ? normalized.start
                : (this.isMultiple ? normalized[0] : normalized);
            const anchor = this.parseDate(anchorStr);
            if (anchor) this.showMonth(anchor.getFullYear(), anchor.getMonth());

            this.emitSelection();
        },

        isPresetActive(value) {
            return JSON.stringify(this.normalizePreset(value)) === JSON.stringify(this.selectedDate);
        },

        onKeydown(event) {
            const target = event.target.closest('[data-date]');
            if (! target) return;

            const date = this.parseDate(target.getAttribute('data-date'));
            if (! date) return;

            const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };

            if (event.key in moves) {
                date.setDate(date.getDate() + moves[event.key]);
            } else if (event.key === 'Home') {
                date.setDate(date.getDate() - ((date.getDay() - this.firstDayOfWeek + 7) % 7));
            } else if (event.key === 'End') {
                date.setDate(date.getDate() + (6 - ((date.getDay() - this.firstDayOfWeek + 7) % 7)));
            } else if (event.key === 'PageUp') {
                date.setMonth(date.getMonth() - 1);
            } else if (event.key === 'PageDown') {
                date.setMonth(date.getMonth() + 1);
            } else {
                return;
            }

            event.preventDefault();
            this.focusDate(this.formatDate(date.getFullYear(), date.getMonth(), date.getDate()));
        },

        focusDate(dateStr) {
            const find = () => this.$root.querySelector(`[data-date='${dateStr}']:not([data-outside])`);

            const el = find();
            if (el) {
                if (el.disabled) return;
                this.focusableDate = dateStr;
                el.focus();
                return;
            }

            const date = this.parseDate(dateStr);
            if (! date) return;

            this.showMonth(date.getFullYear(), date.getMonth());
            this.$nextTick(() => {
                this.focusableDate = dateStr;
                find()?.focus();
            });
        },

        showMonth(year, month) {
            const target = year * 12 + month;
            const first = this.currentYear * 12 + this.currentMonth;
            const last = first + this.monthsCount - 1;

            let base = null;
            if (target < first) base = target;
            else if (target > last) base = target - (this.monthsCount - 1);
            else return;

            this.currentYear = Math.floor(base / 12);
            this.currentMonth = base % 12;
        },

        get canGoPrevious() {
            if (! this.minDate) return true;
            const prevMonthEnd = new Date(this.currentYear, this.currentMonth, 0);
            return this.formatDate(prevMonthEnd.getFullYear(), prevMonthEnd.getMonth(), prevMonthEnd.getDate()) >= this.minDate;
        },

        get canGoNext() {
            if (! this.maxDate) return true;
            const nextMonthStart = new Date(this.currentYear, this.currentMonth + this.monthsCount, 1);
            return this.formatDate(nextMonthStart.getFullYear(), nextMonthStart.getMonth(), 1) <= this.maxDate;
        },

        previousMonth() {
            if (this.isDisabled || ! this.canGoPrevious) return;
            const base = new Date(this.currentYear, this.currentMonth - 1, 1);
            this.currentYear = base.getFullYear();
            this.currentMonth = base.getMonth();
        },

        nextMonth() {
            if (this.isDisabled || ! this.canGoNext) return;
            const base = new Date(this.currentYear, this.currentMonth + 1, 1);
            this.currentYear = base.getFullYear();
            this.currentMonth = base.getMonth();
        },

        isMonthOptionDisabled(month) {
            const start = this.formatDate(this.currentYear, month, 1);
            const end = this.formatDate(this.currentYear, month, new Date(this.currentYear, month + 1, 0).getDate());

            if (this.minDate && end < this.minDate) return true;
            if (this.maxDate && start > this.maxDate) return true;

            return false;
        },

        onYearSelected() {
            if (! this.isMonthOptionDisabled(this.currentMonth)) return;

            for (let m = 0; m < 12; m++) {
                if (! this.isMonthOptionDisabled(m)) {
                    this.currentMonth = m;
                    return;
                }
            }
        },

        isToday(year, month, date) {
            const today = new Date();
            return today.getFullYear() === year
                && today.getMonth() === month
                && today.getDate() === date;
        },

        isDateDisabled(dateStr, dayOfWeek) {
            if (this.disabledDates.includes(dateStr)) return true;
            if (this.disabledDaysOfWeek.includes(dayOfWeek)) return true;
            if (this.minDate && dateStr < this.minDate) return true;
            if (this.maxDate && dateStr > this.maxDate) return true;

            return false;
        },

        formatDate(year, month, date) {
            const m = String(month + 1).padStart(2, '0');
            const d = String(date).padStart(2, '0');
            return year + '-' + m + '-' + d;
        },

        parseDate(dateStr) {
            if (! dateStr || typeof dateStr !== 'string') return null;
            const [year, month, day] = dateStr.split('-').map(Number);
            if (! year || ! month || ! day) return null;
            const date = new Date(year, month - 1, day);
            return isNaN(date) ? null : date;
        },

        goToToday() {
            if (this.isDisabled) return;
            const today = new Date();
            const todayStr = this.formatDate(today.getFullYear(), today.getMonth(), today.getDate());

            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();

            if (this.isRange) return;

            if (this.isMultiple) {
                if (! this.selectedDate.includes(todayStr)) {
                    this.selectedDate = [...this.selectedDate, todayStr];
                }
            } else {
                this.selectedDate = todayStr;
            }

            this.emitSelection();
        },

        reset() {
            if (this.isDisabled) return;

            if (this.isRange) {
                this.selectedDate = { start: null, end: null };
            } else if (this.isMultiple) {
                this.selectedDate = [];
            } else {
                this.selectedDate = '';
            }

            this.hoveredDate = null;
            this.emitSelection();
        }
    }"
    x-effect="syncFromModel()"
    {{ $attributes->merge(['class' => 'select-none w-fit' . ($disabled ? ' opacity-50 cursor-not-allowed' : '')]) }}
>
    @if ($name)
        @if ($range)
            <input type="hidden" name="{{ $name }}[start]" x-bind:value="selectedDate.start ?? ''" />
            <input type="hidden" name="{{ $name }}[end]" x-bind:value="selectedDate.end ?? ''" />
        @elseif ($multiple)
            <template x-for="date in selectedDate" :key="date">
                <input type="hidden" name="{{ $name }}[]" x-bind:value="date" />
            </template>
        @else
            <input type="hidden" name="{{ $name }}" x-bind:value="selectedDate" />
        @endif
    @endif

    <div @class([
        'bg-surface-raised border border-edge p-3 w-fit',
        $calendarRoundedClass,
        $calendarShadowClass,
    ])>
        {{-- Skeleton: reserves the calendar frame before Alpine builds the grid --}}
        <div
            x-show="!ready"
            aria-hidden="true"
            class="pointer-events-none"
        >
            <div class="flex flex-col sm:flex-row">
                @if ($hasPresets)
                    <div class="mb-3 flex gap-1.5 overflow-hidden border-b border-separator pb-3 sm:mb-0 sm:me-3 sm:min-w-28 sm:flex-col sm:border-b-0 sm:border-e sm:pb-0 sm:pe-3">
                        @foreach (range(1, min(4, count((array) $presets) ?: 3)) as $presetSkeleton)
                            <div class="h-8 w-full animate-pulse rounded-md bg-surface-inset"></div>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-col">
                    <div class="relative flex flex-col gap-4 md:flex-row">
                        @foreach (range(1, $monthsCount) as $monthSkeleton)
                            <div class="flex w-fit flex-col gap-3">
                                <div class="flex h-8 items-center justify-center gap-2 px-9">
                                    <div class="h-5 w-20 animate-pulse rounded-md bg-surface-inset"></div>
                                    @if ($captionLayout === 'dropdown')
                                        <div class="h-5 w-12 animate-pulse rounded-md bg-surface-inset"></div>
                                    @endif
                                </div>

                                <div>
                                    <div class="flex">
                                        @foreach (range(1, 7) as $weekDaySkeleton)
                                            <div class="flex h-8 {{ $cellWidthClass }} items-center justify-center">
                                                <div class="h-3 w-5 animate-pulse rounded bg-surface-inset"></div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @foreach (range(1, 6) as $weekSkeleton)
                                        <div class="mt-1 flex">
                                            @foreach (range(1, 7) as $daySkeleton)
                                                <div class="flex {{ $cellWidthClass }} items-center justify-center p-0">
                                                    <div @class([
                                                        'animate-pulse rounded-md bg-surface-inset',
                                                        $hasDayLabels ? 'size-12' : 'size-9',
                                                    ])></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($showToday || $showReset)
                        <div class="mt-3 flex gap-2 border-t border-separator pt-3">
                            @if ($showToday)
                                <div class="h-8 flex-1 animate-pulse rounded-md bg-surface-inset"></div>
                            @endif
                            @if ($showReset)
                                <div class="h-8 flex-1 animate-pulse rounded-md bg-surface-inset"></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div
            x-show="ready"
            style="display:none"
        >
            <div class="flex flex-col sm:flex-row">
                @if ($hasPresets)
                    <div class="mb-3 flex gap-1 overflow-x-auto border-b border-separator pb-3 sm:mb-0 sm:me-3 sm:min-w-28 sm:flex-col sm:overflow-visible sm:border-b-0 sm:border-e sm:pb-0 sm:pe-3">
                        <template x-for="(presetValue, presetLabel) in presets" :key="presetLabel">
                            <button
                                type="button"
                                class="cursor-pointer rounded-md px-2.5 py-1.5 text-start text-sm whitespace-nowrap transition-colors duration-100 disabled:pointer-events-none disabled:opacity-50"
                                x-bind:class="isPresetActive(presetValue)
                                    ? 'bg-active text-fg font-medium'
                                    : 'text-fg-secondary hover:bg-hover hover:text-fg'"
                                x-bind:disabled="isDisabled"
                                x-on:click="applyPreset(presetValue)"
                                x-text="presetLabel"
                            ></button>
                        </template>
                    </div>
                @endif

                <div class="flex flex-col">
                    <div
                        class="relative flex flex-col gap-4 md:flex-row"
                        x-on:keydown="onKeydown"
                        x-on:mouseleave="hoveredDate = null"
                    >
                        <div class="absolute left-0 top-0 z-10">
                            <neura::button
                                variant="ghost"
                                size="sm"
                                icon="chevron-left"
                                aria-label="Previous month"
                                x-on:click="previousMonth"
                                x-bind:disabled="isDisabled || !canGoPrevious"
                            />
                        </div>

                        <div class="absolute right-0 top-0 z-10">
                            <neura::button
                                variant="ghost"
                                size="sm"
                                icon="chevron-right"
                                aria-label="Next month"
                                x-on:click="nextMonth"
                                x-bind:disabled="isDisabled || !canGoNext"
                            />
                        </div>

                        <template x-for="grid in monthGrids" :key="grid.year + '-' + grid.month">
                            <div class="flex w-fit flex-col gap-3">
                                @if ($captionLayout === 'dropdown')
                                    <div class="flex h-8 items-center justify-center gap-1.5 px-9">
                                        <div class="relative rounded-md transition-colors hover:bg-hover">
                                            <div class="flex items-center gap-1 px-2 py-1 text-sm font-medium text-fg">
                                                <span x-text="monthNames[currentMonth]"></span>
                                                <neura::icon name="chevron-down" variant="micro" class="size-3.5 text-fg-muted" />
                                            </div>
                                            <select
                                                class="absolute inset-0 cursor-pointer opacity-0"
                                                aria-label="Month"
                                                x-model.number="currentMonth"
                                                x-bind:disabled="isDisabled"
                                            >
                                                <template x-for="(monthName, index) in monthNames" :key="index">
                                                    <option
                                                        x-bind:value="index"
                                                        x-bind:selected="index === currentMonth"
                                                        x-bind:disabled="isMonthOptionDisabled(index)"
                                                        x-text="monthName"
                                                    ></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="relative rounded-md transition-colors hover:bg-hover">
                                            <div class="flex items-center gap-1 px-2 py-1 text-sm font-medium text-fg">
                                                <span x-text="currentYear"></span>
                                                <neura::icon name="chevron-down" variant="micro" class="size-3.5 text-fg-muted" />
                                            </div>
                                            <select
                                                class="absolute inset-0 cursor-pointer opacity-0"
                                                aria-label="Year"
                                                x-model.number="currentYear"
                                                x-on:change="onYearSelected"
                                                x-bind:disabled="isDisabled"
                                            >
                                                <template x-for="year in yearOptions" :key="year">
                                                    <option
                                                        x-bind:value="year"
                                                        x-bind:selected="year === currentYear"
                                                        x-text="year"
                                                    ></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                @else
                                    <div
                                        class="flex h-8 items-center justify-center px-9 text-sm font-medium text-fg"
                                        aria-live="polite"
                                        x-text="grid.label"
                                    ></div>
                                @endif

                                <div role="grid">
                                    <div class="flex" role="row">
                                        <template x-for="(weekDay, weekDayIndex) in weekDays" :key="weekDayIndex">
                                            <div
                                                class="flex h-8 {{ $cellWidthClass }} items-center justify-center text-[0.8rem] font-normal text-fg-muted"
                                                role="columnheader"
                                                x-text="weekDay"
                                            ></div>
                                        </template>
                                    </div>

                                    <template x-for="(week, weekIndex) in grid.weeks" :key="weekIndex">
                                        <div class="mt-1 flex" role="row">
                                            <template x-for="(day, dayIndex) in week" :key="day.dateStr">
                                                <div
                                                    class="relative p-0 text-center"
                                                    role="gridcell"
                                                    x-bind:aria-selected="day.isSelected && day.inMonth ? 'true' : 'false'"
                                                    x-bind:class="cellClasses(day, dayIndex)"
                                                >
                                                    <button
                                                        type="button"
                                                        class="relative z-10 inline-flex {{ $cellSizeClass }} cursor-pointer items-center justify-center text-sm font-normal transition-colors duration-100 select-none focus-visible:relative focus-visible:z-20 focus-visible:ring-2 focus-visible:ring-edge-focus focus-visible:outline-none disabled:pointer-events-none"
                                                        x-bind:class="dayClasses(day)"
                                                        x-bind:disabled="isDisabled || day.isDisabled"
                                                        x-bind:tabindex="day.dateStr === focusableDate && day.inMonth ? 0 : -1"
                                                        x-bind:data-date="day.dateStr"
                                                        x-bind:data-outside="!day.inMonth ? '' : false"
                                                        x-bind:aria-label="day.label"
                                                        x-bind:aria-current="day.isToday ? 'date' : false"
                                                        x-on:click="selectDate(day)"
                                                        x-on:mouseenter="onDayHover(day)"
                                                    >
                                                        <span x-text="day.date"></span>

                                                        @if ($hasDayLabels)
                                                            <span
                                                                class="text-[10px] leading-none font-normal opacity-70"
                                                                x-text="dayLabels[day.dateStr] ?? ''"
                                                            ></span>
                                                        @endif

                                                        @if ($hasEvents)
                                                            <span
                                                                class="absolute bottom-1 left-1/2 size-1 -translate-x-1/2 rounded-full"
                                                                x-show="hasEventsOn(day.dateStr) && day.inMonth"
                                                                x-bind:class="day.isSelected && day.inMonth ? 'bg-current' : 'bg-primary-600 dark:bg-primary-400'"
                                                            ></span>
                                                        @endif
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    @isset($footer)
                        <div class="mt-3 border-t border-separator pt-3">
                            {{ $footer }}
                        </div>
                    @endisset

                    @if ($showToday || $showReset)
                        <div class="mt-3 flex gap-2 border-t border-separator pt-3">
                            @if ($showToday)
                                <neura::button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    x-on:click="goToToday"
                                    x-bind:disabled="isDisabled"
                                >
                                    {{ __('today') }}
                                </neura::button>
                            @endif

                            @if ($showReset)
                                <neura::button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1"
                                    x-on:click="reset"
                                    x-bind:disabled="isDisabled"
                                >
                                    {{ __('reset') }}
                                </neura::button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

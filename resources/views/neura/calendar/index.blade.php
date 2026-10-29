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

    // Normalize date-only strings (strip time / timezone from Carbon ISO datetimes)
    $toDateStr = static function ($v) {
        if (! is_string($v) || $v === '') {
            return $v;
        }
        return preg_match('/^(\d{4}-\d{2}-\d{2})/', $v, $m) ? $m[1] : $v;
    };
    if ($range && is_array($value)) {
        $value = [
            'start' => $toDateStr($value['start'] ?? null),
            'end' => $toDateStr($value['end'] ?? null),
        ];
    } elseif ($multiple && is_array($value)) {
        $value = array_values(array_filter(array_map($toDateStr, $value)));
    } elseif (is_string($value)) {
        $value = $toDateStr($value) ?? '';
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

    [$daySelectedBgClasses, $daySelectedTextClasses] = PackResolver::selectionClasses();
@endphp

<div
    x-data="{
        selectedDate: {{ json_encode($value) }},
        isMultiple: {{ json_encode($multiple) }},
        isRange: {{ json_encode($range) }},
        isDisabled: {{ json_encode($disabled) }},
        {{-- Days only: compared as YYYY-MM-DD strings, a min of 2026-10-27T09:30 would disable the 27th itself --}}
        minDate: {{ $minDate ? json_encode($toDateStr($minDate)) : 'null' }},
        maxDate: {{ $maxDate ? json_encode($toDateStr($maxDate)) : 'null' }},
        disabledDates: {{ json_encode($disabledDates) }},
        disabledDaysOfWeek: {{ json_encode(array_map('intval', (array) $disabledDaysOfWeek)) }},
        locale: {{ json_encode($locale) }},
        firstDayOfWeek: {{ json_encode((int) $firstDayOfWeek) }},
        monthsCount: {{ json_encode($monthsCount) }},
        showOutsideDays: {{ json_encode($showOutsideDays) }},
        selectedTextClasses: {{ json_encode($daySelectedTextClasses) }},
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
        lastEmitted: null,

        init() {
            this.selectedDate = this.normalizeValue(this.selectedDate);

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

            this.$watch('currentMonth', () => {
                this.buildMonths();
                this.$nextTick(() => this.syncSelection(false));
            });
            this.$watch('currentYear', () => {
                this.buildMonths();
                this.$nextTick(() => this.syncSelection(false));
            });

            // Keep grid in sync; x-modelable handles wire:model / x-model (avoid $wire.set).
            const release = () => {
                this.$root.querySelectorAll('[data-cal-highlight]').forEach((node) => node.removeAttribute('data-pressed'));
            };
            window.addEventListener('pointerup', release);
            window.addEventListener('pointercancel', release);

            this.$watch('selectedDate', (value) => {
                const normalized = this.normalizeValue(value);
                if (JSON.stringify(normalized) !== JSON.stringify(value)) {
                    this.selectedDate = normalized;
                    return;
                }

                {{--
                    A value set from outside after init (wire:model, x-model) shows its month.
                    The calendar's own picks are left alone: a range end chosen two months on
                    must not jump back to the start.
                --}}
                if (JSON.stringify(value) !== this.lastEmitted) {
                    const anchor = this.parseDate(this.anchorDate());
                    if (anchor) this.showMonth(anchor.getFullYear(), anchor.getMonth());
                }

                this.buildMonths();
                this.$nextTick(() => this.syncSelection(true));
            });

            this.$nextTick(() => this.syncSelection(false));

            if (typeof ResizeObserver !== 'undefined') {
                let frame = 0;
                const observer = new ResizeObserver(() => {
                    cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(() => this.syncSelection(false));
                });
                observer.observe(this.$root);
            }
        },

        toDateStr(value) {
            if (! value || typeof value !== 'string') return value;
            const m = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
            return m ? (m[1] + '-' + m[2] + '-' + m[3]) : value;
        },

        normalizeValue(value) {
            if (this.isRange) {
                if (! value || typeof value !== 'object') return { start: null, end: null };
                return {
                    start: value.start ? this.toDateStr(value.start) : null,
                    end: value.end ? this.toDateStr(value.end) : null,
                };
            }
            if (this.isMultiple) {
                return (Array.isArray(value) ? value : []).map(v => this.toDateStr(v)).filter(Boolean);
            }
            return value ? this.toDateStr(value) : '';
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
                classes.push('rounded-md', this.selectedTextClasses);
            } else if (band === 'middle') {
                classes.push('rounded-none text-fg');
            } else if (band === 'start' || band === 'end') {
                classes.push('rounded-md text-fg');
            } else if (! day.inMonth) {
                classes.push('rounded-md text-fg-muted');
            } else if (day.isToday) {
                classes.push('rounded-md bg-active text-fg font-medium');
            } else {
                classes.push('rounded-md text-fg');
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
            this.lastEmitted = JSON.stringify(this.selectedDate);
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

        dayMotionReduced() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },

        dayBox(days, button) {
            const outer = days.getBoundingClientRect();
            const inner = button.getBoundingClientRect();

            {{--
                Layout pixels, not screen pixels: the popup scales while it opens, and a box
                measured mid-scale left the highlight small and off its day for good.
            --}}
            const scaleX = days.offsetWidth ? outer.width / days.offsetWidth : 1;
            const scaleY = days.offsetHeight ? outer.height / days.offsetHeight : 1;

            return {
                x: (inner.left - outer.left) / scaleX + days.scrollLeft,
                y: (inner.top - outer.top) / scaleY + days.scrollTop,
                w: inner.width / scaleX,
                h: inner.height / scaleY,
            };
        },

        dayAtPoint(days, x, y) {
            const buttons = days.querySelectorAll('button[data-date]');
            let best = null;
            let bestDist = Infinity;

            buttons.forEach((button) => {
                if (button.disabled || button.classList.contains('invisible')) return;
                const rect = button.getBoundingClientRect();
                const cx = Math.min(Math.max(x, rect.left), rect.right);
                const cy = Math.min(Math.max(y, rect.top), rect.bottom);
                const dist = ((x - cx) ** 2) + ((y - cy) ** 2);
                if (dist < bestDist) {
                    bestDist = dist;
                    best = button;
                }
            });

            return best;
        },

        moveDayHighlight(days, highlight, button) {
            if (highlight.dataset.shown === '1' && highlight.dataset.key === button.dataset.date) return;
            if (highlight.dataset.key !== button.dataset.date) highlight.removeAttribute('data-pressed');
            highlight.dataset.key = button.dataset.date || '';
            const box = this.dayBox(days, button);
            highlight.dataset.box = `${box.x}|${box.y}|${box.w}|${box.h}`;
            const shown = highlight.dataset.shown === '1';
            if (! shown || this.dayMotionReduced()) this.jumpDayHighlight(highlight, box, ! shown);
            else this.glideDayHighlight(highlight, box);
            highlight.dataset.shown = '1';
        },

        jumpDayHighlight(highlight, box, fadeIn) {
            highlight._nkAnim?.cancel();
            highlight.style.transform = `translate(${box.x}px, ${box.y}px)`;
            highlight.style.width = `${box.w}px`;
            highlight.style.height = `${box.h}px`;
            if (! fadeIn || this.dayMotionReduced()) {
                highlight.style.opacity = '1';
                return;
            }
            const from = getComputedStyle(highlight).opacity;
            highlight._nkAnim = highlight.animate(
                [{ opacity: from }, { opacity: 1 }],
                { duration: 160, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' },
            );
            highlight._nkAnim.onfinish = () => {
                highlight.style.opacity = '1';
                highlight._nkAnim?.cancel();
            };
        },

        glideDayHighlight(highlight, box) {
            const from = getComputedStyle(highlight);
            const fromTransform = from.transform === 'none' ? 'translate(0px, 0px)' : from.transform;
            const fromWidth = from.width;
            const fromHeight = from.height;
            highlight._nkAnim?.cancel();
            const next = {
                transform: `translate(${box.x}px, ${box.y}px)`,
                width: `${box.w}px`,
                height: `${box.h}px`,
                opacity: '1',
            };
            highlight._nkAnim = highlight.animate(
                [{ transform: fromTransform, width: fromWidth, height: fromHeight, opacity: 1 }, next],
                { duration: 260, easing: 'linear(0, 0.18 12%, 0.42 24%, 0.64 38%, 0.82 52%, 0.94 66%, 1.02 78%, 1.035 88%, 1.01 94%, 1)', fill: 'forwards' },
            );
            highlight._nkAnim.onfinish = () => {
                highlight.style.transform = next.transform;
                highlight.style.width = next.width;
                highlight.style.height = next.height;
                highlight.style.opacity = '1';
                highlight._nkAnim?.cancel();
            };
        },

        selectionEntries() {
            if (this.isRange) {
                const entries = [];
                if (this.selectedDate?.start) entries.push({ key: 'start', date: this.selectedDate.start });
                if (this.selectedDate?.end && this.selectedDate.end !== this.selectedDate.start) {
                    entries.push({ key: 'end', date: this.selectedDate.end });
                }
                return entries;
            }

            if (this.isMultiple) {
                return (Array.isArray(this.selectedDate) ? this.selectedDate : []).map((date) => ({ key: date, date }));
            }

            return this.selectedDate ? [{ key: 'value', date: this.selectedDate }] : [];
        },

        ensureSelectionDisc(days, key) {
            const existing = [...days.querySelectorAll('[data-cal-selected]')].find((node) => node.dataset.calSelected === key);
            if (existing) return existing;

            const proto = days.querySelector('[data-cal-proto]');
            if (! proto) return null;
            const disc = proto.cloneNode(false);
            disc.removeAttribute('data-cal-proto');
            disc.dataset.calSelected = key;
            days.appendChild(disc);

            return disc;
        },

        syncSelection(animate) {
            const entries = this.selectionEntries();
            const wanted = new Set(entries.map((entry) => entry.key));

            this.$root.querySelectorAll('.nk-cal-days').forEach((days) => {
                if (days.getBoundingClientRect().width === 0) return;

                days.querySelectorAll('[data-cal-selected]').forEach((disc) => {
                    if (! wanted.has(disc.dataset.calSelected)) this.hideDayHighlight(disc);
                });

                entries.forEach((entry) => {
                    const button = [...days.querySelectorAll('button[data-date]')].find((node) => node.dataset.date === entry.date && ! node.hasAttribute('data-outside'));
                    const disc = this.ensureSelectionDisc(days, entry.key);
                    if (! disc) return;
                    if (! button) {
                        this.hideDayHighlight(disc);
                        return;
                    }
                    if (! animate) {
                        if (disc._nkAnim && disc._nkAnim.playState === 'running') return;
                        const box = this.dayBox(days, button);
                        const signature = `${box.x}|${box.y}|${box.w}|${box.h}`;
                        if (disc.dataset.shown === '1' && disc.dataset.key === entry.date && disc.dataset.box === signature) return;
                        this.jumpDayHighlight(disc, box, disc.dataset.shown !== '1');
                        disc.dataset.shown = '1';
                        disc.dataset.key = entry.date;
                        disc.dataset.box = signature;
                        return;
                    }
                    this.moveDayHighlight(days, disc, button);
                });
            });
        },

        hideDayHighlight(highlight) {
            if (! highlight || highlight.dataset.shown !== '1') return;
            highlight.dataset.shown = '';
            highlight.removeAttribute('data-pressed');
            highlight._nkAnim?.cancel();
            if (this.dayMotionReduced()) {
                highlight.style.opacity = '0';
                return;
            }
            const from = getComputedStyle(highlight).opacity;
            highlight._nkAnim = highlight.animate(
                [{ opacity: from }, { opacity: 0 }],
                { duration: 160, easing: 'cubic-bezier(0.22, 1, 0.36, 1)', fill: 'forwards' },
            );
            highlight._nkAnim.onfinish = () => {
                highlight.style.opacity = '0';
                highlight._nkAnim?.cancel();
            };
        },

        onDayPointer(event) {
            if (event.pointerType === 'touch') return;
            const days = event.currentTarget;
            const highlight = days.querySelector('[data-cal-highlight]');
            if (! highlight) return;
            const button = this.dayAtPoint(days, event.clientX, event.clientY);
            if (! button) return;
            this.moveDayHighlight(days, highlight, button);
        },

        onDayPointerLeave(event) {
            if (event.currentTarget.contains(document.activeElement)) return;
            this.hideDayHighlight(event.currentTarget.querySelector('[data-cal-highlight]'));
        },

        onDayPointerDown(event) {
            if (event.button !== 0) return;
            const days = event.currentTarget;
            const highlight = days.querySelector('[data-cal-highlight]');
            const button = event.target.closest('[data-date]');
            if (! highlight || ! button || button.disabled || ! days.contains(button)) return;
            this.moveDayHighlight(days, highlight, button);
            highlight.dataset.pressed = '';
        },

        onDayFocus(event) {
            const button = event.target.closest('[data-date]');
            const days = event.currentTarget;
            const highlight = days.querySelector('[data-cal-highlight]');
            if (! button || button.disabled || ! highlight || ! days.contains(button)) return;
            this.moveDayHighlight(days, highlight, button);
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
            const m = dateStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (! m) return null;
            const date = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
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
    x-modelable="selectedDate"
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

    {{--
        An aside (such as the date picker's time column) sits beside the months, below them on
        narrow screens. A grid, not a flex row: a stretched grid item has a definite height, so
        the aside can fill the months' height without adding its own.
    --}}
    <div @class([
        'bg-surface-raised border border-edge p-3 w-fit',
        'grid gap-3 sm:grid-cols-[auto_auto]' => isset($aside),
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
                                class="cursor-pointer rounded-md px-2.5 py-1.5 text-start text-sm whitespace-nowrap transition-colors duration-fast ease-standard disabled:pointer-events-none disabled:opacity-50"
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

                                    <div
                                        class="nk-cal-days"
                                        x-on:pointermove="onDayPointer($event)"
                                        x-on:pointerleave="onDayPointerLeave($event)"
                                        x-on:pointerdown="onDayPointerDown($event)"
                                        x-on:focusin="onDayFocus($event)"
                                    >
                                    <span class="nk-cal-highlight" data-cal-highlight aria-hidden="true"></span>
                                    <span class="nk-cal-selected {{ $daySelectedBgClasses }}" data-cal-proto aria-hidden="true"></span>
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
                                                        class="relative z-10 inline-flex {{ $cellSizeClass }} cursor-pointer items-center justify-center text-sm font-normal transition-colors duration-fast ease-standard select-none focus-visible:relative focus-visible:z-20 focus-visible:ring-2 focus-visible:ring-edge-focus focus-visible:outline-none disabled:pointer-events-none"
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

        @isset($aside)
            <div {{ $aside->attributes->class('flex min-h-0 flex-col border-t border-separator pt-3 sm:border-t-0 sm:border-s sm:pt-0 sm:ps-3') }} data-slot="calendar-aside">
                {{ $aside }}
            </div>
        @endisset
    </div>
</div>

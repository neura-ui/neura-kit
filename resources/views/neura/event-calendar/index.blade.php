@props([
    'events' => [],
    'view' => 'month',
    'views' => ['month', 'week', 'day', 'agenda'],
    'date' => null,
    'locale' => 'en',
    'firstDayOfWeek' => 0,
    'showOutsideDays' => true,
    'hourStart' => 0,
    'hourEnd' => 24,
    'scrollTo' => 7,
    'showMiniCalendar' => false,
    'height' => '40rem',
    'rounded' => null,
    'shadow' => null,
    'size' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $showOutsideDays = filled($showOutsideDays) && $showOutsideDays;
    $showMiniCalendar = filled($showMiniCalendar) && $showMiniCalendar;

    $allowedViews = ['month', 'week', 'day', 'agenda'];
    $views = array_values(array_intersect($allowedViews, (array) $views)) ?: ['month'];
    $view = in_array($view, $views, true) ? $view : $views[0];

    $hourStart = max(0, min(23, (int) $hourStart));
    $hourEnd = max($hourStart + 1, min(24, (int) $hourEnd));
    $scrollTo = max($hourStart, min($hourEnd - 1, (int) $scrollTo));

    $initialKey = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : now()->toDateString();

    $rounded = PackResolver::rounded($rounded ?? neura_config('event-calendar', 'rounded'));
    $shadow = PackResolver::shadow($shadow ?? neura_config('event-calendar', 'shadow'));
    $s = PackResolver::eventCalendarSize($size ?? neura_config('event-calendar', 'size'));

    $viewLabels = ['month' => __('Month'), 'week' => __('Week'), 'day' => __('Day'), 'agenda' => __('Agenda')];
    $viewIcons = ['month' => 'squares-2x2', 'week' => 'view-columns', 'day' => 'bars-3', 'agenda' => 'list-bullet'];
@endphp

<div x-data="{
    events: {{ \Illuminate\Support\Js::from($events) }},
    view: {{ \Illuminate\Support\Js::from($view) }},
    availableViews: {{ \Illuminate\Support\Js::from($views) }},
    focusedKey: {{ \Illuminate\Support\Js::from($initialKey) }},
    locale: {{ \Illuminate\Support\Js::from($locale) }},
    firstDayOfWeek: {{ (int) $firstDayOfWeek }},
    showOutsideDays: {{ $showOutsideDays ? 'true' : 'false' }},
    hourStart: {{ $hourStart }},
    hourEnd: {{ $hourEnd }},
    scrollToHour: {{ $scrollTo }},
    hourHeight: {{ (int) $s['hourHeight'] }},
    now: new Date(),
    moreDay: null,
    ready: false,

    colorMap: {
        blue: { dot: 'bg-blue-500', allDay: 'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-500/90 dark:hover:bg-blue-500', block: 'bg-blue-500/12 text-blue-800 border-blue-500 hover:bg-blue-500/20 dark:bg-blue-500/22 dark:text-blue-100' },
        emerald: { dot: 'bg-emerald-500', allDay: 'bg-emerald-500 text-white hover:bg-emerald-600 dark:bg-emerald-500/90 dark:hover:bg-emerald-500', block: 'bg-emerald-500/12 text-emerald-800 border-emerald-500 hover:bg-emerald-500/20 dark:bg-emerald-500/22 dark:text-emerald-100' },
        violet: { dot: 'bg-violet-500', allDay: 'bg-violet-500 text-white hover:bg-violet-600 dark:bg-violet-500/90 dark:hover:bg-violet-500', block: 'bg-violet-500/12 text-violet-800 border-violet-500 hover:bg-violet-500/20 dark:bg-violet-500/22 dark:text-violet-100' },
        amber: { dot: 'bg-amber-500', allDay: 'bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-500/90 dark:hover:bg-amber-500', block: 'bg-amber-500/15 text-amber-800 border-amber-500 hover:bg-amber-500/25 dark:bg-amber-500/22 dark:text-amber-100' },
        rose: { dot: 'bg-rose-500', allDay: 'bg-rose-500 text-white hover:bg-rose-600 dark:bg-rose-500/90 dark:hover:bg-rose-500', block: 'bg-rose-500/12 text-rose-800 border-rose-500 hover:bg-rose-500/20 dark:bg-rose-500/22 dark:text-rose-100' },
        sky: { dot: 'bg-sky-500', allDay: 'bg-sky-500 text-white hover:bg-sky-600 dark:bg-sky-500/90 dark:hover:bg-sky-500', block: 'bg-sky-500/12 text-sky-800 border-sky-500 hover:bg-sky-500/20 dark:bg-sky-500/22 dark:text-sky-100' },
        orange: { dot: 'bg-orange-500', allDay: 'bg-orange-500 text-white hover:bg-orange-600 dark:bg-orange-500/90 dark:hover:bg-orange-500', block: 'bg-orange-500/15 text-orange-800 border-orange-500 hover:bg-orange-500/25 dark:bg-orange-500/22 dark:text-orange-100' },
        teal: { dot: 'bg-teal-500', allDay: 'bg-teal-500 text-white hover:bg-teal-600 dark:bg-teal-500/90 dark:hover:bg-teal-500', block: 'bg-teal-500/12 text-teal-800 border-teal-500 hover:bg-teal-500/20 dark:bg-teal-500/22 dark:text-teal-100' },
        pink: { dot: 'bg-pink-500', allDay: 'bg-pink-500 text-white hover:bg-pink-600 dark:bg-pink-500/90 dark:hover:bg-pink-500', block: 'bg-pink-500/12 text-pink-800 border-pink-500 hover:bg-pink-500/20 dark:bg-pink-500/22 dark:text-pink-100' },
        indigo: { dot: 'bg-indigo-500', allDay: 'bg-indigo-500 text-white hover:bg-indigo-600 dark:bg-indigo-500/90 dark:hover:bg-indigo-500', block: 'bg-indigo-500/12 text-indigo-800 border-indigo-500 hover:bg-indigo-500/20 dark:bg-indigo-500/22 dark:text-indigo-100' },
        slate: { dot: 'bg-slate-500', allDay: 'bg-slate-500 text-white hover:bg-slate-600 dark:bg-slate-500/90 dark:hover:bg-slate-500', block: 'bg-slate-500/12 text-slate-800 border-slate-500 hover:bg-slate-500/20 dark:bg-slate-500/22 dark:text-slate-100' },
    },

    weekDays: [],
    monthLabels: [],

    init() {
        this.initLabels();
        this.$watch('view', () => { this.moreDay = null;
            this.emitRange();
            this.$nextTick(() => this.scrollGrid()); });
        this.$watch('focusedKey', () => { this.moreDay = null;
            this.emitRange(); });
        setInterval(() => { this.now = new Date(); }, 60000);
        this.ready = true;
        this.emitRange();
        this.$nextTick(() => this.scrollGrid());
    },

    initLabels() {
        this.weekDays = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(2023, 0, 1 + ((this.firstDayOfWeek + i) % 7));
            this.weekDays.push({
                short: d.toLocaleDateString(this.locale, { weekday: 'short' }),
                narrow: d.toLocaleDateString(this.locale, { weekday: 'narrow' }),
            });
        }
    },

    /* ---------- date helpers (ported from neura::calendar) ---------- */
    pad(n) { return String(n).padStart(2, '0'); },
    keyOf(date) { return date.getFullYear() + '-' + this.pad(date.getMonth() + 1) + '-' + this.pad(date.getDate()); },
    parseKey(str) {
        if (!str || typeof str !== 'string') return null;
        const [y, m, d] = str.split('-').map(Number);
        if (!y || !m || !d) return null;
        const date = new Date(y, m - 1, d);
        return isNaN(date) ? null : date;
    },
    parseDateTime(value) {
        if (!value) return null;
        if (value instanceof Date) return value;
        const str = String(value).trim().replace('T', ' ');
        const [datePart, timePart] = str.split(' ');
        const [y, m, d] = datePart.split('-').map(Number);
        if (!y || !m || !d) return null;
        let hh = 0,
            mm = 0;
        if (timePart) { const p = timePart.split(':');
            hh = Number(p[0]) || 0;
            mm = Number(p[1]) || 0; }
        return new Date(y, m - 1, d, hh, mm);
    },
    get focusedDate() { return this.parseKey(this.focusedKey) ?? new Date(); },
    isSameDay(a, b) { return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); },
    isToday(date) { return this.isSameDay(date, this.now); },
    startOfWeek(date) {
        const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const offset = (d.getDay() - this.firstDayOfWeek + 7) % 7;
        d.setDate(d.getDate() - offset);
        return d;
    },
    addDays(date, n) { const d = new Date(date);
        d.setDate(d.getDate() + n); return d; },

    /* ---------- events model ---------- */
    get normalizedEvents() {
        return (this.events || []).map((ev, i) => {
            const start = this.parseDateTime(ev.start) ?? new Date();
            let end = this.parseDateTime(ev.end);
            const allDay = !!ev.allDay;
            if (!end) end = allDay ? start : new Date(start.getTime() + 60 * 60000);
            if (end < start) end = start;
            return {
                id: ev.id ?? ('ev-' + i),
                title: ev.title ?? 'Untitled',
                location: ev.location ?? null,
                color: this.colorMap[ev.color] ? ev.color : 'blue',
                allDay,
                start,
                end,
                startKey: this.keyOf(start),
                endKey: this.keyOf(end),
                raw: ev,
            };
        });
    },
    color(name, kind) { return (this.colorMap[name] ?? this.colorMap.blue)[kind]; },

    coversDay(ev, key) { return ev.startKey <= key && ev.endKey >= key; },
    isMultiDay(ev) { return ev.startKey !== ev.endKey; },

    dayEntries(key) {
        const list = this.normalizedEvents.filter(ev => this.coversDay(ev, key));
        return list.sort((a, b) => {
            const aAll = a.allDay || this.isMultiDay(a);
            const bAll = b.allDay || this.isMultiDay(b);
            if (aAll !== bAll) return aAll ? -1 : 1;
            return a.start - b.start;
        });
    },

    formatTime(date) {
        return date.toLocaleTimeString(this.locale, { hour: 'numeric', minute: '2-digit' }).replace(':00', '');
    },
    formatRange(ev) {
        if (ev.allDay) return 'All day';
        return this.formatTime(ev.start) + ' – ' + this.formatTime(ev.end);
    },

    /* ---------- month view ---------- */
    get monthMatrix() {
        const base = this.focusedDate;
        const year = base.getFullYear();
        const month = base.getMonth();
        const first = new Date(year, month, 1);
        const offset = (first.getDay() - this.firstDayOfWeek + 7) % 7;
        const cursor = new Date(year, month, 1 - offset);
        const weeks = [];
        for (let w = 0; w < 6; w++) {
            const week = [];
            for (let i = 0; i < 7; i++) {
                const key = this.keyOf(cursor);
                week.push({
                    date: cursor.getDate(),
                    key,
                    inMonth: cursor.getMonth() === month,
                    isToday: this.isToday(cursor),
                    entries: this.dayEntries(key),
                });
                cursor.setDate(cursor.getDate() + 1);
            }
            weeks.push(week);
        }
        return weeks;
    },
    monthCap: 3,
    visibleEntries(cell) { return cell.entries.slice(0, this.monthCap); },
    overflowCount(cell) { return Math.max(0, cell.entries.length - this.monthCap); },

    /* ---------- week / day views ---------- */
    get gridDays() {
        if (this.view === 'day') return [this.focusedDate];
        const start = this.startOfWeek(this.focusedDate);
        return Array.from({ length: 7 }, (_, i) => this.addDays(start, i));
    },
    get hours() {
        const list = [];
        for (let h = this.hourStart; h < this.hourEnd; h++) list.push(h);
        return list;
    },
    get gridHeight() { return (this.hourEnd - this.hourStart) * this.hourHeight; },
    formatHour(h) {
        const d = new Date(2023, 0, 1, h, 0);
        return d.toLocaleTimeString(this.locale, { hour: 'numeric' });
    },

    allDayForDay(date) {
        const key = this.keyOf(date);
        return this.normalizedEvents
            .filter(ev => this.coversDay(ev, key) && (ev.allDay || this.isMultiDay(ev)))
            .sort((a, b) => a.start - b.start);
    },
    get gridHasAllDay() {
        return this.gridDays.some(d => this.allDayForDay(d).length > 0);
    },

    timedForDay(date) {
        const key = this.keyOf(date);
        const dayStart = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0);
        const dayEnd = this.addDays(dayStart, 1);
        const lo = this.hourStart * 60;
        const hi = this.hourEnd * 60;

        const list = this.normalizedEvents
            .filter(ev => !ev.allDay && !this.isMultiDay(ev) && this.coversDay(ev, key))
            .map(ev => {
                const segStart = ev.start < dayStart ? dayStart : ev.start;
                const segEnd = ev.end > dayEnd ? dayEnd : ev.end;
                let startMin = segStart.getHours() * 60 + segStart.getMinutes();
                let endMin = segEnd.getHours() * 60 + segEnd.getMinutes();
                if (endMin <= startMin) endMin = startMin + 30;
                startMin = Math.max(lo, startMin);
                endMin = Math.min(hi, Math.max(endMin, startMin + 20));
                return { ...ev, startMin, endMin };
            })
            .filter(ev => ev.endMin > lo && ev.startMin < hi);

        return this.assignColumns(list);
    },
    assignColumns(list) {
        const evs = list.slice().sort((a, b) => a.startMin - b.startMin || b.endMin - a.endMin);
        let cluster = [];
        let clusterEnd = -Infinity;
        const finalize = (cl) => {
            const colEnds = [];
            cl.forEach(ev => {
                let placed = false;
                for (let i = 0; i < colEnds.length; i++) {
                    if (colEnds[i] <= ev.startMin) { ev._col = i;
                        colEnds[i] = ev.endMin;
                        placed = true; break; }
                }
                if (!placed) { ev._col = colEnds.length;
                    colEnds.push(ev.endMin); }
            });
            cl.forEach(ev => ev._cols = colEnds.length);
        };
        evs.forEach(ev => {
            if (cluster.length && ev.startMin >= clusterEnd) { finalize(cluster);
                cluster = [];
                clusterEnd = -Infinity; }
            cluster.push(ev);
            clusterEnd = Math.max(clusterEnd, ev.endMin);
        });
        if (cluster.length) finalize(cluster);
        return evs;
    },
    eventStyle(ev) {
        const top = (ev.startMin - this.hourStart * 60) * (this.hourHeight / 60);
        const height = (ev.endMin - ev.startMin) * (this.hourHeight / 60);
        const widthPct = 100 / ev._cols;
        const leftPct = ev._col * widthPct;
        return `top:${top}px; height:${Math.max(height, 20)}px; left:calc(${leftPct}% + 2px); width:calc(${widthPct}% - 4px);`;
    },

    /* ---------- current-time indicator ---------- */
    get nowMinutes() { return this.now.getHours() * 60 + this.now.getMinutes(); },
    get nowVisible() {
        const inGrid = this.gridDays.some(d => this.isToday(d));
        return inGrid && this.nowMinutes >= this.hourStart * 60 && this.nowMinutes <= this.hourEnd * 60;
    },
    get nowTop() { return (this.nowMinutes - this.hourStart * 60) * (this.hourHeight / 60); },

    /* ---------- agenda view ---------- */
    get agendaGroups() {
        const from = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth(), 1);
        const to = new Date(this.focusedDate.getFullYear(), this.focusedDate.getMonth() + 1, 0);
        const groups = [];
        let cursor = new Date(from);
        while (cursor <= to) {
            const key = this.keyOf(cursor);
            const entries = this.dayEntries(key);
            if (entries.length) {
                groups.push({
                    key,
                    date: new Date(cursor),
                    isToday: this.isToday(cursor),
                    label: cursor.toLocaleDateString(this.locale, { weekday: 'long', month: 'long', day: 'numeric' }),
                    entries,
                });
            }
            cursor = this.addDays(cursor, 1);
        }
        return groups;
    },

    /* ---------- toolbar / navigation ---------- */
    get title() {
        const d = this.focusedDate;
        if (this.view === 'day') return d.toLocaleDateString(this.locale, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        if (this.view === 'week') {
            const start = this.startOfWeek(d);
            const end = this.addDays(start, 6);
            const sameMonth = start.getMonth() === end.getMonth();
            const startLabel = start.toLocaleDateString(this.locale, { month: 'short', day: 'numeric' });
            const endLabel = sameMonth ?
                end.getDate() + ', ' + end.getFullYear() :
                end.toLocaleDateString(this.locale, { month: 'short', day: 'numeric', year: 'numeric' });
            return startLabel + ' – ' + endLabel;
        }
        return d.toLocaleDateString(this.locale, { month: 'long', year: 'numeric' });
    },
    columnHeader(date) {
        return {
            weekday: date.toLocaleDateString(this.locale, { weekday: 'short' }),
            day: date.getDate(),
        };
    },

    goPrev() {
        const d = this.focusedDate;
        if (this.view === 'day') this.focusedKey = this.keyOf(this.addDays(d, -1));
        else if (this.view === 'week') this.focusedKey = this.keyOf(this.addDays(d, -7));
        else this.focusedKey = this.keyOf(new Date(d.getFullYear(), d.getMonth() - 1, 1));
    },
    goNext() {
        const d = this.focusedDate;
        if (this.view === 'day') this.focusedKey = this.keyOf(this.addDays(d, 1));
        else if (this.view === 'week') this.focusedKey = this.keyOf(this.addDays(d, 7));
        else this.focusedKey = this.keyOf(new Date(d.getFullYear(), d.getMonth() + 1, 1));
    },
    goToday() { this.focusedKey = this.keyOf(new Date()); },
    goToDate(key, view) {
        if (!key) return;
        this.focusedKey = key;
        if (view) this.view = view;
    },
    setView(v) { if (this.availableViews.includes(v)) this.view = v; },
    viewLabel(v) { return { month: '{{ $viewLabels['month'] }}', week: '{{ $viewLabels['week'] }}', day: '{{ $viewLabels['day'] }}', agenda: '{{ $viewLabels['agenda'] }}' } [v]; },

    scrollGrid() {
        const el = this.$refs.gridScroll;
        if (!el) return;
        el.scrollTop = (this.scrollToHour - this.hourStart) * this.hourHeight - 8;
    },

    /* ---------- interaction (dispatch only, host owns the data) ---------- */
    selectDay(key, event) {
        if (event && event.target.closest('[data-event]')) return;
        this.$dispatch('day-selected', { date: key });
    },
    selectSlot(date, hour, event) {
        if (event && event.target.closest('[data-event]')) return;
        const start = new Date(date.getFullYear(), date.getMonth(), date.getDate(), hour, 0);
        const end = new Date(start.getTime() + 60 * 60000);
        this.$dispatch('slot-selected', {
            start: this.keyOf(start) + ' ' + this.pad(start.getHours()) + ':00',
            end: this.keyOf(end) + ' ' + this.pad(end.getHours()) + ':00',
        });
    },
    openEvent(ev) { this.moreDay = null;
        this.$dispatch('event-clicked', { event: ev.raw ?? ev }); },
    showMore(cell, event) {
        const stage = this.$refs.stage.getBoundingClientRect();
        const rect = event.currentTarget.closest('[data-cell]').getBoundingClientRect();
        this.moreDay = {
            key: cell.key,
            label: this.parseKey(cell.key)?.toLocaleDateString(this.locale, { weekday: 'long', month: 'long', day: 'numeric' }),
            entries: cell.entries,
            x: rect.left - stage.left,
            y: rect.top - stage.top,
        };
    },
    emitRange() {
        const d = this.focusedDate;
        let start, end;
        if (this.view === 'day') { start = d;
            end = d; } else if (this.view === 'week') { start = this.startOfWeek(d);
            end = this.addDays(start, 6); } else { start = new Date(d.getFullYear(), d.getMonth(), 1);
            end = new Date(d.getFullYear(), d.getMonth() + 1, 0); }
        this.$dispatch('range-changed', { view: this.view, start: this.keyOf(start), end: this.keyOf(end) });
    },
}" x-cloak wire:ignore.self
    {{ $attributes->class(['flex flex-col overflow-hidden border border-edge bg-surface-raised text-fg', $rounded, $shadow]) }}
    style="height: {{ $height }};">
    {{-- ================= Toolbar ================= --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-edge {{ $s['toolbar'] }}">
        <div class="flex items-center gap-1">
            <neura::button variant="outline" size="sm" x-on:click="goToday">{{ __('Today') }}</neura::button>
            <div class="ms-1 flex items-center">
                <neura::button variant="ghost" size="sm" icon="chevron-left" aria-label="{{ __('Previous') }}"
                    x-on:click="goPrev" />
                <neura::button variant="ghost" size="sm" icon="chevron-right" aria-label="{{ __('Next') }}"
                    x-on:click="goNext" />
            </div>
        </div>

        <h2 class="min-w-0 flex-1 truncate font-semibold text-fg {{ $s['title'] }}" x-text="title"
            aria-live="polite"></h2>

        <div class="flex items-center gap-2">
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @endif

            @if (count($views) > 1)
                <div class="hidden items-center gap-0.5 rounded-lg border border-edge bg-surface-inset p-0.5 sm:flex"
                    role="tablist" aria-label="{{ __('Calendar view') }}">
                    @foreach ($views as $v)
                        <button type="button" role="tab" x-on:click="setView('{{ $v }}')"
                            x-bind:aria-selected="view === '{{ $v }}'"
                            x-bind:class="view === '{{ $v }}'
                                ?
                                'bg-surface-raised text-fg shadow-sm' :
                                'text-fg-secondary hover:text-fg'"
                            class="rounded-md px-2.5 py-1 text-sm font-medium transition-colors">{{ $viewLabels[$v] }}</button>
                    @endforeach
                </div>

                {{-- Compact view switcher on mobile --}}
                <div class="sm:hidden">
                    <neura::dropdown>
                        <x-slot:button>
                            <neura::button variant="outline" size="sm" iconAfter="chevron-down">
                                <span x-text="viewLabel(view)"></span>
                            </neura::button>
                        </x-slot:button>
                        <x-slot:menu>
                            @foreach ($views as $v)
                                <neura::dropdown.item icon="{{ $viewIcons[$v] }}"
                                    x-on:click="setView('{{ $v }}')">{{ $viewLabels[$v] }}
                                </neura::dropdown.item>
                            @endforeach
                        </x-slot:menu>
                    </neura::dropdown>
                </div>
            @endif
        </div>
    </div>

    <div class="flex min-h-0 flex-1">
        @if ($showMiniCalendar)
            <div class="hidden w-72 shrink-0 flex-col gap-4 overflow-y-auto border-e border-edge p-3 lg:flex">
                <div class="flex justify-center" x-on:date-selected="goToDate($event.detail.date)">
                    <neura::calendar :value="$initialKey" :first-day-of-week="$firstDayOfWeek"
                        class="[&>div]:border-0 [&>div]:bg-transparent [&>div]:p-0 [&>div]:shadow-none" />
                </div>

                @isset($sidebar)
                    <div class="border-t border-separator pt-4">{{ $sidebar }}</div>
                @endisset
            </div>
        @endif

        <div class="relative flex min-w-0 flex-1 flex-col" x-ref="stage">
            {{-- ================= MONTH ================= --}}
            <div x-show="view === 'month'" class="flex min-h-0 flex-1 flex-col" x-cloak>
                <div class="grid grid-cols-7 border-b border-edge">
                    <template x-for="(wd, i) in weekDays" :key="i">
                        <div class="text-center font-medium text-fg-muted {{ $s['weekday'] }}">
                            <span class="hidden sm:inline" x-text="wd.short"></span>
                            <span class="sm:hidden" x-text="wd.narrow"></span>
                        </div>
                    </template>
                </div>

                <div class="grid min-h-0 flex-1 grid-rows-6">
                    <template x-for="(week, wi) in monthMatrix" :key="wi">
                        <div class="grid grid-cols-7">
                            <template x-for="cell in week" :key="cell.key">
                                <div data-cell
                                    class="group relative flex min-h-0 min-w-0 cursor-pointer flex-col border-b border-e border-separator transition-colors last:border-e-0 hover:bg-hover [&:nth-child(7n)]:border-e-0 {{ $s['monthCell'] }}"
                                    x-bind:class="!cell.inMonth && 'bg-surface-inset/40'"
                                    x-on:click="selectDay(cell.key, $event)">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="flex items-center justify-center rounded-full font-medium tabular-nums {{ $s['dayNumber'] }}"
                                            x-bind:class="cell.isToday ?
                                                'bg-primary-600 text-white dark:bg-primary-500' :
                                                (cell.inMonth ? 'text-fg' : 'text-fg-disabled')"
                                            x-text="cell.date"></span>
                                    </div>

                                    <div class="flex min-h-0 flex-col gap-0.5 overflow-hidden">
                                        <template x-for="ev in visibleEntries(cell)" :key="ev.id + cell.key">
                                            <button type="button" data-event x-on:click.stop="openEvent(ev)"
                                                class="flex w-full items-center gap-1 truncate rounded text-left transition-colors {{ $s['event'] }}"
                                                x-bind:class="(ev.allDay || isMultiDay(ev)) ?
                                                color(ev.color, 'allDay') + ' font-medium': 'hover:bg-active'">
                                                <template x-if="!(ev.allDay || isMultiDay(ev))">
                                                    <span class="shrink-0 rounded-full {{ $s['eventDot'] }}"
                                                        x-bind:class="color(ev.color, 'dot')"></span>
                                                </template>
                                                <template x-if="!(ev.allDay || isMultiDay(ev))">
                                                    <span
                                                        class="shrink-0 font-medium text-fg-muted tabular-nums {{ $s['eventTime'] }}"
                                                        x-text="formatTime(ev.start)"></span>
                                                </template>
                                                <span class="truncate"
                                                    x-bind:class="!(ev.allDay || isMultiDay(ev)) && 'text-fg'"
                                                    x-text="ev.title"></span>
                                            </button>
                                        </template>

                                        <template x-if="overflowCount(cell) > 0">
                                            <button type="button" data-event x-on:click.stop="showMore(cell, $event)"
                                                class="rounded px-1 py-0.5 text-left font-medium text-fg-muted hover:bg-active hover:text-fg {{ $s['eventTime'] }}"
                                                x-text="'+ ' + overflowCount(cell) + ' more'"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ================= WEEK / DAY (time grid) ================= --}}
            <div x-show="view === 'week' || view === 'day'" class="flex min-h-0 flex-1 flex-col" x-cloak>
                {{-- Sticky day headers --}}
                <div class="flex border-b border-edge pe-[var(--nk-cal-scrollbar,0px)]">
                    <div class="shrink-0 border-e border-separator {{ $s['gutter'] }}"></div>
                    <div class="flex flex-1" x-bind:class="view === 'day' ? '' : 'divide-x divide-separator'">
                        <template x-for="(d, i) in gridDays" :key="i">
                            <button type="button" x-on:click="goToDate(keyOf(d), 'day')"
                                class="flex flex-1 flex-col items-center gap-0.5 transition-colors hover:bg-hover {{ $s['colHeader'] }}">
                                <span class="text-xs font-medium uppercase tracking-wide text-fg-muted"
                                    x-text="columnHeader(d).weekday"></span>
                                <span
                                    class="flex items-center justify-center rounded-full font-semibold tabular-nums {{ $s['colHeaderDay'] }}"
                                    x-bind:class="isToday(d) ? 'bg-primary-600 text-white dark:bg-primary-500' : 'text-fg'"
                                    x-text="columnHeader(d).day"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- All-day row --}}
                <div x-show="gridHasAllDay" class="flex border-b border-edge bg-surface-inset/30" x-cloak>
                    <div
                        class="flex shrink-0 items-center justify-end border-e border-separator pe-2 text-[11px] font-medium text-fg-muted {{ $s['gutter'] }}">
                        {{ __('All day') }}</div>
                    <div class="flex flex-1" x-bind:class="view === 'day' ? '' : 'divide-x divide-separator'">
                        <template x-for="(d, i) in gridDays" :key="i">
                            <div class="flex flex-1 flex-col gap-1 p-1">
                                <template x-for="ev in allDayForDay(d)" :key="ev.id + i">
                                    <button type="button" data-event x-on:click.stop="openEvent(ev)"
                                        class="truncate rounded text-left font-medium transition-colors {{ $s['event'] }}"
                                        x-bind:class="color(ev.color, 'allDay')" x-text="ev.title"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Scrollable time grid --}}
                <div x-ref="gridScroll" class="relative min-h-0 flex-1 overflow-y-auto">
                    <div class="flex" x-bind:style="`height:${gridHeight}px`">
                        {{-- Hour gutter --}}
                        <div class="shrink-0 border-e border-separator {{ $s['gutter'] }}">
                            <template x-for="(h, i) in hours" :key="h">
                                <div class="relative" x-bind:style="`height:${hourHeight}px`">
                                    <span x-show="i > 0"
                                        class="absolute -top-2 right-2 text-fg-muted {{ $s['hourLabel'] }}"
                                        x-text="formatHour(h)"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Day columns --}}
                        <div class="relative flex flex-1"
                            x-bind:class="view === 'day' ? '' : 'divide-x divide-separator'">
                            {{-- horizontal hour lines --}}
                            <div class="pointer-events-none absolute inset-0">
                                <template x-for="(h, i) in hours" :key="h">
                                    <div class="border-b border-separator" x-bind:style="`height:${hourHeight}px`">
                                    </div>
                                </template>
                            </div>

                            <template x-for="(d, di) in gridDays" :key="di">
                                <div class="relative flex-1" x-bind:class="isToday(d) && 'bg-primary-500/[0.04]'">
                                    {{-- click targets per hour --}}
                                    <template x-for="h in hours" :key="h">
                                        <div class="cursor-pointer transition-colors hover:bg-hover"
                                            x-bind:style="`height:${hourHeight}px`"
                                            x-on:click="selectSlot(d, h, $event)"></div>
                                    </template>

                                    {{-- timed events --}}
                                    <template x-for="ev in timedForDay(d)" :key="ev.id + di">
                                        <button type="button" data-event x-on:click.stop="openEvent(ev)"
                                            class="absolute z-10 flex flex-col overflow-hidden rounded-md border-l-2 text-left leading-tight transition-colors {{ $s['timedEvent'] }}"
                                            x-bind:class="color(ev.color, 'block')" x-bind:style="eventStyle(ev)">
                                            <span class="truncate font-semibold" x-text="ev.title"></span>
                                            <span class="truncate opacity-80" x-show="(ev.endMin - ev.startMin) >= 40"
                                                x-text="formatRange(ev)"></span>
                                            <span class="truncate opacity-70"
                                                x-show="ev.location && (ev.endMin - ev.startMin) >= 60"
                                                x-text="ev.location"></span>
                                        </button>
                                    </template>

                                    {{-- current time indicator --}}
                                    <template x-if="nowVisible && isToday(d)">
                                        <div class="pointer-events-none absolute inset-x-0 z-20"
                                            x-bind:style="`top:${nowTop}px`">
                                            <div class="relative h-px bg-rose-500">
                                                <span
                                                    class="absolute -left-1 -top-1 size-2 rounded-full bg-rose-500"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= AGENDA ================= --}}
            <div x-show="view === 'agenda'" class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4" x-cloak>
                <template x-if="agendaGroups.length === 0">
                    <div class="flex h-full flex-col items-center justify-center gap-2 py-16 text-center">
                        <neura::icon name="calendar" class="size-10 text-fg-disabled" />
                        <p class="text-sm text-fg-muted">{{ __('No events this month') }}</p>
                    </div>
                </template>

                <div class="space-y-1">
                    <template x-for="group in agendaGroups" :key="group.key">
                        <div class="flex rounded-lg {{ $s['agendaRow'] }}">
                            <div class="flex shrink-0 flex-col items-center {{ $s['agendaDate'] }}">
                                <span class="text-xs font-medium uppercase text-fg-muted"
                                    x-text="group.date.toLocaleDateString(locale, { weekday: 'short' })"></span>
                                <span
                                    class="mt-0.5 flex items-center justify-center rounded-full font-semibold tabular-nums {{ $s['agendaDayNum'] }}"
                                    x-bind:class="group.isToday ? 'bg-primary-600 text-white dark:bg-primary-500' : 'text-fg'"
                                    x-text="group.date.getDate()"></span>
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5">
                                <template x-for="ev in group.entries" :key="ev.id + group.key">
                                    <button type="button" data-event x-on:click.stop="openEvent(ev)"
                                        class="flex w-full items-start gap-3 rounded-lg border border-transparent px-2.5 py-2 text-left transition-colors hover:border-edge hover:bg-hover">
                                        <span class="mt-1 size-2.5 shrink-0 rounded-full"
                                            x-bind:class="color(ev.color, 'dot')"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-fg" x-text="ev.title"></p>
                                            <p class="truncate text-xs text-fg-muted">
                                                <span x-text="formatRange(ev)"></span>
                                                <template x-if="ev.location">
                                                    <span><span class="mx-1">·</span><span
                                                            x-text="ev.location"></span></span>
                                                </template>
                                            </p>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ================= "+N more" popover ================= --}}
            <template x-if="moreDay">
                <div>
                    <div class="fixed inset-0 z-30" x-on:click="moreDay = null"></div>
                    <div class="absolute z-40 w-64 rounded-lg border border-edge bg-surface-raised p-2 shadow-lg ring-1 ring-black/5 dark:ring-white/10"
                        x-bind:style="`left:${Math.max(8, Math.min(moreDay.x, $refs.stage.clientWidth - 264))}px; top:${Math.max(8, Math.min(moreDay.y, $refs.stage.clientHeight - 268))}px`"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        <div class="flex items-center justify-between px-1.5 pb-2">
                            <span class="text-sm font-semibold text-fg" x-text="moreDay.label"></span>
                            <button type="button" class="rounded p-0.5 text-fg-muted hover:bg-hover hover:text-fg"
                                x-on:click="moreDay = null" aria-label="{{ __('Close') }}">
                                <neura::icon name="x-mark" class="size-4" />
                            </button>
                        </div>
                        <div class="max-h-64 space-y-0.5 overflow-y-auto">
                            <template x-for="ev in moreDay.entries" :key="ev.id">
                                <button type="button" x-on:click.stop="openEvent(ev)"
                                    class="flex w-full items-center gap-2 rounded px-1.5 py-1.5 text-left text-sm transition-colors hover:bg-hover">
                                    <span class="size-2 shrink-0 rounded-full"
                                        x-bind:class="color(ev.color, 'dot')"></span>
                                    <span class="shrink-0 text-xs text-fg-muted tabular-nums"
                                        x-show="!(ev.allDay || isMultiDay(ev))" x-text="formatTime(ev.start)"></span>
                                    <span class="truncate text-fg" x-text="ev.title"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

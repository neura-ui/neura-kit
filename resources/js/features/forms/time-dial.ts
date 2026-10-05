import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Time dial.
 *
 * The value is UTC minutes from midnight on the reference day.
 * The hour face stays fixed, with noon at the top.
 * A cursor rides the home city's local time. The other cities sit on theirs.
 */

const DAY = 1440;
const STEP = 15;
const CX = 100;
const BAND_IN = 58;
const BAND_OUT = 74;

interface City {
    id: string;
    name: string;
    code: string;
    offset: number;
}

interface Phrases {
    behind: string;
    ahead: string;
    home: string;
    nextDay: string;
    previousDay: string;
    atWork: string;
}

interface TimeDialConfig {
    cities?: City[];
    value?: number | null;
    homeId?: string | null;
    min?: number | null;
    max?: number | null;
    weekday?: number;
    hourCycle?: 12 | 24;
    workingHours?: [number, number];
    label?: string;
    phrases?: Partial<Phrases>;
}

function mod(value: number, base: number): number {
    return ((value % base) + base) % base;
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function polar(minutes: number, radius: number): { x: number; y: number } {
    const rad = ((minutes - 720) / DAY) * Math.PI * 2 - Math.PI / 2;
    return {
        x: CX + radius * Math.cos(rad),
        y: CX + radius * Math.sin(rad),
    };
}

function sector(start: number, end: number): string {
    const sweep = mod(end - start, DAY) || DAY;
    if (sweep >= DAY - 1) {
        return `M ${CX} ${CX - BAND_OUT} A ${BAND_OUT} ${BAND_OUT} 0 1 1 ${CX - 0.01} ${CX - BAND_OUT} L ${CX - 0.01} ${CX - BAND_IN} A ${BAND_IN} ${BAND_IN} 0 1 0 ${CX} ${CX - BAND_IN} Z`;
    }
    const large = sweep > DAY / 2 ? 1 : 0;
    const outerStart = polar(start, BAND_OUT);
    const outerEnd = polar(start + sweep, BAND_OUT);
    const innerEnd = polar(start + sweep, BAND_IN);
    const innerStart = polar(start, BAND_IN);
    return `M ${outerStart.x} ${outerStart.y} A ${BAND_OUT} ${BAND_OUT} 0 ${large} 1 ${outerEnd.x} ${outerEnd.y} L ${innerEnd.x} ${innerEnd.y} A ${BAND_IN} ${BAND_IN} 0 ${large} 0 ${innerStart.x} ${innerStart.y} Z`;
}

onAlpineInit(() => {
    window.Alpine.data('neuraTimeDial', (config: TimeDialConfig = {}) => ({
        cities: (config.cities ?? []).slice(0, 5),
        value: Number(config.value ?? 0),
        raw: Number(config.value ?? 0),
        homeId: config.homeId || config.cities?.[0]?.id || '',
        min: config.min == null ? -Infinity : Number(config.min),
        max: config.max == null ? Infinity : Number(config.max),
        weekday: Number(config.weekday ?? 1),
        hourCycle: config.hourCycle === 24 ? 24 : 12,
        workingHours: config.workingHours ?? [540, 1080],
        label: config.label || 'Time',
        phrases: {
            behind: config.phrases?.behind || '{hours} h behind',
            ahead: config.phrases?.ahead || '{hours} h ahead',
            home: config.phrases?.home || 'Home',
            nextDay: config.phrases?.nextDay || 'next day',
            previousDay: config.phrases?.previousDay || 'previous day',
            atWork: config.phrases?.atWork || '{count} of {total} offices at work',
        } as Phrases,
        defaultValue: Number(config.value ?? 0),
        defaultHomeId: config.homeId || config.cities?.[0]?.id || '',
        dragging: false,
        announcement: '',
        velocity: 0,
        lastStamp: 0,

        init() {
            if (!this.cities.some((city: City) => city.id === this.homeId)) {
                this.homeId = this.cities[0]?.id || '';
            }
            this.defaultHomeId = this.homeId;
            this.raw = this.value;
            const model = this.$root?._x_model?.get?.();
            if (typeof model === 'number') {
                this.value = model;
                this.raw = model;
                this.defaultValue = model;
            }
        },

        get home(): City {
            return this.cities.find((city: City) => city.id === this.homeId) || this.cities[0];
        },

        get homeLocal(): number {
            return this.value + (this.home?.offset || 0);
        },

        get bandPath(): string {
            const [start, end] = this.workingHours;
            return sector(start, end);
        },

        get homeMinutes(): number {
            return Math.round(mod(this.homeLocal, DAY));
        },

        get valueText(): string {
            const city = this.home;
            if (!city) return '';
            return `${city.name} ${this.formatTime(this.homeLocal)}`;
        },

        localMinutes(city: City): number {
            return this.value + city.offset;
        },

        dayDelta(city: City): number {
            return Math.floor(this.localMinutes(city) / DAY) - Math.floor(this.value / DAY);
        },

        isWorking(city: City): boolean {
            const minute = mod(this.localMinutes(city), DAY);
            const [start, end] = this.workingHours;
            if (start === end) return true;
            if (start < end) return minute >= start && minute < end;
            return minute >= start || minute < end;
        },

        formatTime(total: number): string {
            const minuteOfDay = mod(total, DAY);
            let hour = Math.floor(minuteOfDay / 60);
            const minute = minuteOfDay % 60;
            if (this.hourCycle === 24) return `${pad(hour)}:${pad(minute)}`;
            const period = hour >= 12 ? 'PM' : 'AM';
            hour %= 12;
            if (hour === 0) hour = 12;
            return `${hour}:${pad(minute)} ${period}`;
        },

        weekdayName(city: City): string {
            const day = mod(this.weekday + Math.floor(this.localMinutes(city) / DAY), 7);
            const sunday = Date.UTC(2024, 0, 7);
            return new Intl.DateTimeFormat(undefined, { weekday: 'long', timeZone: 'UTC' }).format(new Date(sunday + day * 86400000));
        },

        offsetLabel(minutes: number): string {
            const sign = minutes < 0 ? '−' : '+';
            const abs = Math.abs(minutes);
            const hour = Math.floor(abs / 60);
            const minute = abs % 60;
            return minute ? `UTC${sign}${hour}:${pad(minute)}` : `UTC${sign}${hour}`;
        },

        hoursLabel(delta: number): string {
            const hours = Math.abs(delta) / 60;
            const text = Number.isInteger(hours) ? String(hours) : String(Math.round(hours * 100) / 100);
            const template = delta < 0 ? this.phrases.behind : this.phrases.ahead;
            return template.replace('{hours}', text);
        },

        cityMeta(city: City): string {
            const home = this.home;
            if (!home || city.id === home.id) return `${this.offsetLabel(city.offset)} · ${this.phrases.home}`;
            return `${this.offsetLabel(city.offset)} · ${this.hoursLabel(city.offset - home.offset)}`;
        },

        dayNote(city: City): string {
            const delta = this.dayDelta(city);
            if (delta > 0) return this.phrases.nextDay;
            if (delta < 0) return this.phrases.previousDay;
            return '';
        },

        poseMinutes(): number {
            return (this.dragging ? this.raw : this.value) + (this.home?.offset || 0);
        },

        markStyle(minutes: number, radius: number): string {
            const local = mod(minutes, DAY);
            const rad = ((local - 720) / DAY) * Math.PI * 2 - Math.PI / 2;
            const x = 50 + radius * Math.cos(rad);
            const y = 50 + radius * Math.sin(rad);
            return `left:${x}%;top:${y}%`;
        },

        cityStyle(city: City): string {
            const minutes = (this.dragging ? this.raw : this.value) + city.offset;
            return this.markStyle(minutes, 33);
        },

        cursorStyle(): string {
            return this.markStyle(this.poseMinutes(), 37);
        },

        get needleStyle(): string {
            const turn = ((mod(this.poseMinutes(), DAY) - 720) / DAY) * 360;
            return `transform: rotate(${turn}deg)`;
        },

        atWorkCount(): number {
            return this.cities.filter((city: City) => this.isWorking(city)).length;
        },

        atWorkText(): string {
            return this.phrases.atWork
                .replace('{count}', String(this.atWorkCount()))
                .replace('{total}', String(this.cities.length));
        },

        summaryText(): string {
            return this.cities.map((city: City) => {
                const note = this.dayNote(city);
                const time = this.formatTime(this.localMinutes(city));
                return note ? `${city.name} ${time}, ${note}` : `${city.name} ${time}`;
            }).join('. ');
        },

        clamp(value: number): number {
            return Math.min(this.max, Math.max(this.min, value));
        },

        snap(value: number): number {
            return Math.round(value / STEP) * STEP;
        },

        commit(next: number, speak: boolean) {
            const value = this.clamp(this.snap(next));
            this.raw = value;
            this.value = value;
            this.publish();
            if (speak) this.announcement = `${this.summaryText()}. ${this.atWorkText()}`;
        },

        publish() {
            const input = this.$refs.input as HTMLInputElement | undefined;
            if (input) input.value = String(this.value);
            this.$dispatch('time-dial-change', { value: this.value, homeId: this.homeId });
            this.$root?._x_model?.set?.(this.value);
        },

        setHome(id: string) {
            if (!this.cities.some((city: City) => city.id === id)) return;
            this.homeId = id;
            this.announcement = `${this.valueText}. ${this.atWorkText()}`;
            this.$dispatch('time-dial-home', { value: this.value, homeId: id });
        },

        angleOf(event: PointerEvent): number {
            const rect = (this.$refs.dial as HTMLElement).getBoundingClientRect();
            const x = event.clientX - (rect.left + rect.width / 2);
            const y = event.clientY - (rect.top + rect.height / 2);
            return Math.atan2(y, x) * 180 / Math.PI;
        },

        minutesAt(event: PointerEvent): number {
            const fromTop = mod(this.angleOf(event) + 90, 360);
            const local = mod(720 + (fromTop / 360) * DAY, DAY);
            let next = local - (this.home?.offset || 0);
            while (next - this.raw > DAY / 2) next -= DAY;
            while (this.raw - next > DAY / 2) next += DAY;
            return next;
        },

        clearPointerFocus() {
            delete (this.$refs.dial as HTMLElement).dataset.pointerFocus;
        },

        onPointerDown(event: PointerEvent) {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            event.preventDefault();
            window.getSelection()?.removeAllRanges();
            const dial = this.$refs.dial as HTMLElement;
            dial.dataset.pointerFocus = 'true';
            dial.focus({ preventScroll: true, focusVisible: false } as FocusOptions);
            const rect = dial.getBoundingClientRect();
            const x = event.clientX - (rect.left + rect.width / 2);
            const y = event.clientY - (rect.top + rect.height / 2);
            if (Math.hypot(x, y) < rect.width * 0.28) return;
            dial.setPointerCapture(event.pointerId);
            this.velocity = 0;
            this.lastStamp = event.timeStamp;
            this.raw = this.value;
            this.dragging = true;
            this.raw = this.minutesAt(event);
            this.value = this.clamp(this.snap(this.raw));
            this.publish();
        },

        onPointerMove(event: PointerEvent) {
            if (!this.dragging) return;
            const next = this.minutesAt(event);
            const dt = Math.max(16, event.timeStamp - this.lastStamp);
            this.velocity = (next - this.raw) / dt;
            this.lastStamp = event.timeStamp;
            if (next < this.min) this.raw = this.min + (next - this.min) * 0.28;
            else if (next > this.max) this.raw = this.max + (next - this.max) * 0.28;
            else this.raw = next;
            this.value = this.clamp(this.snap(this.raw));
            this.publish();
        },

        onPointerUp() {
            window.getSelection()?.removeAllRanges();
            if (!this.dragging) return;
            this.dragging = false;
            const coast = prefersReducedMotion() ? 0 : Math.max(-180, Math.min(180, this.velocity * 220));
            this.commit(this.raw + coast, true);
        },

        onKey(event: KeyboardEvent) {
            const steps: Record<string, number> = {
                ArrowRight: STEP,
                ArrowUp: STEP,
                ArrowLeft: -STEP,
                ArrowDown: -STEP,
                PageUp: 60,
                PageDown: -60,
            };
            if (event.key === 'Home') {
                event.preventDefault();
                this.commit(this.snap(-((this.home?.offset) || 0)), true);
                return;
            }
            if (event.key === 'End') {
                event.preventDefault();
                this.commit(this.snap(DAY - STEP - ((this.home?.offset) || 0)), true);
                return;
            }
            if (!(event.key in steps)) return;
            event.preventDefault();
            this.commit(this.value + steps[event.key], true);
        },

        findOverlap() {
            const start = Number.isFinite(this.min) ? this.snap(this.min) : 0;
            const end = Number.isFinite(this.max) ? this.max : DAY;
            let best = start;
            let bestScore = -1;
            for (let time = start; time < end; time += STEP) {
                const score = this.cities.filter((city: City) => {
                    const minute = mod(time + city.offset, DAY);
                    const [from, to] = this.workingHours;
                    if (from === to) return true;
                    if (from < to) return minute >= from && minute < to;
                    return minute >= from || minute < to;
                }).length;
                if (score > bestScore) {
                    bestScore = score;
                    best = time;
                }
            }
            this.commit(best, true);
        },

        reset() {
            this.homeId = this.defaultHomeId;
            this.commit(this.defaultValue, true);
        },
    }));
});

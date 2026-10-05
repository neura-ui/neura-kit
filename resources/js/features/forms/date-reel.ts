import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Date reel.
 *
 * A phone-style wheel. Each column is a short cylinder: a flick keeps its
 * speed, then a spring lands on an entry. The face does not spin as a whole.
 * The value is a local YYYY-MM-DDTHH:mm string.
 */

const SLOTS = 11;
const HALF = 5;
const ANGLE = 0.42;

type Mode = 'datetime' | 'date' | 'time';
type ReelId = 'day' | 'month' | 'date' | 'year' | 'hour' | 'minute' | 'period';

interface Parts {
    y: number;
    m: number;
    d: number;
    h: number;
    min: number;
}

interface Phrases {
    today: string;
    tomorrow: string;
    yesterday: string;
    inDays: string;
    daysAgo: string;
    at: string;
    thisEvening: string;
    schedule: string;
    scheduling: string;
    scheduled: string;
    day: string;
    month: string;
    date: string;
    year: string;
    hour: string;
    minute: string;
    period: string;
    presets: string;
    label: string;
}

interface Preset {
    label: string;
    value: string;
}

interface DateReelConfig {
    mode?: Mode;
    value?: Parts | null;
    today?: Parts | null;
    min?: Parts | null;
    max?: Parts | null;
    minuteStep?: number;
    hourCycle?: 12 | 24;
    presets?: Preset[] | false | null;
    title?: string;
    confirm?: boolean;
    intro?: boolean;
    speed?: number;
    paused?: boolean;
    label?: string;
    locale?: string;
    phrases?: Partial<Phrases>;
}

interface Reel {
    id: ReelId;
    name: string;
    el: HTMLElement;
    nodes: HTMLElement[];
    lens: HTMLElement[];
    pos: number;
    vel: number;
    target: number;
    min: number;
    max: number;
    dragging: boolean;
    pointerY: number;
    stamp: number;
    moved: number;
    shown: number;
    introAt: number;
    buffer: string;
    typedAt: number;
}

interface Host {
    summary: string;
    announcement: string;
    iso: string;
    live: string;
    phase: 'idle' | 'pending' | 'success';
    dragging: boolean;
    presets: Preset[];
    showConfirm: boolean;
    title: string;
    phrases: Phrases;
    $refs: { stage?: HTMLElement; input?: HTMLInputElement };
    $root: HTMLElement;
    $nextTick: (fn: () => void) => void;
    $dispatch: (name: string, detail?: unknown) => void;
    _x_model?: { get?: () => unknown; set?: (value: string) => void };
}

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function stamp(parts: Parts): number {
    return Date.UTC(parts.y, parts.m, parts.d, parts.h, parts.min);
}

function fromStamp(ms: number, hours: number, minutes: number): Parts {
    const date = new Date(ms);
    return {
        y: date.getUTCFullYear(),
        m: date.getUTCMonth(),
        d: date.getUTCDate(),
        h: hours,
        min: minutes,
    };
}

function shiftDays(parts: Parts, days: number): Parts {
    return fromStamp(Date.UTC(parts.y, parts.m, parts.d + days), parts.h, parts.min);
}

function dayNumber(parts: Parts): number {
    return Math.floor(Date.UTC(parts.y, parts.m, parts.d) / 86400000);
}

function daysInMonth(year: number, month: number): number {
    return new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
}

function toIso(parts: Parts): string {
    return `${parts.y}-${pad(parts.m + 1)}-${pad(parts.d)}T${pad(parts.h)}:${pad(parts.min)}`;
}

function clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}

const DEFAULT_PHRASES: Phrases = {
    today: 'Today',
    tomorrow: 'Tomorrow',
    yesterday: 'Yesterday',
    inDays: 'In {count} days',
    daysAgo: '{count} days ago',
    at: 'at',
    thisEvening: 'This evening',
    schedule: 'Schedule',
    scheduling: 'Scheduling',
    scheduled: 'Scheduled',
    day: 'Day',
    month: 'Month',
    date: 'Date',
    year: 'Year',
    hour: 'Hour',
    minute: 'Minute',
    period: 'AM or PM',
    presets: 'Quick picks',
    label: 'Date and time',
};

function createEngine(config: DateReelConfig) {
    const mode: Mode = config.mode === 'date' || config.mode === 'time' ? config.mode : 'datetime';
    const hourCycle = config.hourCycle === 24 ? 24 : 12;
    const step = [1, 5, 10, 15, 30].includes(Number(config.minuteStep)) ? Number(config.minuteStep) : 5;
    const locale = config.locale || undefined;
    const intro = config.intro !== false;
    const paused = config.paused === true;
    const speed = Math.max(0.35, Number(config.speed) || 1);
    const phrases = { ...DEFAULT_PHRASES, ...config.phrases };
    const today: Parts = config.today || fromStamp(Date.UTC(2026, 9, 5), 0, 0);
    const min = config.min || (mode === 'date' ? shiftDays({ ...today, y: today.y - 100 }, 0) : shiftDays(today, -30));
    const max = config.max || (mode === 'date' ? shiftDays({ ...today, y: today.y + 10 }, 0) : shiftDays(today, 365));
    const minYear = min.y;
    const maxYear = max.y;

    let api: Host;
    let reels: Reel[] = [];
    let raf = 0;
    let last = 0;
    let committed = '';
    let introduced = false;
    let destroyed = false;
    let observer: IntersectionObserver | null = null;
    let inView = true;
    const reduced = prefersReducedMotion();

    const periodNames = (() => {
        const format = (hour: number) => new Intl.DateTimeFormat(locale, {
            hour: 'numeric',
            hourCycle: 'h12',
            timeZone: 'UTC',
        }).formatToParts(new Date(Date.UTC(2026, 0, 1, hour))).find((part) => part.type === 'dayPeriod')?.value || (hour < 12 ? 'AM' : 'PM');
        return [format(1), format(13)];
    })();

    function weekdayName(parts: Parts): string {
        return new Intl.DateTimeFormat(locale, { weekday: 'long', timeZone: 'UTC' }).format(new Date(Date.UTC(parts.y, parts.m, parts.d)));
    }

    function nextMonday(): Parts {
        const weekday = new Date(Date.UTC(today.y, today.m, today.d)).getUTCDay();
        const delta = (1 - weekday + 7) % 7;
        return { ...shiftDays(today, delta), h: 9, min: 0 };
    }

    function defaultPresets(): Preset[] {
        if (mode !== 'datetime') return [];
        return [
            { label: phrases.thisEvening, value: toIso({ ...today, h: 18, min: 0 }) },
            { label: phrases.tomorrow, value: toIso({ ...shiftDays(today, 1), h: 9, min: 0 }) },
            { label: weekdayName(nextMonday()), value: toIso(nextMonday()) },
        ];
    }

    function initialParts(): Parts {
        const given = config.value;
        if (given) return { ...given };
        if (mode === 'date') return { ...today, h: 0, min: 0 };
        if (mode === 'time') return { ...today, h: 9, min: 30 };
        return { ...shiftDays(today, 1), h: 9, min: 0 };
    }

    function rowHeight(): number {
        const stage = api.$refs.stage;
        if (!stage) return 36;
        const raw = parseFloat(getComputedStyle(stage).getPropertyValue('--reel-row'));
        return Number.isFinite(raw) && raw > 8 ? raw : 36;
    }

    function reelBy(id: ReelId): Reel | undefined {
        return reels.find((reel) => reel.id === id);
    }

    function hourFrom(index: number, period: number): number {
        if (hourCycle === 24) return index;
        const face = index % 12;
        if (period === 1) return face === 0 ? 12 : face + 12;
        return face === 0 ? 0 : face;
    }

    function indexFor(id: ReelId, parts: Parts): number {
        if (id === 'day') return dayNumber(parts) - dayNumber(min);
        if (id === 'month') return parts.m;
        if (id === 'date') return parts.d - 1;
        if (id === 'year') return parts.y - minYear;
        if (id === 'hour') return hourCycle === 24 ? parts.h : parts.h % 12;
        if (id === 'minute') return clamp(Math.round(parts.min / step), 0, Math.floor(59 / step));
        return parts.h >= 12 ? 1 : 0;
    }

    function limits(id: ReelId): [number, number] {
        if (id === 'day') return [0, Math.max(0, dayNumber(max) - dayNumber(min))];
        if (id === 'month') return [0, 11];
        if (id === 'date') return [0, 30];
        if (id === 'year') return [0, Math.max(0, maxYear - minYear)];
        if (id === 'hour') return [0, hourCycle === 24 ? 23 : 11];
        if (id === 'minute') return [0, Math.floor(59 / step)];
        return [0, 1];
    }

    function entryLabel(id: ReelId, index: number): string {
        if (id === 'day') {
            const parts = shiftDays(min, index);
            const delta = dayNumber(parts) - dayNumber(today);
            if (delta === 0) return phrases.today;
            if (delta === 1) return phrases.tomorrow;
            if (delta === -1) return phrases.yesterday;
            return new Intl.DateTimeFormat(locale, { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(parts.y, parts.m, parts.d)));
        }
        if (id === 'month') {
            return new Intl.DateTimeFormat(locale, { month: 'short', timeZone: 'UTC' }).format(new Date(Date.UTC(2026, index, 1)));
        }
        if (id === 'date') return String(index + 1);
        if (id === 'year') return String(minYear + index);
        if (id === 'hour') {
            if (hourCycle === 24) return pad(index);
            return String(index === 0 ? 12 : index);
        }
        if (id === 'minute') return pad(index * step);
        return periodNames[index] || '';
    }

    function isDim(id: ReelId, index: number): boolean {
        if (id !== 'date' || mode !== 'date') return false;
        const year = reelBy('year');
        const month = reelBy('month');
        if (!year || !month) return false;
        return index + 1 > daysInMonth(minYear + Math.round(year.pos), Math.round(month.pos));
    }

    function read(): Parts {
        if (mode === 'date') {
            const year = Math.round(reelBy('year')?.pos || 0);
            const month = Math.round(reelBy('month')?.pos || 0);
            const dim = daysInMonth(minYear + year, month);
            const day = Math.min(dim, Math.round(reelBy('date')?.pos || 0) + 1);
            return { y: minYear + year, m: month, d: day, h: 0, min: 0 };
        }
        if (mode === 'time') {
            const period = Math.round(reelBy('period')?.pos || 0);
            const hour = hourFrom(Math.round(reelBy('hour')?.pos || 0), period);
            const minute = Math.round(reelBy('minute')?.pos || 0) * step;
            return { ...today, h: hour, min: minute };
        }
        const parts = shiftDays(min, Math.round(reelBy('day')?.pos || 0));
        const period = Math.round(reelBy('period')?.pos || 0);
        parts.h = hourFrom(Math.round(reelBy('hour')?.pos || 0), period);
        parts.min = Math.round(reelBy('minute')?.pos || 0) * step;
        return parts;
    }

    function relativeDay(parts: Parts): string {
        const delta = dayNumber(parts) - dayNumber(today);
        if (delta === 0) return phrases.today;
        if (delta === 1) return phrases.tomorrow;
        if (delta === -1) return phrases.yesterday;
        if (delta > 1 && delta <= 14) return phrases.inDays.replace('{count}', String(delta));
        if (delta < -1 && delta >= -14) return phrases.daysAgo.replace('{count}', String(Math.abs(delta)));
        return new Intl.DateTimeFormat(locale, { weekday: 'long', month: 'long', day: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(parts.y, parts.m, parts.d)));
    }

    function formatTime(parts: Parts): string {
        return new Intl.DateTimeFormat(locale, {
            hour: 'numeric',
            minute: '2-digit',
            hourCycle: hourCycle === 24 ? 'h23' : 'h12',
            timeZone: 'UTC',
        }).format(new Date(Date.UTC(2026, 0, 1, parts.h, parts.min)));
    }

    function format(parts: Parts): string {
        if (mode === 'time') return formatTime(parts);
        if (mode === 'date') {
            return new Intl.DateTimeFormat(locale, { month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' }).format(new Date(Date.UTC(parts.y, parts.m, parts.d)));
        }
        return `${relativeDay(parts)} ${phrases.at} ${formatTime(parts)}`;
    }

    function pageStep(id: ReelId): number {
        if (id === 'day' || id === 'date') return 7;
        if (id === 'hour') return hourCycle === 24 ? 6 : 3;
        if (id === 'minute') return Math.max(1, Math.round(15 / step));
        if (id === 'year') return 10;
        if (id === 'month') return 3;
        return 1;
    }

    function place(reel: Reel, index: number, animate: boolean) {
        const next = clamp(index, reel.min, reel.max);
        reel.target = next;
        if (!animate || reduced || paused) {
            reel.pos = next;
            reel.vel = 0;
            paint();
            settle();
            return;
        }
        kick();
    }

    function paintReel(reel: Reel) {
        const row = rowHeight();
        const radius = row / ANGLE;
        const nearest = Math.round(reel.pos);
        for (let slot = 0; slot < SLOTS; slot += 1) {
            const index = nearest + (slot - HALF);
            const node = reel.nodes[slot];
            if (!node) continue;
            if (index < reel.min || index > reel.max) {
                node.textContent = '';
                node.style.opacity = '0';
                continue;
            }
            const offset = index - reel.pos;
            const angle = offset * ANGLE;
            const y = Math.sin(angle) * radius;
            const fade = Math.max(0, Math.cos(angle));
            const scale = Math.max(0.04, fade);
            const label = entryLabel(reel.id, index);
            if (node.dataset.label !== label) {
                node.textContent = label;
                node.dataset.label = label;
            }
            node.style.transform = `translateY(${y.toFixed(2)}px) scale(${(0.9 + fade * 0.1).toFixed(3)}, ${scale.toFixed(3)})`;
            let opacity = Math.pow(fade, 1.2);
            if (Math.abs(offset) < 0.62) opacity = 0;
            if (isDim(reel.id, index)) opacity *= 0.35;
            node.style.opacity = opacity.toFixed(3);
        }
        const base = Math.floor(reel.pos);
        const frac = reel.pos - base;
        [base, base + 1].forEach((index, copy) => {
            const node = reel.lens[copy];
            if (!node) return;
            const inside = index >= reel.min && index <= reel.max;
            const label = inside ? entryLabel(reel.id, index) : '';
            if (node.dataset.label !== label) {
                node.textContent = label;
                node.dataset.label = label;
            }
            const shift = copy === 0 ? -frac * row : (1 - frac) * row;
            node.style.transform = `translateY(${shift.toFixed(2)}px)`;
            node.classList.toggle('is-dim', inside && isDim(reel.id, index));
        });
        if (reel.shown !== nearest) {
            reel.shown = nearest;
            reel.el.setAttribute('aria-valuenow', String(nearest));
            reel.el.setAttribute('aria-valuetext', entryLabel(reel.id, nearest));
        }
    }

    function publishLive() {
        const parts = read();
        const iso = toIso(parts);
        const summary = format(parts);
        if (api.live !== iso) api.live = iso;
        if (api.summary !== summary) api.summary = summary;
    }

    function paint() {
        reels.forEach(paintReel);
        publishLive();
    }

    function atRest(): boolean {
        return reels.every((reel) => !reel.dragging && Math.abs(reel.pos - reel.target) < 0.0015 && Math.abs(reel.vel) < 0.02);
    }

    function reconcile(): boolean {
        let changed = false;
        if (mode === 'date') {
            const year = reelBy('year');
            const month = reelBy('month');
            const date = reelBy('date');
            if (year && month && date && !date.dragging) {
                const dim = daysInMonth(minYear + Math.round(year.pos), Math.round(month.pos));
                if (Math.round(date.pos) > dim - 1) {
                    date.target = dim - 1;
                    changed = true;
                }
            }
        }
        const parts = read();
        const time = stamp(parts);
        let next = parts;
        if (mode !== 'time' && time < stamp({ ...min, h: 0, min: 0 })) next = { ...min, h: parts.h, min: parts.min };
        if (mode !== 'time' && time > stamp({ ...max, h: 23, min: 59 })) next = { ...max, h: parts.h, min: parts.min };
        if (toIso(next) !== toIso(parts)) {
            reels.forEach((reel) => {
                const index = indexFor(reel.id, next);
                if (reel.target !== index) {
                    reel.target = index;
                    changed = true;
                }
            });
        }
        return changed;
    }

    function settle() {
        if (!atRest()) return;
        if (reconcile()) {
            kick();
            return;
        }
        const parts = read();
        const iso = toIso(parts);
        if (iso === committed) return;
        committed = iso;
        api.iso = iso;
        api.live = iso;
        api.summary = format(parts);
        const input = api.$refs.input;
        if (input) input.value = iso;
        api.$dispatch('date-reel-change', { value: iso });
        api.announcement = api.summary;
        const model = (api.$root as HTMLElement & { _x_model?: { set?: (value: string) => void } })._x_model;
        model?.set?.(iso);
    }

    function frame(now: number) {
        const dt = Math.min(0.032, (now - last) / 1000 || 0.016);
        last = now;
        let moving = false;
        const omega = 14 * speed;
        reels.forEach((reel) => {
            if (reel.introAt) {
                moving = true;
                if (now >= reel.introAt) {
                    const room = reel.max - reel.target;
                    reel.pos = room > 0 ? reel.target + Math.min(4, room) : reel.target;
                    reel.vel = 0;
                    reel.introAt = 0;
                }
            }
            if (reel.dragging) {
                moving = true;
                return;
            }
            const distance = reel.pos - reel.target;
            if (Math.abs(distance) < 0.0015 && Math.abs(reel.vel) < 0.02) {
                reel.pos = reel.target;
                reel.vel = 0;
                return;
            }
            const accel = -omega * omega * distance - 2 * omega * reel.vel;
            reel.vel += accel * dt;
            reel.pos += reel.vel * dt;
            moving = true;
        });
        paint();
        if (moving && inView && !document.hidden) raf = requestAnimationFrame(frame);
        else {
            raf = 0;
            settle();
        }
    }

    function kick() {
        if (destroyed || !inView || document.hidden) return;
        if (raf) return;
        last = 0;
        raf = requestAnimationFrame(frame);
    }

    function mountReel(id: ReelId, parts: Parts): Reel {
        const [minIndex, maxIndex] = limits(id);
        const index = clamp(indexFor(id, parts), minIndex, maxIndex);
        const el = document.createElement('div');
        el.className = 'nk-reel';
        el.dataset.reel = id;
        el.tabIndex = 0;
        el.setAttribute('role', 'spinbutton');
        el.setAttribute('aria-label', phrases[id]);
        el.setAttribute('aria-valuemin', String(minIndex));
        el.setAttribute('aria-valuemax', String(maxIndex));
        const drum = document.createElement('div');
        drum.className = 'nk-reel-drum';
        drum.setAttribute('aria-hidden', 'true');
        const nodes = Array.from({ length: SLOTS }, () => {
            const item = document.createElement('div');
            item.className = 'nk-reel-item';
            drum.appendChild(item);
            return item;
        });
        const clip = document.createElement('div');
        clip.className = 'nk-reel-clip';
        clip.setAttribute('aria-hidden', 'true');
        const lens = [0, 1].map(() => {
            const item = document.createElement('div');
            item.className = 'nk-reel-lens-text';
            clip.appendChild(item);
            return item;
        });
        el.append(drum, clip);
        const reel: Reel = {
            id,
            name: phrases[id],
            el,
            nodes,
            lens,
            pos: index,
            vel: 0,
            target: index,
            min: minIndex,
            max: maxIndex,
            dragging: false,
            pointerY: 0,
            stamp: 0,
            moved: 0,
            shown: -1,
            introAt: 0,
            buffer: '',
            typedAt: 0,
        };
        el.addEventListener('pointerdown', (event) => onDown(reel, event));
        el.addEventListener('pointermove', (event) => onMove(reel, event));
        el.addEventListener('pointerup', (event) => onUp(reel, event));
        el.addEventListener('pointercancel', (event) => onUp(reel, event));
        el.addEventListener('keydown', (event) => onKey(reel, event));
        el.addEventListener('wheel', (event) => onWheel(reel, event), { passive: false });
        return reel;
    }

    function onDown(reel: Reel, event: PointerEvent) {
        if (event.pointerType === 'mouse' && event.button !== 0) return;
        reel.dragging = true;
        reel.pointerY = event.clientY;
        reel.stamp = event.timeStamp;
        reel.moved = 0;
        reel.vel = 0;
        api.dragging = true;
        reel.el.focus();
        try { reel.el.setPointerCapture(event.pointerId); } catch { /* synthetic pointers */ }
    }

    function onMove(reel: Reel, event: PointerEvent) {
        if (!reel.dragging) return;
        const dy = event.clientY - reel.pointerY;
        const dt = Math.max(16, event.timeStamp - reel.stamp);
        reel.pointerY = event.clientY;
        reel.stamp = event.timeStamp;
        reel.moved += Math.abs(dy);
        let next = reel.pos - dy / rowHeight();
        if (next < reel.min) {
            next = reel.pos < reel.min && next > reel.pos ? Math.min(reel.min, next) : reel.min + (next - reel.min) * 0.28;
        } else if (next > reel.max) {
            next = reel.pos > reel.max && next < reel.pos ? Math.max(reel.max, next) : reel.max + (next - reel.max) * 0.28;
        }
        reel.vel = (next - reel.pos) / (dt / 1000);
        reel.pos = next;
        paint();
    }

    function onUp(reel: Reel, event: PointerEvent) {
        if (!reel.dragging) return;
        reel.dragging = false;
        api.dragging = reels.some((item) => item.dragging);
        if (reel.moved < 4) {
            const rect = reel.el.getBoundingClientRect();
            const rows = (event.clientY - rect.top - rect.height / 2) / rowHeight();
            reel.target = clamp(Math.round(reel.pos + rows), reel.min, reel.max);
            reel.vel = 0;
        } else if (reduced || paused) {
            reel.target = clamp(Math.round(reel.pos), reel.min, reel.max);
            reel.pos = reel.target;
            reel.vel = 0;
        } else {
            reel.target = clamp(Math.round(reel.pos + reel.vel * 0.16), reel.min, reel.max);
        }
        kick();
    }

    function onWheel(reel: Reel, event: WheelEvent) {
        event.preventDefault();
        const delta = Math.abs(event.deltaY) >= Math.abs(event.deltaX) ? event.deltaY : event.deltaX;
        const dir = Math.sign(delta);
        if (!dir) return;
        reel.target = clamp(Math.round(reel.target) + dir, reel.min, reel.max);
        if (reduced || paused) {
            reel.pos = reel.target;
            reel.vel = 0;
            paint();
            settle();
            return;
        }
        kick();
    }

    function onKey(reel: Reel, event: KeyboardEvent) {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            const index = reels.indexOf(reel) + (event.key === 'ArrowRight' ? 1 : -1);
            reels[index]?.el.focus();
            return;
        }
        if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault();
            place(reel, event.key === 'Home' ? reel.min : reel.max, true);
            return;
        }
        if (event.key === 'PageUp' || event.key === 'PageDown') {
            event.preventDefault();
            const dir = event.key === 'PageDown' ? 1 : -1;
            place(reel, Math.round(reel.target) + dir * pageStep(reel.id), true);
            return;
        }
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            place(reel, Math.round(reel.target) + (event.key === 'ArrowDown' ? 1 : -1), true);
            return;
        }
        if (event.key.length === 1 && /[0-9a-z]/i.test(event.key)) {
            event.preventDefault();
            const now = performance.now();
            if (now - reel.typedAt > 700) reel.buffer = '';
            reel.buffer += event.key.toLowerCase();
            reel.typedAt = now;
            for (let index = reel.min; index <= reel.max; index += 1) {
                const label = entryLabel(reel.id, index).toLowerCase();
                if (label.startsWith(reel.buffer) || label.replace(/\D/g, '').startsWith(reel.buffer)) {
                    place(reel, index, true);
                    return;
                }
            }
        }
    }

    function playIntro() {
        if (introduced) return;
        introduced = true;
        if (!intro || reduced || paused) return;
        const start = performance.now();
        reels.forEach((reel, index) => {
            reel.introAt = start + index * 90;
        });
        kick();
    }

    function columns(): ReelId[] {
        if (mode === 'date') return ['month', 'date', 'year'];
        if (mode === 'time') return hourCycle === 24 ? ['hour', 'minute'] : ['hour', 'minute', 'period'];
        return hourCycle === 24 ? ['day', 'hour', 'minute'] : ['day', 'hour', 'minute', 'period'];
    }

    return {
        attach(host: Host) {
            api = host;
            const parts = initialParts();
            const stage = () => api.$refs.stage;
            api.presets = config.presets === false ? [] : (config.presets || defaultPresets());
            api.showConfirm = config.confirm === true;
            api.title = config.title || '';
            api.phrases = phrases;
            api.summary = format(parts);
            api.iso = toIso(parts);
            api.live = api.iso;
            committed = api.iso;
            api.$nextTick(() => {
                const root = stage();
                if (!root) return;
                reels = columns().map((id) => mountReel(id, parts));
                const lens = document.createElement('div');
                lens.className = 'nk-reel-lens';
                lens.setAttribute('aria-hidden', 'true');
                root.append(lens, ...reels.map((reel) => reel.el));
                root.setAttribute('role', 'group');
                root.setAttribute('aria-label', config.label || phrases.label);
                paint();
                const input = api.$refs.input;
                if (input) input.value = committed;
                observer = new IntersectionObserver((entries) => {
                    inView = entries.some((entry) => entry.isIntersecting);
                    if (!inView) {
                        park();
                        return;
                    }
                    playIntro();
                    if (!atRest()) kick();
                });
                observer.observe(api.$root);
                document.addEventListener('visibilitychange', onVisible);
            });
        },
        destroy() {
            destroyed = true;
            if (raf) cancelAnimationFrame(raf);
            observer?.disconnect();
            document.removeEventListener('visibilitychange', onVisible);
        },
        liveIso() {
            return api.live;
        },
        spinTo(value: string) {
            const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(value);
            if (!match) return;
            const parts = {
                y: Number(match[1]),
                m: Number(match[2]) - 1,
                d: Number(match[3]),
                h: Number(match[4]),
                min: Number(match[5]),
            };
            reels.forEach((reel, index) => {
                window.setTimeout(() => {
                    if (!destroyed) place(reel, indexFor(reel.id, parts), true);
                }, reduced || paused ? 0 : index * 45);
            });
        },
        confirm() {
            if (api.phase !== 'idle') return;
            api.phase = 'pending';
            api.$dispatch('date-reel-confirm', { value: api.iso });
            window.setTimeout(() => {
                if (destroyed) return;
                api.phase = 'success';
                api.announcement = phrases.scheduled;
                window.setTimeout(() => {
                    if (!destroyed) api.phase = 'idle';
                }, 900);
            }, 700);
        },
    };

    function park() {
        if (raf) cancelAnimationFrame(raf);
        raf = 0;
        reels.forEach((reel) => {
            reel.pos = reel.target;
            reel.vel = 0;
            reel.introAt = 0;
        });
        if (api) paint();
    }

    function onVisible() {
        if (document.hidden) {
            park();
            return;
        }
        if (inView && !atRest()) kick();
    }
}

onAlpineInit(() => {
    window.Alpine.data('neuraDateReel', (config: DateReelConfig = {}) => {
        const engine = createEngine(config);
        return {
            summary: '',
            announcement: '',
            iso: '',
            live: '',
            phase: 'idle' as 'idle' | 'pending' | 'success',
            dragging: false,
            presets: [] as Preset[],
            showConfirm: false,
            title: '',
            phrases: DEFAULT_PHRASES,
            init() {
                engine.attach(this as unknown as Host);
            },
            destroy() {
                engine.destroy();
            },
            matches(value: string) {
                return this.live === value;
            },
            pick(value: string) {
                engine.spinTo(value);
            },
            confirmPick() {
                engine.confirm();
            },
            confirmText() {
                if (this.phase === 'pending') return this.phrases.scheduling;
                if (this.phase === 'success') return this.phrases.scheduled;
                return this.phrases.schedule;
            },
        };
    });
});

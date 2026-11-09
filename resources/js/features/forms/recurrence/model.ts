/**
 * Recurrence rule.
 *
 * The editor's state is a flat rule that reads from and writes to an RRULE (RFC 5545):
 * `FREQ=WEEKLY;BYDAY=MO,TH;BYHOUR=9;BYMINUTE=0`. Only what the editor can show is kept: a
 * daily, weekly (on some weekdays), monthly (on a day, or the nth weekday) or yearly (on a
 * date) repetition, an optional time, and an end by date or by count. Dates are local
 * calendar days. No imports, so it runs in Node.
 */

export type Freq = 'daily' | 'weekly' | 'monthly' | 'yearly';
export type End = 'never' | 'until' | 'count';

export interface Rule {
    freq: Freq;
    interval: number;
    /** Weekly: weekdays, 0 = Sunday … 6 = Saturday. */
    days: number[];
    monthMode: 'day' | 'nth';
    /** Monthly on a day: 1 … 31, or -1 for the last day. */
    monthDay: number;
    /** Monthly on a weekday: 1 … 4, or -1 for the last one. */
    nth: number;
    nthDay: number;
    /** Yearly: month 1 … 12 and day of that month. */
    month: number;
    yearDay: number;
    /** HH:mm, or null for no time. */
    time: string | null;
    end: End;
    /** YYYY-MM-DD, inclusive. */
    until: string | null;
    count: number;
}

export const CODES = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
const FREQS: Record<string, Freq> = { DAILY: 'daily', WEEKLY: 'weekly', MONTHLY: 'monthly', YEARLY: 'yearly' };
const DAY_MS = 86400000;

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

export function iso(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function fromIso(text: string | null | undefined): Date | null {
    const match = String(text ?? '').match(/^(\d{4})-?(\d{2})-?(\d{2})/);
    if (!match) return null;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    return Number.isNaN(date.getTime()) ? null : date;
}

export function startOfDay(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function addDays(date: Date, days: number): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

function daysBetween(a: Date, b: Date): number {
    return Math.round((startOfDay(b).getTime() - startOfDay(a).getTime()) / DAY_MS);
}

function mondayOf(date: Date): Date {
    return addDays(date, -((date.getDay() + 6) % 7));
}

function lastDayOfMonth(date: Date): number {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
}

/** The nth weekday of its month, or the last (-1). */
function isNth(date: Date, nth: number, weekday: number): boolean {
    if (date.getDay() !== weekday) return false;
    return nth === -1 ? date.getDate() + 7 > lastDayOfMonth(date) : Math.ceil(date.getDate() / 7) === nth;
}

/** A sensible rule for a start date: every week on its weekday, the other modes on its day. */
export function defaultRule(start: Date): Rule {
    const week = Math.ceil(start.getDate() / 7);
    return {
        freq: 'weekly',
        interval: 1,
        days: [start.getDay()],
        monthMode: 'day',
        monthDay: start.getDate(),
        nth: week > 4 ? -1 : week,
        nthDay: start.getDay(),
        month: start.getMonth() + 1,
        yearDay: start.getDate(),
        time: null,
        end: 'never',
        until: null,
        count: 10,
    };
}

function clampInt(value: unknown, min: number, max: number, fallback: number): number {
    const n = Number.parseInt(String(value), 10);
    return Number.isFinite(n) ? Math.min(max, Math.max(min, n)) : fallback;
}

/**
 * The rule in an RRULE string, over the defaults for `start`. Accepts the bare rule, an
 * `RRULE:` line, or a block with DTSTART and other lines; keys in any case; parts it does
 * not know are dropped.
 */
export function parseRRule(text: string | null | undefined, start: Date): Rule {
    const rule = defaultRule(start);
    const source = String(text ?? '');
    const line = source.split(/\r?\n/).map((l) => l.trim()).find((l) => /(^|[:;])FREQ=/i.test(l));
    if (!line) return rule;

    const parts: Record<string, string> = {};
    for (const part of line.replace(/^RRULE:/i, '').split(';')) {
        const [key, value] = part.split('=');
        if (key && value !== undefined) parts[key.trim().toUpperCase()] = value.trim().toUpperCase();
    }

    const freq = FREQS[parts.FREQ];
    if (!freq) return rule;
    rule.freq = freq;
    rule.interval = clampInt(parts.INTERVAL, 1, 999, 1);

    const byDay = (parts.BYDAY ?? '').split(',').map((d) => d.match(/^([+-]?\d{1,2})?(SU|MO|TU|WE|TH|FR|SA)$/)).filter(Boolean) as RegExpMatchArray[];
    const setPos = parts.BYSETPOS !== undefined ? clampInt(parts.BYSETPOS, -1, 5, 1) : null;
    const monthDay = parts.BYMONTHDAY !== undefined ? clampInt(parts.BYMONTHDAY.split(',')[0], -1, 31, rule.monthDay) : null;

    if (freq === 'weekly' && byDay.length) {
        rule.days = [...new Set(byDay.map((m) => CODES.indexOf(m[2])))];
    }

    if (freq === 'monthly') {
        const nthDay = byDay[0];
        if (nthDay && (nthDay[1] || setPos !== null)) {
            const nth = nthDay[1] ? Number(nthDay[1]) : (setPos as number);
            rule.monthMode = 'nth';
            rule.nth = nth < 0 || nth > 4 ? -1 : Math.max(1, nth);
            rule.nthDay = CODES.indexOf(nthDay[2]);
        } else if (monthDay !== null && monthDay !== 0) {
            rule.monthMode = 'day';
            rule.monthDay = monthDay;
        }
    }

    if (freq === 'yearly') {
        if (parts.BYMONTH !== undefined) rule.month = clampInt(parts.BYMONTH.split(',')[0], 1, 12, rule.month);
        if (monthDay !== null && monthDay > 0) rule.yearDay = monthDay;
    }

    if (parts.BYHOUR !== undefined) {
        const hour = clampInt(parts.BYHOUR.split(',')[0], 0, 23, 0);
        const minute = clampInt((parts.BYMINUTE ?? '0').split(',')[0], 0, 59, 0);
        rule.time = `${pad(hour)}:${pad(minute)}`;
    }

    const until = parts.UNTIL ? fromIso(parts.UNTIL) : null;
    if (until) {
        rule.end = 'until';
        rule.until = iso(until);
    } else if (parts.COUNT !== undefined) {
        rule.end = 'count';
        rule.count = clampInt(parts.COUNT, 1, 999, 10);
    }

    return rule;
}

/** Weekdays in RRULE order, Monday first. */
function sortedCodes(days: number[]): string[] {
    return [...new Set(days)].sort((a, b) => ((a + 6) % 7) - ((b + 6) % 7)).map((d) => CODES[d]);
}

/** The rule as an RRULE string, without the `RRULE:` prefix. `time: false` leaves the hour out. */
export function toRRule(rule: Rule, options: { time?: boolean } = {}): string {
    const parts = [`FREQ=${rule.freq.toUpperCase()}`];
    if (rule.interval > 1) parts.push(`INTERVAL=${rule.interval}`);

    if (rule.freq === 'weekly') {
        parts.push(`BYDAY=${sortedCodes(rule.days.length ? rule.days : [0]).join(',')}`);
    } else if (rule.freq === 'monthly') {
        parts.push(rule.monthMode === 'nth' ? `BYDAY=${rule.nth}${CODES[rule.nthDay]}` : `BYMONTHDAY=${rule.monthDay}`);
    } else if (rule.freq === 'yearly') {
        parts.push(`BYMONTH=${rule.month}`, `BYMONTHDAY=${rule.yearDay}`);
    }

    if (options.time !== false && rule.time) {
        const [hour, minute] = rule.time.split(':').map(Number);
        parts.push(`BYHOUR=${hour}`, `BYMINUTE=${minute}`);
    }

    if (rule.end === 'until' && rule.until) parts.push(`UNTIL=${rule.until.replace(/-/g, '')}T235959`);
    else if (rule.end === 'count') parts.push(`COUNT=${rule.count}`);

    return parts.join(';');
}

function matches(rule: Rule, date: Date, start: Date): boolean {
    switch (rule.freq) {
        case 'daily':
            return daysBetween(start, date) % rule.interval === 0;
        case 'weekly': {
            const weeks = Math.round(daysBetween(mondayOf(start), mondayOf(date)) / 7);
            const days = rule.days.length ? rule.days : [start.getDay()];
            return weeks % rule.interval === 0 && days.includes(date.getDay());
        }
        case 'monthly': {
            const months = (date.getFullYear() - start.getFullYear()) * 12 + date.getMonth() - start.getMonth();
            if (months % rule.interval !== 0) return false;
            if (rule.monthMode === 'nth') return isNth(date, rule.nth, rule.nthDay);
            return rule.monthDay === -1 ? date.getDate() === lastDayOfMonth(date) : date.getDate() === rule.monthDay;
        }
        case 'yearly':
            return (date.getFullYear() - start.getFullYear()) % rule.interval === 0
                && date.getMonth() + 1 === rule.month
                && date.getDate() === rule.yearDay;
    }
}

/**
 * The first `limit` dates of the rule from `start` that fall on or after `from`. A count
 * counts from the start, so dates already passed use it up too.
 */
export function occurrences(rule: Rule, start: Date, limit: number, from: Date = start): Date[] {
    const first = startOfDay(start);
    const after = startOfDay(from);
    const until = rule.end === 'until' ? fromIso(rule.until) : null;
    const out: Date[] = [];
    let seen = 0;

    // Fifty years covers a rule every few years on 29 February.
    for (let i = 0; i < 366 * 50 && out.length < limit; i++) {
        const date = addDays(first, i);
        if (until && date > until) break;
        if (!matches(rule, date, first)) continue;
        seen += 1;
        if (rule.end === 'count' && seen > rule.count) break;
        if (date >= after) out.push(date);
    }

    return out;
}

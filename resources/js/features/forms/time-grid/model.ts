/**
 * Time grid model.
 *
 * Each column (a weekday, a room…) keeps a sorted list of spans in minutes from midnight,
 * end excluded, minute-precise rather than cell by cell. Painting a cell only adds or
 * removes that cell's interval, so a span off the step or outside the grid survives until
 * the user touches its own cells.
 *
 * No imports: the tests load this file with Node directly.
 */

export interface Span {
    start: number;
    end: number;
    level: string;
}

export type Spans = Map<string, Span[]>;

export type Format = 'ranges' | 'days';

export interface SerializeOptions {
    format: Format;
    keyName: 'day' | 'column';
    /** Shown columns, in the order they are drawn. Hidden columns with spans follow. */
    order: string[];
    withLevel: boolean;
}

const DAY = 1440;

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

/** Minutes out of 9:00, 09:00 or 09:00:00; 24:00 is the end of the day. */
export function toMinutes(text: unknown): number | null {
    const match = /^(\d{1,2}):(\d{2})(?::\d{2})?$/.exec(String(text ?? '').trim());
    if (!match) return null;

    const hours = Number(match[1]);
    const minutes = Number(match[2]);
    if (minutes > 59 || hours > 24 || (hours === 24 && minutes > 0)) return null;

    return hours * 60 + minutes;
}

export function toClock(minutes: number): string {
    return pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60);
}

function merge(spans: Span[]): Span[] {
    const merged: Span[] = [];

    for (const span of spans) {
        const last = merged[merged.length - 1];
        if (last && last.level === span.level && last.end >= span.start) {
            last.end = Math.max(last.end, span.end);
        } else {
            merged.push({ ...span });
        }
    }

    return merged;
}

/** Set [start, end) to a level, or clear it with null. Returns a new list. */
export function paint(spans: Span[], start: number, end: number, level: string | null): Span[] {
    if (end <= start) return spans.map((span) => ({ ...span }));

    const kept: Span[] = [];
    for (const span of spans) {
        if (span.end <= start || span.start >= end) {
            kept.push({ ...span });
            continue;
        }
        if (span.start < start) kept.push({ start: span.start, end: start, level: span.level });
        if (span.end > end) kept.push({ start: end, end: span.end, level: span.level });
    }

    if (level !== null) kept.push({ start, end, level });
    kept.sort((a, b) => a.start - b.start);

    return merge(kept);
}

/** A cell is full when one level covers all of it, partial when anything less does. */
export function coverage(spans: Span[], start: number, end: number): { level: string | null; partial: boolean } {
    let covered = 0;
    const levels = new Set<string>();

    for (const span of spans) {
        const overlap = Math.min(span.end, end) - Math.max(span.start, start);
        if (overlap > 0) {
            covered += overlap;
            levels.add(span.level);
        }
    }

    if (covered === 0) return { level: null, partial: false };
    if (covered >= end - start && levels.size === 1) return { level: [...levels][0], partial: false };

    return { level: null, partial: true };
}

/**
 * Either shape in: a list of `{ day | column, start, end, level? }`, or an object of
 * column => `[{ start, end, level? }]`. A PHP list with gaps in its keys (filtered without
 * array_values) arrives as an object and is read as the list it was. An end at 00:00 is
 * read as midnight at the end of the day; a span that still ends before it starts is dropped.
 */
export function parse(value: unknown, defaultLevel: string): Spans {
    const spans: Spans = new Map();

    const add = (key: string, item: unknown): void => {
        if (!item || typeof item !== 'object') return;

        const slot = item as Record<string, unknown>;
        const start = toMinutes(slot.start);
        let end = toMinutes(slot.end);
        if (start === null || end === null) return;
        if (end === 0) end = DAY;
        if (end <= start) return;

        const level = typeof slot.level === 'string' && slot.level !== '' ? slot.level : defaultLevel;
        spans.set(key, paint(spans.get(key) ?? [], start, end, level));
    };

    const addRange = (item: unknown): void => {
        const key = item && typeof item === 'object' ? ((item as Record<string, unknown>).day ?? (item as Record<string, unknown>).column) : null;
        if (typeof key === 'string' || typeof key === 'number') add(String(key), item);
    };

    const isSlot = (item: unknown): boolean => !!item && typeof item === 'object' && ('start' in item || 'end' in item);

    if (Array.isArray(value)) {
        value.forEach(addRange);
    } else if (value && typeof value === 'object') {
        for (const [key, entry] of Object.entries(value)) {
            if (Array.isArray(entry)) entry.forEach((item) => add(key, item));
            else if (isSlot(entry)) addRange(entry);
            else if (entry && typeof entry === 'object') Object.values(entry).forEach((item) => add(key, item));
        }
    }

    return spans;
}

export function serialize(spans: Spans, options: SerializeOptions): unknown {
    const { format, keyName, order, withLevel } = options;
    const keys = [...order, ...[...spans.keys()].filter((key) => !order.includes(key))];

    const slot = (span: Span): Record<string, string> => {
        const item: Record<string, string> = { start: toClock(span.start), end: toClock(span.end) };
        if (withLevel) item.level = span.level;
        return item;
    };

    if (format === 'days') {
        const days: Record<string, Record<string, string>[]> = {};
        for (const key of keys) {
            const list = spans.get(key) ?? [];
            if (list.length > 0 || order.includes(key)) days[key] = list.map(slot);
        }
        return days;
    }

    return keys.flatMap((key) => (spans.get(key) ?? []).map((span) => ({ [keyName]: key, ...slot(span) })));
}

/**
 * The cells a stroke crosses between two pointer events, both ends included: one step at a
 * time along the longer axis, the other rounded. Browsers send one move per frame, so a
 * quick stroke would otherwise jump cells.
 */
export function cellsBetween(from: [number, number], to: [number, number]): Array<[number, number]> {
    const steps = Math.max(Math.abs(to[0] - from[0]), Math.abs(to[1] - from[1]));
    const cells: Array<[number, number]> = [];

    for (let index = 0; index <= steps; index++) {
        const t = steps === 0 ? 0 : index / steps;
        cells.push([Math.round(from[0] + (to[0] - from[0]) * t), Math.round(from[1] + (to[1] - from[1]) * t)]);
    }

    return cells;
}

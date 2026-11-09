/**
 * Slider scale.
 *
 * A scale is linear (min, max, step) or a list of allowed values (`steps`), spaced evenly
 * along the track whatever their gaps: 0,10 / 0,25 / 0,5 / 1 / 2 / 5 € each get the same room.
 * Positions run from 0 to 1 along the track, slots are what the native range inputs hold:
 * the value itself on a linear scale, its index on a list. No imports, so it runs in Node.
 */

export interface Scale {
    min: number;
    max: number;
    step: number;
    steps: number[] | null;
}

export function precisionOf(step: number): number {
    if (!Number.isFinite(step)) return 0;
    const text = String(step);
    const exponent = text.match(/e-(\d+)$/);
    if (exponent) return Number(exponent[1]);
    const dot = text.indexOf('.');
    return dot < 0 ? 0 : text.length - dot - 1;
}

function round(value: number, decimals: number): number {
    const factor = 10 ** decimals;
    return Math.round(value * factor) / factor;
}

function clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}

/** Index of the listed value nearest to `value`. */
function nearestIndex(value: number, steps: number[]): number {
    let best = 0;
    for (let i = 1; i < steps.length; i++) {
        if (Math.abs(steps[i] - value) < Math.abs(steps[best] - value)) best = i;
    }
    return best;
}

/** The value on the scale nearest to `value`. */
export function snap(value: number, scale: Scale): number {
    if (scale.steps && scale.steps.length) return scale.steps[nearestIndex(value, scale.steps)];
    const step = scale.step > 0 ? scale.step : 1;
    const decimals = Math.max(precisionOf(step), precisionOf(scale.min));
    const snapped = scale.min + Math.round((clamp(value, scale.min, scale.max) - scale.min) / step) * step;
    // A last step shorter than the others still reaches max.
    return round(snapped > scale.max ? scale.max : snapped, decimals);
}

export function toSlot(value: number, scale: Scale): number {
    return scale.steps && scale.steps.length ? nearestIndex(value, scale.steps) : snap(value, scale);
}

export function fromSlot(slot: number, scale: Scale): number {
    if (scale.steps && scale.steps.length) return scale.steps[clamp(Math.round(slot), 0, scale.steps.length - 1)];
    return snap(slot, scale);
}

export function toPosition(value: number, scale: Scale): number {
    if (scale.steps && scale.steps.length) {
        return scale.steps.length < 2 ? 0 : nearestIndex(value, scale.steps) / (scale.steps.length - 1);
    }
    return scale.max === scale.min ? 0 : (clamp(value, scale.min, scale.max) - scale.min) / (scale.max - scale.min);
}

export function fromPosition(position: number, scale: Scale): number {
    const p = clamp(position, 0, 1);
    if (scale.steps && scale.steps.length) return scale.steps[Math.round(p * (scale.steps.length - 1))];
    return snap(scale.min + p * (scale.max - scale.min), scale);
}

function toNumber(value: unknown): number | null {
    if (value === null || value === undefined || value === '') return null;
    const number = typeof value === 'number' ? value : Number(value);
    return Number.isFinite(number) ? number : null;
}

/**
 * A bound value as one or two values on the scale: a number (or numeric string) for a
 * single thumb, a pair in any order (array or {0, 1}) for a range. Missing ends fall back
 * to min and max.
 */
export function normalize(value: unknown, scale: Scale, range: boolean): number[] {
    const low = scale.steps && scale.steps.length ? scale.steps[0] : scale.min;
    const high = scale.steps && scale.steps.length ? scale.steps[scale.steps.length - 1] : scale.max;

    if (!range) {
        const single = toNumber(Array.isArray(value) ? value[0] : value);
        return [snap(single ?? low, scale)];
    }

    let pair: unknown[] = [];
    if (Array.isArray(value)) pair = value;
    else if (value && typeof value === 'object') pair = [(value as Record<string, unknown>)[0], (value as Record<string, unknown>)[1]];
    else pair = [value];

    const a = toNumber(pair[0]);
    const b = toNumber(pair[1]);
    const ends = [snap(a ?? low, scale), snap(b ?? high, scale)];
    return ends[0] <= ends[1] ? ends : [ends[1], ends[0]];
}

/**
 * The two ends after `moved` was dragged: the moved thumb stops `gap` short of the other
 * (in values on a linear scale, in listed values on a list), and pushes it when the track
 * runs out.
 */
export function constrain(values: number[], moved: number, gap: number, scale: Scale): number[] {
    if (values.length < 2) return values.slice();
    const listed = !!(scale.steps && scale.steps.length);
    const toUnit = (v: number) => (listed ? nearestIndex(v, scale.steps as number[]) : v);
    const fromUnit = (u: number) => (listed ? (scale.steps as number[])[u] : snap(u, scale));
    const low = listed ? 0 : scale.min;
    const high = listed ? (scale.steps as number[]).length - 1 : scale.max;
    const space = Math.max(0, Math.min(gap, high - low));

    let [a, b] = [toUnit(values[0]), toUnit(values[1])];
    if (moved === 0) {
        a = Math.min(a, b - space);
        if (a < low) [a, b] = [low, low + space];
    } else {
        b = Math.max(b, a + space);
        if (b > high) [a, b] = [high - space, high];
    }
    return [fromUnit(a), fromUnit(b)];
}

export interface FormatOptions {
    locale?: string;
    decimals?: number | null;
    prefix?: string;
    suffix?: string;
}

/** A value for people: the locale's digits with the prefix and suffix around them. */
export function format(value: number, options: FormatOptions = {}): string {
    const decimals = options.decimals ?? precisionOf(value);
    let text: string;
    try {
        text = new Intl.NumberFormat(options.locale || undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value);
    } catch {
        text = value.toFixed(decimals);
    }
    return `${options.prefix ?? ''}${text}${options.suffix ?? ''}`;
}

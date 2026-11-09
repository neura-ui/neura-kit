import { onAlpineInit } from '../../../runtime/alpine';
import { describeNaturalDate, parseNaturalDate, selectionFor } from './parse';
import type { NaturalMode, NaturalResult } from './parse';

/**
 * The calendar's natural-language field. It sits inside the calendar's own component and
 * works through it: the dates it understands are outlined in the months (naturalPreview),
 * the months move to show them, and Enter applies them the way a preset does. With a time
 * (the date picker's time mode), the time goes out as `time-selected` after the date.
 */

interface NaturalDateConfig {
    locale?: string;
    weekStart?: number;
    time?: boolean;
    placeholder?: string;
}

function fromIso(value: string): Date {
    const [y, m, d] = value.split('-').map(Number);
    return new Date(y, m - 1, d);
}

/** Every day a value covers: a date, a range's days, or a list. */
function daysOf(value: ReturnType<typeof selectionFor>): string[] {
    if (typeof value === 'string') return [value];
    if (Array.isArray(value)) return value;
    if (!value.end) return [value.start];
    const days: string[] = [];
    for (let d = fromIso(value.start), end = fromIso(value.end); d <= end && days.length < 62; d.setDate(d.getDate() + 1)) {
        days.push(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'));
    }
    return days;
}

onAlpineInit(() => {
    window.Alpine.data('neuraNaturalDate', (config: NaturalDateConfig = {}) => ({
        text: '',
        result: null as NaturalResult | null,
        blocked: false,

        init(this: any) {
            this.$watch('text', () => this.read());
        },

        /** The calendar this field belongs to decides the mode. */
        naturalMode(this: any): NaturalMode {
            if (config.time) return 'single';
            return this.isRange ? 'range' : this.isMultiple ? 'multiple' : 'single';
        },

        get label(): string {
            const self = this as any;
            return self.result ? describeNaturalDate(self.result, self.naturalMode(), config.locale ?? 'en') : '';
        },

        read(this: any) {
            const text = this.text.trim();
            this.result = text ? parseNaturalDate(text, { locale: config.locale, weekStart: config.weekStart }) : null;

            const days = this.result ? daysOf(selectionFor(this.result, this.naturalMode())) : [];
            this.blocked = days.some((day: string) => this.isDateDisabled(day, fromIso(day).getDay()));
            this.naturalPreview = days;

            // The months follow what was understood.
            if (days.length) {
                const first = fromIso(days[0]);
                if (first.getFullYear() !== this.currentYear || first.getMonth() !== this.currentMonth) {
                    this.showMonth(first.getFullYear(), first.getMonth());
                }
            }
        },

        apply(this: any) {
            if (!this.result || this.blocked || this.isDisabled) return;
            const result = this.result as NaturalResult;
            this.applyPreset(selectionFor(result, this.naturalMode()));
            if (config.time && result.time) this.$dispatch('time-selected', { time: result.time });
            this.clear();
        },

        clear(this: any) {
            this.text = '';
            this.result = null;
            this.blocked = false;
            this.naturalPreview = [];
        },
    }));
});

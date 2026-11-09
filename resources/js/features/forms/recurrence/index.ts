import { onAlpineInit } from '../../../runtime/alpine';
import { defaultRule, fromIso, iso, occurrences, parseRRule, startOfDay, toRRule } from './model';
import type { End, Freq, Rule } from './model';
import { describeRule, parseNaturalRule } from './language';

/**
 * Recurrence editor.
 *
 * The rule lives here and is written to the model as an RRULE on every change (the editor's
 * changes are clicks, not drags). A value set from outside is read back into the rule. With
 * `optional`, an empty value means no repetition; otherwise an empty value takes the default
 * rule, so what the editor shows is always what the model holds.
 */

interface RecurrenceConfig {
    value?: string | null;
    start?: string | null;
    locale?: string;
    weekStart?: number;
    time?: boolean;
    optional?: boolean;
    preview?: number;
    maxInterval?: number;
    phrases?: Record<string, string>;
}

onAlpineInit(() => {
    window.Alpine.data('neuraRecurrence', (config: RecurrenceConfig = {}) => {
        const today = startOfDay(new Date());
        const start = fromIso(config.start) ?? today;
        const from = start > today ? start : today;
        const locale = config.locale || 'en';
        const withTime = config.time !== false;
        const optional = !!config.optional;
        const limit = Math.max(0, Number(config.preview ?? 5));
        const maxInterval = Math.max(1, Number(config.maxInterval ?? 99));
        const phrases = config.phrases ?? {};
        const weekStart = Number(config.weekStart ?? 1);

        const safe = (options: Intl.DateTimeFormatOptions) => {
            try {
                return new Intl.DateTimeFormat(locale, options);
            } catch {
                return new Intl.DateTimeFormat('en', options);
            }
        };
        const weekdayFormat = safe({ weekday: 'short' });
        const dateFormat = safe({ day: 'numeric', month: 'long', year: 'numeric' });
        const timeFormat = safe({ hour: 'numeric', minute: '2-digit' });

        let lastWritten: string | null = null;
        let reading = false;

        const read = (component: any, value: unknown): void => {
            const text = typeof value === 'string' ? value.trim() : '';
            reading = true;
            component.rule = parseRRule(text, start);
            component.active = !!text || !optional;
            reading = false;
            lastWritten = text;
            // An empty value without `optional` takes the rule on screen.
            if (!text && !optional) component.commit();
        };

        return {
            value: config.value ?? '',
            rule: defaultRule(start) as Rule,
            active: !optional,
            text: '',

            init(this: any) {
                read(this, this.value);

                this.$watch('value', (value: unknown) => {
                    const text = typeof value === 'string' ? value.trim() : '';
                    if (text === lastWritten) return;
                    read(this, value);
                });

                this.$watch('rule', () => {
                    if (!reading) this.commit();
                });

                // Picking an end date chooses the end by date.
                this.$watch('rule.until', (until: string | null) => {
                    if (!reading && until && this.rule.end !== 'until') this.rule.end = 'until';
                });
            },

            commit(this: any) {
                const value = this.active ? toRRule(this.rule, { time: withTime }) : '';
                if (value === lastWritten && this.value === value) return;
                lastWritten = value;
                this.value = value;
                this.$dispatch('recurrence-change', { value });
            },

            phrase(key: string): string {
                return phrases[key] ?? key;
            },

            get parsed(): Rule | null {
                const self = this as any;
                return self.text.trim() ? parseNaturalRule(self.text, self.rule, today) : null;
            },

            get naturalPreview(): string {
                const parsed = (this as any).parsed as Rule | null;
                return parsed ? describeRule(parsed, locale, { time: withTime, weekStart }) : '';
            },

            get summary(): string {
                const self = this as any;
                return self.active ? describeRule(self.rule, locale, { time: withTime, weekStart }) : self.phrase('noRepeat');
            },

            get rrule(): string {
                const self = this as any;
                return self.active ? toRRule(self.rule, { time: withTime }) : '';
            },

            get upcoming(): Array<{ key: string; weekday: string; date: string; time: string }> {
                const self = this as any;
                if (!self.active || !limit) return [];
                const time = withTime && self.rule.time ? self.rule.time.split(':').map(Number) : null;
                return occurrences(self.rule, start, limit, from).map((date) => ({
                    key: iso(date),
                    weekday: weekdayFormat.format(date).replace(/\.$/, ''),
                    date: dateFormat.format(date),
                    time: time ? timeFormat.format(new Date(2026, 0, 1, time[0], time[1])).replace(/ /g, ' ') : '',
                }));
            },

            get everyLabel(): string {
                const self = this as any;
                return self.phrase(`every_${self.rule.freq}`);
            },

            get unitLabel(): string {
                const self = this as any;
                return self.phrase(`${self.rule.freq}_${self.rule.interval === 1 ? 'one' : 'many'}`);
            },

            is(this: any, freq: Freq): boolean {
                return this.active && this.rule.freq === freq;
            },

            setFreq(this: any, freq: Freq) {
                if (freq === 'weekly' && !this.rule.days.length) this.rule.days = [start.getDay()];
                this.rule.freq = freq;
                if (!this.active) {
                    this.active = true;
                    this.commit();
                }
            },

            setNone(this: any) {
                this.active = false;
                this.commit();
            },

            stepInterval(this: any, delta: number) {
                this.rule.interval = Math.min(maxInterval, Math.max(1, this.rule.interval + delta));
            },

            hasDay(this: any, day: number): boolean {
                return this.rule.days.includes(day);
            },

            toggleDay(this: any, day: number) {
                const days: number[] = this.rule.days.slice();
                const at = days.indexOf(day);
                if (at >= 0) {
                    // A weekly rule keeps at least one day.
                    if (days.length > 1) days.splice(at, 1);
                } else {
                    days.push(day);
                }
                this.rule.days = days;
            },

            setMonthMode(this: any, mode: 'day' | 'nth') {
                this.rule.monthMode = mode;
            },

            setEnd(this: any, end: End) {
                if (end === 'until' && !this.rule.until) {
                    const later = new Date(from.getFullYear(), from.getMonth() + 3, from.getDate());
                    reading = true;
                    this.rule.until = iso(later);
                    reading = false;
                }
                this.rule.end = end;
            },

            stepCount(this: any, delta: number) {
                this.rule.count = Math.min(999, Math.max(1, this.rule.count + delta));
                if (this.rule.end !== 'count') this.rule.end = 'count';
            },

            applyNatural(this: any) {
                const parsed = this.parsed as Rule | null;
                if (!parsed) return;
                reading = true;
                this.rule = parsed;
                reading = false;
                this.active = true;
                this.text = '';
                this.commit();
            },

            suggest(this: any, text: string) {
                const parsed = parseNaturalRule(text, this.rule, today);
                if (!parsed) return;
                reading = true;
                this.rule = parsed;
                reading = false;
                this.active = true;
                this.commit();
            },
        };
    });
});

import { onAlpineInit } from '../../../runtime/alpine';

/**
 * Rating.
 *
 * The stars are drawn by the Blade; this follows the pointer for a preview, writes the note
 * on a click, and drives the group as a slider from the keyboard (arrows, Home, End, digits).
 * With `half`, the left half of a star gives half a point.
 */

interface RatingConfig {
    value?: number | null;
    max?: number;
    half?: boolean;
    clearable?: boolean;
    disabled?: boolean;
    labels?: string[];
    showValue?: boolean;
    locale?: string;
    outOf?: string;
}

onAlpineInit(() => {
    window.Alpine.data('neuraRating', (config: RatingConfig = {}) => {
        const max = Math.max(1, Number(config.max ?? 5));
        const unit = config.half ? 0.5 : 1;
        const labels = config.labels ?? [];
        let number: Intl.NumberFormat | null = null;
        try {
            number = new Intl.NumberFormat(config.locale || undefined, { maximumFractionDigits: 1 });
        } catch {
            number = null;
        }
        const show = (v: number) => (number ? number.format(v) : String(v));
        const clean = (v: unknown): number => {
            const n = Number(v);
            return Number.isFinite(n) ? Math.min(max, Math.max(0, Math.round(n / unit) * unit)) : 0;
        };

        return {
            value: config.value ?? 0,
            hover: null as number | null,

            init(this: any) {
                this.value = clean(this.value);
            },

            get shown(): number {
                const self = this as any;
                return self.hover ?? clean(self.value);
            },

            fill(this: any, index: number): Record<string, string> {
                const part = Math.min(1, Math.max(0, this.shown - (index - 1)));
                return { '--nk-rating-fill': `${part * 100}%` };
            },

            get caption(): string {
                const self = this as any;
                const v = self.shown;
                if (labels.length) return v > 0 ? labels[Math.min(labels.length, Math.ceil(v)) - 1] ?? '' : '';
                return config.showValue && v > 0 ? show(v) : '';
            },

            get valueText(): string {
                const self = this as any;
                const v = clean(self.value);
                const text = (config.outOf ?? '{value} out of {max}').replace('{value}', show(v)).replace('{max}', String(max));
                const word = labels.length && v > 0 ? labels[Math.min(labels.length, Math.ceil(v)) - 1] : '';
                return word ? `${text}, ${word}` : text;
            },

            at(index: number, event: PointerEvent | MouseEvent): number {
                if (!config.half) return index;
                const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
                const rtl = getComputedStyle(event.currentTarget as HTMLElement).direction === 'rtl';
                const left = rtl ? rect.right - event.clientX : event.clientX - rect.left;
                return left < rect.width / 2 ? index - 0.5 : index;
            },

            preview(this: any, index: number, event: PointerEvent) {
                if (config.disabled || event.pointerType === 'touch') return;
                this.hover = this.at(index, event);
            },

            pick(this: any, index: number, event: MouseEvent) {
                if (config.disabled) return;
                const picked = this.at(index, event);
                this.set(config.clearable !== false && picked === clean(this.value) ? 0 : picked);
                this.hover = null;
            },

            set(this: any, v: number) {
                const next = clean(v);
                if (next === clean(this.value)) return;
                this.value = next;
                this.$dispatch('rating-change', { value: next });
            },

            onKey(this: any, event: KeyboardEvent) {
                if (config.disabled) return;
                const current = clean(this.value);
                const keys: Record<string, number> = {
                    ArrowRight: current + unit,
                    ArrowUp: current + unit,
                    ArrowLeft: current - unit,
                    ArrowDown: current - unit,
                    Home: 0,
                    End: max,
                };
                let next: number | undefined = keys[event.key];
                if (next === undefined && /^[0-9]$/.test(event.key) && Number(event.key) <= max) next = Number(event.key);
                if (next === undefined) return;
                event.preventDefault();
                this.hover = null;
                this.set(next);
            },
        };
    });
});

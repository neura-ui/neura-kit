import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Activity rings.
 *
 * Concentric tick rings for a handful of daily goals.
 * A second, thinner lap traces the inside edge once a goal passes 100%.
 */

const SVG = 'http://www.w3.org/2000/svg';
const CX = 110;
const THICK = 11;
const GAP = 8;

interface Metric {
    id: string;
    label: string;
    unit: string;
    goal: number;
    color?: string | null;
}

interface Day {
    id: string;
    label: string;
    short: string;
    values: Record<string, number>;
}

interface Phrases {
    ofGoals: string;
    amount: string;
    spoken: string;
    week: string;
}

interface ActivityRingsConfig {
    metrics?: Metric[];
    days?: Day[];
    day?: string | null;
    focused?: string | null;
    label?: string;
    phrases?: Partial<Phrases>;
}

interface RingLayer {
    group: SVGGElement;
    main: SVGLineElement[];
    extra: SVGLineElement[];
}

function radii(count: number): number[] {
    const outer = 98;
    return Array.from({ length: count }, (_, index) => outer - index * (THICK + GAP));
}

function tickCount(radius: number): number {
    return Math.max(40, Math.round(radius * 0.74));
}

function line(radius: number, index: number, count: number, length: number): SVGLineElement {
    const angle = (index / count) * Math.PI * 2 - Math.PI / 2;
    const cos = Math.cos(angle);
    const sin = Math.sin(angle);
    const half = length / 2;
    const node = document.createElementNS(SVG, 'line');
    node.setAttribute('x1', String(CX + (radius - half) * cos));
    node.setAttribute('y1', String(CX + (radius - half) * sin));
    node.setAttribute('x2', String(CX + (radius + half) * cos));
    node.setAttribute('y2', String(CX + (radius + half) * sin));
    node.setAttribute('stroke-linecap', 'round');
    return node;
}

onAlpineInit(() => {
    window.Alpine.data('neuraActivityRings', (config: ActivityRingsConfig = {}) => ({
        metrics: (config.metrics ?? []).slice(0, 4),
        days: config.days ?? [],
        dayId: config.day || config.days?.[config.days.length - 1]?.id || '',
        focusedId: config.focused ?? null,
        hoverId: null as string | null,
        label: config.label || 'Activity',
        phrases: {
            ofGoals: config.phrases?.ofGoals || 'of goals',
            amount: config.phrases?.amount || '{value} / {goal} {unit}',
            spoken: config.phrases?.spoken || '{label}, {value} of {goal} {unit}, {percent} percent',
            week: config.phrases?.week || 'Days',
        } as Phrases,
        shown: [] as number[],
        announcement: '',
        layers: [] as RingLayer[],
        _raf: 0,

        init() {
            if (!this.days.some((day: Day) => day.id === this.dayId)) {
                this.dayId = this.days[this.days.length - 1]?.id || '';
            }
            this.shown = this.metrics.map(() => 0);
            this.$nextTick(() => {
                this.build();
                this.animateTo(this.valuesFor(this.dayId));
            });
        },

        colorOf(metric: Metric, index: number): string {
            const color = metric.color || '';
            if (!color) return `var(--ring-${index % 4})`;
            if (/^(#|okl|rgb|hsl|var\()/.test(color)) return color;
            return `var(--color-${color}-500, var(--ring-${index % 4}))`;
        },

        valuesFor(dayId: string): number[] {
            const day = this.days.find((item: Day) => item.id === dayId);
            return this.metrics.map((metric: Metric) => Number(day?.values?.[metric.id] ?? 0));
        },

        format(value: number): string {
            return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(Math.round(value));
        },

        percentOf(index: number): number {
            const goal = this.metrics[index]?.goal || 0;
            if (goal <= 0) return 0;
            return Math.round(((this.shown[index] || 0) / goal) * 100);
        },

        centerPercent(): number {
            if (!this.metrics.length) return 0;
            const total = this.metrics.reduce((sum: number, metric: Metric, index: number) => {
                const goal = metric.goal || 0;
                if (goal <= 0) return sum;
                return sum + Math.min(1, (this.shown[index] || 0) / goal);
            }, 0);
            return Math.round((total / this.metrics.length) * 100);
        },

        amountLabel(metric: Metric, index: number): string {
            return this.phrases.amount
                .replace('{value}', this.format(this.shown[index] || 0))
                .replace('{goal}', this.format(metric.goal || 0))
                .replace('{unit}', metric.unit);
        },

        spoken(metric: Metric, index: number): string {
            return this.phrases.spoken
                .replace('{label}', metric.label)
                .replace('{value}', this.format(this.shown[index] || 0))
                .replace('{goal}', this.format(metric.goal || 0))
                .replace('{unit}', metric.unit)
                .replace('{percent}', String(this.percentOf(index)));
        },

        settledLabel(): string {
            const values = this.valuesFor(this.dayId);
            const parts = this.metrics.map((metric: Metric, index: number) => {
                const goal = metric.goal || 0;
                const percent = goal > 0 ? Math.round((values[index] || 0) / goal * 100) : 0;
                return `${metric.label} ${percent}%`;
            });
            return `${this.label}. ${parts.join('. ')}`;
        },

        caption(): string {
            return this.metrics
                .map((metric: Metric, index: number) => `${this.format(this.shown[index] || 0)} ${metric.unit}`)
                .join(' · ');
        },

        isDim(id: string): boolean {
            const active = this.hoverId || this.focusedId;
            return Boolean(active && active !== id);
        },

        build() {
            const face = this.$refs.face as SVGSVGElement | undefined;
            const host = face?.querySelector('[data-rings]');
            if (!host) return;
            host.replaceChildren();
            const rings = radii(this.metrics.length);
            this.layers = this.metrics.map((metric: Metric, index: number) => {
                const radius = rings[index];
                const count = tickCount(radius);
                const color = this.colorOf(metric, index);
                const group = document.createElementNS(SVG, 'g');
                group.setAttribute('data-ring', metric.id);
                const main = Array.from({ length: count }, (_, tick) => {
                    const node = line(radius, tick, count, THICK * 0.78);
                    node.setAttribute('stroke', color);
                    node.setAttribute('stroke-width', '2.35');
                    group.appendChild(node);
                    return node;
                });
                const extra = Array.from({ length: count }, (_, tick) => {
                    const node = line(radius - 9, tick, count, 4.2);
                    node.setAttribute('stroke', color);
                    node.setAttribute('stroke-width', '1.7');
                    node.style.opacity = '0';
                    group.appendChild(node);
                    return node;
                });
                const hit = document.createElementNS(SVG, 'circle');
                hit.setAttribute('cx', String(CX));
                hit.setAttribute('cy', String(CX));
                hit.setAttribute('r', String(radius));
                hit.setAttribute('fill', 'none');
                hit.setAttribute('stroke', 'transparent');
                hit.setAttribute('stroke-width', String(THICK + 6));
                hit.style.pointerEvents = 'stroke';
                hit.style.cursor = 'pointer';
                hit.addEventListener('mouseenter', () => { this.hoverId = metric.id; });
                hit.addEventListener('mouseleave', () => { if (this.hoverId === metric.id) this.hoverId = null; });
                hit.addEventListener('click', () => this.toggleFocus(metric.id));
                group.appendChild(hit);
                host.appendChild(group);
                return { group, main, extra };
            });
            this.paint();
        },

        paint() {
            this.metrics.forEach((metric: Metric, index: number) => {
                const layer = this.layers[index];
                if (!layer) return;
                const goal = metric.goal || 0;
                const ratio = goal > 0 ? (this.shown[index] || 0) / goal : 0;
                const lap = Math.min(1, Math.max(0, ratio));
                const overflow = Math.max(0, ratio - 1);
                const lit = lap * layer.main.length;
                layer.main.forEach((node, tick) => {
                    const local = lit - tick;
                    node.style.opacity = local >= 1 ? '1' : local > 0 ? String(0.16 + 0.84 * local) : '0.16';
                });
                const extraLit = overflow * layer.extra.length;
                layer.extra.forEach((node, tick) => {
                    const local = extraLit - tick;
                    node.style.opacity = local >= 1 ? '0.95' : local > 0 ? String(0.95 * local) : '0';
                });
            });
            this.applyDim();
        },

        applyDim() {
            const active = this.hoverId || this.focusedId;
            this.layers.forEach((layer, index) => {
                const id = this.metrics[index]?.id;
                layer.group.style.opacity = !active || active === id ? '1' : '0.2';
            });
        },

        animateTo(targets: number[]) {
            if (this._raf) cancelAnimationFrame(this._raf);
            const from = this.metrics.map((_: Metric, index: number) => this.shown[index] || 0);
            if (prefersReducedMotion()) {
                this.shown = targets.slice();
                this.paint();
                return;
            }
            const started = performance.now();
            const duration = 720;
            const frame = (now: number) => {
                const progress = Math.min(1, (now - started) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                this.shown = from.map((value, index) => value + ((targets[index] || 0) - value) * eased);
                this.paint();
                this._raf = progress < 1 ? requestAnimationFrame(frame) : 0;
            };
            this._raf = requestAnimationFrame(frame);
        },

        selectDay(id: string) {
            if (!this.days.some((day: Day) => day.id === id) || id === this.dayId) return;
            this.dayId = id;
            this.animateTo(this.valuesFor(id));
            this.announcement = this.settledLabel();
            this.$dispatch('activity-rings-day', { day: id });
            this.$nextTick(() => {
                const current = this.$root.querySelector('[data-day][aria-checked="true"]') as HTMLElement | null;
                current?.focus();
            });
        },

        onWeekKey(event: KeyboardEvent) {
            const order = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End'];
            if (!order.includes(event.key) || !this.days.length) return;
            event.preventDefault();
            let index = this.days.findIndex((day: Day) => day.id === this.dayId);
            if (index < 0) index = 0;
            if (event.key === 'Home') index = 0;
            else if (event.key === 'End') index = this.days.length - 1;
            else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') index = (index + 1) % this.days.length;
            else index = (index - 1 + this.days.length) % this.days.length;
            this.selectDay(this.days[index].id);
        },

        toggleFocus(id: string) {
            this.focusedId = this.focusedId === id ? null : id;
            this.hoverId = null;
            this.applyDim();
            this.$dispatch('activity-rings-focus', { metric: this.focusedId });
        },
    }));
});

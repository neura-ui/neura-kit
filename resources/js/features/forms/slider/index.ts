import { onAlpineInit } from '../../../runtime/alpine';
import { constrain, format, fromPosition, fromSlot, normalize, snap, toPosition, toSlot } from './model';
import type { Scale } from './model';

/**
 * Slider and range slider.
 *
 * The thumbs are native range inputs (keyboard, screen readers and touch for free) laid over
 * a drawn rail; the rail itself takes a press or a drag and moves the nearest thumb. The value
 * is written when a thumb is released or stepped with the keyboard, or on every move with
 * `live`, so a deferred wire:model stays quiet while the thumb travels.
 */

interface SliderConfig {
    value?: unknown;
    min?: number;
    max?: number;
    step?: number;
    steps?: number[] | null;
    range?: boolean;
    minGap?: number;
    live?: boolean;
    disabled?: boolean;
    locale?: string;
    decimals?: number | null;
    prefix?: string;
    suffix?: string;
}

onAlpineInit(() => {
    window.Alpine.data('neuraSlider', (config: SliderConfig = {}) => {
        const steps = Array.isArray(config.steps) && config.steps.length ? config.steps.map(Number) : null;
        const scale: Scale = {
            min: steps ? steps[0] : Number(config.min ?? 0),
            max: steps ? steps[steps.length - 1] : Number(config.max ?? 100),
            step: Number(config.step) > 0 ? Number(config.step) : 1,
            steps,
        };
        const range = !!config.range;
        const gap = Math.max(0, Number(config.minGap ?? 0));
        const formatting = {
            locale: config.locale,
            decimals: config.decimals ?? null,
            prefix: config.prefix ?? '',
            suffix: config.suffix ?? '',
        };
        const decimals = formatting.decimals ?? Math.max(
            ...(steps ?? [scale.min, scale.step]).map((v) => (String(v).split('.')[1] ?? '').length),
        );

        let lastWritten = '';
        let drag: { pointer: number; thumb: number } | null = null;

        return {
            value: config.value ?? null,
            ends: normalize(config.value, scale, range),
            active: -1,
            last: 1,

            init(this: any) {
                // A bound value has arrived through x-modelable by now.
                this.ends = normalize(this.value, scale, range);
                lastWritten = JSON.stringify(this.value);

                this.$watch('value', (value: unknown) => {
                    const text = JSON.stringify(value);
                    if (text === lastWritten) return;
                    lastWritten = text;
                    this.ends = normalize(value, scale, range);
                });
            },

            output(this: any): number | number[] {
                return range ? [this.ends[0], this.ends[1]] : this.ends[0];
            },

            commit(this: any) {
                const value = this.output();
                const text = JSON.stringify(value);
                if (text === lastWritten) return;
                lastWritten = text;
                this.value = value;
                this.$dispatch('slider-change', { value });
            },

            position(this: any, index: number): number {
                return toPosition(this.ends[index] ?? scale.min, scale);
            },

            slot(this: any, index: number): number {
                return toSlot(this.ends[index] ?? scale.min, scale);
            },

            text(this: any, index: number): string {
                return format(this.ends[index] ?? scale.min, { ...formatting, decimals });
            },

            get valueText(): string {
                const self = this as any;
                return range ? `${self.text(0)} – ${self.text(1)}` : self.text(0);
            },

            /** Fill and thumb positions, set on the body inside wire:ignore. */
            get vars(): Record<string, string> {
                const self = this as any;
                return {
                    '--nk-slider-from': String(range ? self.position(0) : 0),
                    '--nk-slider-to': String(range ? self.position(1) : self.position(0)),
                };
            },

            at(this: any, index: number): Record<string, string> {
                return { '--nk-slider-at': String(this.position(index)) };
            },

            /** Which thumb sits on top: the last moved, or the one that can still move when they meet. */
            top(this: any, index: number): boolean {
                if (!range) return true;
                const [a, b] = [this.position(0), this.position(1)];
                const thumb = b - a < 0.02 ? (a > 0.5 ? 0 : 1) : this.last;
                return thumb === index;
            },

            /** A tick, a bar or a mark label inside the selection. */
            within(this: any, position: number): boolean {
                const from = range ? this.position(0) : 0;
                const to = range ? this.position(1) : this.position(0);
                return position >= from - 1e-9 && position <= to + 1e-9;
            },

            set(this: any, index: number, value: number) {
                const next = this.ends.slice();
                next[index] = value;
                this.ends = range ? constrain(next, index, gap, scale) : next;
                this.last = index;
            },

            onInput(this: any, index: number, event: Event) {
                const input = event.target as HTMLInputElement;
                this.set(index, fromSlot(Number(input.value), scale));
                // A thumb held back by the other one stays where it stopped.
                input.value = String(this.slot(index));
                if (config.live) this.commit();
            },

            onField(this: any, index: number, event: Event) {
                const input = event.target as HTMLInputElement;
                const typed = Number(String(input.value).replace(',', '.').replace(/\s/g, ''));
                if (input.value !== '' && Number.isFinite(typed)) {
                    this.set(index, snap(typed, scale));
                    this.commit();
                }
                input.value = String(this.ends[index]);
            },

            /** A press on the rail moves the nearest thumb there, and drags it until release. */
            trackDown(this: any, event: PointerEvent) {
                if (config.disabled || event.button !== 0) return;
                if ((event.target as HTMLElement).closest('input')) return;
                const track = event.currentTarget as HTMLElement;
                const value = this.valueAt(track, event.clientX);
                let thumb = 0;
                if (range) {
                    if (value >= this.ends[1]) thumb = 1;
                    else if (value > this.ends[0]) thumb = Math.abs(value - this.ends[0]) <= Math.abs(this.ends[1] - value) ? 0 : 1;
                }
                event.preventDefault();
                drag = { pointer: event.pointerId, thumb };
                track.setPointerCapture?.(event.pointerId);
                this.active = thumb;
                this.set(thumb, value);
                track.querySelectorAll<HTMLInputElement>('input[type="range"]')[thumb]?.focus({ preventScroll: true });
                if (config.live) this.commit();
            },

            trackMove(this: any, event: PointerEvent) {
                if (!drag || event.pointerId !== drag.pointer) return;
                this.set(drag.thumb, this.valueAt(event.currentTarget as HTMLElement, event.clientX));
                if (config.live) this.commit();
            },

            trackUp(this: any, event: PointerEvent) {
                if (!drag || event.pointerId !== drag.pointer) return;
                drag = null;
                this.commit();
            },

            valueAt(track: HTMLElement, clientX: number): number {
                const rect = track.getBoundingClientRect();
                // The track is as tall as a thumb, and a thumb's centre stops half a thumb from each end.
                const thumb = track.clientHeight;
                const span = Math.max(1, rect.width - thumb);
                return fromPosition((clientX - rect.left - thumb / 2) / span, scale);
            },
        };
    });
});

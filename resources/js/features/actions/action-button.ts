import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Async action button.
 *
 * Idle shows a trailing arrow. Press runs one action, morphs the label
 * through pending and success, then returns to idle. Pending stays
 * focusable: aria-disabled, not the disabled attribute.
 */

interface Glyph {
    id: string;
    char: string;
    order: number;
    fresh: boolean;
}

export interface ActionButtonConfig {
    label?: string;
    pendingLabel?: string;
    successLabel?: string;
    resetAfter?: number;
    method?: string | null;
    delay?: number;
}

type Phase = 'idle' | 'pending' | 'success';

function toGlyphs(chars: string[], seq: number): Glyph[] {
    return chars.map((char, order) => ({
        id: `${seq}:${order}`,
        char,
        order,
        fresh: seq > 0,
    }));
}

/** Shared edges keep their identity so only the changed run is replaced. */
function morphGlyphs(prevGlyphs: Glyph[], prevText: string, nextText: string, seq: number): Glyph[] {
    const prev = [...prevText];
    const next = [...nextText];
    let start = 0;
    let end = 0;

    while (start < prev.length && start < next.length && prev[start] === next[start]) start += 1;
    while (
        end < prev.length - start &&
        end < next.length - start &&
        prev[prev.length - 1 - end] === next[next.length - 1 - end]
    ) {
        end += 1;
    }

    if (start < 2) start = 0;
    if (end < 2) end = 0;

    return [
        ...prevGlyphs.slice(0, start),
        ...toGlyphs(next.slice(start, next.length - end), seq),
        ...prevGlyphs.slice(prevGlyphs.length - end),
    ];
}

function springEase(): string {
    const value = getComputedStyle(document.documentElement).getPropertyValue('--nk-ease-spring').trim();
    return value || 'cubic-bezier(0.22, 1, 0.36, 1)';
}

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraActionButton', (config: ActionButtonConfig = {}) => ({
            label: config.label ?? 'Save',
            pendingLabel: config.pendingLabel ?? 'Saving',
            successLabel: config.successLabel ?? 'Saved',
            resetAfter: config.resetAfter ?? 2400,
            method: config.method ?? null,
            delay: config.delay ?? 800,
            state: 'idle' as Phase,
            text: config.label ?? 'Save',
            seq: 0,
            glyphs: toGlyphs([...(config.label ?? 'Save')], 0),
            announcement: '',
            armedUntil: 0,
            resetTimer: null as number | null,
            observer: null as ResizeObserver | null,
            widthAnim: null as Animation | null,
            flipFrom: new Map<string, number>(),
            measured: false,

            init(): void {
                const row = this.$refs.glyphs as HTMLElement | undefined;
                const morph = this.$refs.morph as HTMLElement | undefined;
                if (!row || !morph || typeof ResizeObserver === 'undefined') return;

                this.observer = new ResizeObserver(([entry]) => {
                    const next = entry?.contentRect.width ?? 0;
                    if (!next) return;

                    const reduced = prefersReducedMotion();
                    const armed = performance.now() < this.armedUntil;
                    if (!this.measured || reduced || !armed) {
                        this.measured = true;
                        this.jumpWidth(morph, next);
                        return;
                    }

                    this.springWidth(morph, next);
                });
                this.observer.observe(row);
            },

            destroy(): void {
                if (this.resetTimer !== null) window.clearTimeout(this.resetTimer);
                this.observer?.disconnect();
                this.widthAnim?.cancel();
            },

            markWide(): void {
                const button = this.$el as HTMLElement;
                if (button.offsetWidth > 220) button.dataset.wide = '';
                else delete button.dataset.wide;
            },

            jumpWidth(morph: HTMLElement, next: number): void {
                this.widthAnim?.cancel();
                morph.style.width = `${next}px`;
                delete morph.dataset.morphing;
                this.markWide();
            },

            springWidth(morph: HTMLElement, next: number): void {
                const current = morph.getBoundingClientRect().width;
                if (Math.abs(current - next) < 0.5) {
                    morph.style.width = `${next}px`;
                    delete morph.dataset.morphing;
                    return;
                }

                this.widthAnim?.cancel();
                morph.dataset.morphing = '';
                morph.style.width = `${current}px`;
                const anim = morph.animate(
                    [{ width: `${current}px` }, { width: `${next}px` }],
                    { duration: 420, easing: springEase(), fill: 'forwards' },
                );
                this.widthAnim = anim;
                anim.onfinish = () => {
                    morph.style.width = `${next}px`;
                    anim.cancel();
                    delete morph.dataset.morphing;
                    this.markWide();
                };
            },

            labelFor(phase: Phase): string {
                if (phase === 'pending') return this.pendingLabel;
                if (phase === 'success') return this.successLabel;
                return this.label;
            },

            syncText(): void {
                const next = this.labelFor(this.state);
                this.announcement = this.state === 'idle' ? '' : next;
                if (next === this.text) return;

                this.armedUntil = performance.now() + 700;
                this.captureFlip();
                this.seq += 1;
                this.glyphs = morphGlyphs(this.glyphs, this.text, next, this.seq);
                this.text = next;
                this.$nextTick(() => this.playFlip());
            },

            captureFlip(): void {
                const row = this.$refs.glyphs as HTMLElement | undefined;
                this.flipFrom = new Map();
                if (!row) return;
                for (const el of row.querySelectorAll<HTMLElement>('[data-glyph]')) {
                    this.flipFrom.set(el.dataset.glyph ?? '', el.getBoundingClientRect().left);
                }
            },

            playFlip(): void {
                if (prefersReducedMotion()) return;
                const row = this.$refs.glyphs as HTMLElement | undefined;
                if (!row) return;

                let easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
                try {
                    easing = springEase();
                } catch {
                    easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
                }

                for (const el of row.querySelectorAll<HTMLElement>('[data-glyph]')) {
                    const prev = this.flipFrom.get(el.dataset.glyph ?? '');
                    if (prev === undefined) continue;
                    const dx = prev - el.getBoundingClientRect().left;
                    if (Math.abs(dx) < 0.5) continue;
                    el.animate(
                        [{ transform: `translateX(${dx}px)` }, { transform: 'translateX(0px)' }],
                        { duration: 420, easing },
                    );
                }
            },

            async run(): Promise<void> {
                if (this.state === 'pending' || (this.$el as HTMLButtonElement).disabled) return;
                if (this.resetTimer !== null) window.clearTimeout(this.resetTimer);

                this.state = 'pending';
                this.syncText();

                try {
                    await this.perform();
                    this.state = 'success';
                    this.syncText();
                    if (this.resetAfter > 0) {
                        this.resetTimer = window.setTimeout(() => {
                            this.state = 'idle';
                            this.syncText();
                        }, this.resetAfter);
                    }
                } catch (error) {
                    this.state = 'idle';
                    this.syncText();
                    this.$dispatch('action-error', { message: error instanceof Error ? error.message : String(error) });
                }
            },

            async perform(): Promise<void> {
                const wire = (this as unknown as { $wire?: Record<string, unknown> }).$wire;
                if (this.method && wire) {
                    const direct = wire[this.method];
                    if (typeof direct === 'function') {
                        await (direct as () => Promise<unknown>)();
                        return;
                    }
                    const call = wire.$call ?? wire.call;
                    if (typeof call === 'function') {
                        await (call as (name: string) => Promise<unknown>).call(wire, this.method);
                        return;
                    }
                }

                await new Promise((resolve) => window.setTimeout(resolve, this.delay));
            },
        }));
    });
}

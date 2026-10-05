import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Split button.
 *
 * The main half morphs its label (and icon) in place while its width springs.
 * The chevron never scales, so the menu opens from a still anchor.
 */

interface Glyph {
    id: string;
    char: string;
    order: number;
    fresh: boolean;
}

export interface SplitAction {
    label: string;
    method?: string | null;
    disabled?: boolean;
    color?: string | null;
    icon?: string | null;
    swap?: boolean;
    pendingLabel?: string | null;
    successLabel?: string | null;
    doneLabel?: string | null;
    resetAfter?: number | null;
}

interface SplitPhase {
    pendingLabel: string | null;
    successLabel: string | null;
    resetAfter: number;
}

type Phase = 'idle' | 'pending' | 'success';

export interface SplitButtonConfig {
    label?: string;
    method?: string | null;
    icon?: string | null;
    actions?: SplitAction[];
    pendingLabel?: string | null;
    successLabel?: string | null;
    doneLabel?: string | null;
    resetAfter?: number;
    delay?: number;
    disabled?: boolean;
}

function toGlyphs(chars: string[], seq: number): Glyph[] {
    return chars.map((char, order) => ({
        id: `${seq}:${order}`,
        char,
        order,
        fresh: seq > 0,
    }));
}

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
        window.Alpine.data('neuraSplitButton', (config: SplitButtonConfig = {}) => ({
            label: config.label ?? 'Merge',
            method: config.method ?? null,
            icon: config.icon ?? null,
            actions: config.actions ?? [],
            basePending: config.pendingLabel || null,
            baseSuccess: config.successLabel || config.doneLabel || null,
            baseReset: config.resetAfter ?? 2400,
            pendingLabel: config.pendingLabel || null,
            successLabel: config.successLabel || config.doneLabel || null,
            resetAfter: config.resetAfter ?? 2400,
            delay: config.delay ?? 800,
            disabled: config.disabled ?? false,
            state: 'idle' as Phase,
            open: false,
            text: config.label ?? 'Merge',
            seq: 0,
            glyphs: toGlyphs([...(config.label ?? 'Merge')], 0),
            announcement: '',
            armedUntil: 0,
            resetTimer: null as number | null,
            observer: null as ResizeObserver | null,
            widthAnim: null as Animation | null,
            flipFrom: new Map<string, number>(),
            measured: false,

            init(): void {
                const row = this.$refs.content as HTMLElement | undefined;
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

            jumpWidth(morph: HTMLElement, next: number): void {
                this.widthAnim?.cancel();
                morph.style.width = `${next}px`;
                delete morph.dataset.morphing;
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
                };
            },

            labelFor(phase: Phase): string {
                if (phase === 'pending' && this.pendingLabel) return this.pendingLabel;
                if (phase === 'success' && this.successLabel) return this.successLabel;
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

            setLabel(next: string): void {
                this.label = next;
                this.syncText();
            },

            setIcon(name: string | null): void {
                if (!name || name === this.icon) return;
                this.armedUntil = performance.now() + 700;
                this.icon = name;
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

                const easing = springEase();
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

            items(): HTMLButtonElement[] {
                const menu = this.$refs.menu as HTMLElement | undefined;
                if (!menu) return [];
                return [...menu.querySelectorAll<HTMLButtonElement>('[role="menuitem"]:not([disabled])')];
            },

            focusEdge(edge: 'first' | 'last'): void {
                const items = this.items();
                const target = edge === 'last' ? items[items.length - 1] : items[0];
                target?.focus();
            },

            focusMove(delta: number): void {
                const items = this.items();
                if (!items.length) return;
                const current = items.indexOf(document.activeElement as HTMLButtonElement);
                const next = current === -1
                    ? (delta > 0 ? 0 : items.length - 1)
                    : (current + delta + items.length) % items.length;
                items[next]?.focus();
            },

            openMenu(focus: 'first' | 'last' | false = false): void {
                if (this.disabled || this.state === 'pending') return;
                this.open = true;
                if (focus) this.$nextTick(() => this.focusEdge(focus));
            },

            closeMenu(restore = false): void {
                if (!this.open) return;
                this.open = false;
                if (restore) (this.$refs.trigger as HTMLElement | undefined)?.focus();
            },

            onTriggerKey(event: KeyboardEvent): void {
                if (this.disabled) return;
                const key = event.key;
                if (key === 'ArrowDown' || key === 'ArrowUp') {
                    event.preventDefault();
                    this.openMenu(key === 'ArrowUp' ? 'last' : 'first');
                    return;
                }
                if (key === 'Enter' || key === ' ') {
                    event.preventDefault();
                    if (this.open) this.closeMenu(false);
                    else this.openMenu('first');
                }
            },

            async perform(method: string | null | undefined): Promise<void> {
                const wire = (this as unknown as { $wire?: Record<string, unknown> }).$wire;
                if (method && wire) {
                    const direct = wire[method];
                    if (typeof direct === 'function') {
                        await (direct as () => Promise<unknown>)();
                        return;
                    }
                    const call = wire.$call ?? wire.call;
                    if (typeof call === 'function') {
                        await (call as (name: string) => Promise<unknown>).call(wire, method);
                        return;
                    }
                }

                await new Promise((resolve) => window.setTimeout(resolve, this.delay));
            },

            async run(method?: string | null, phase?: SplitPhase | null): Promise<void> {
                if (this.state === 'pending' || this.disabled) return;
                if (this.resetTimer !== null) window.clearTimeout(this.resetTimer);
                this.closeMenu(false);
                this.pendingLabel = phase?.pendingLabel ?? this.basePending;
                this.successLabel = phase?.successLabel ?? this.baseSuccess;
                this.resetAfter = phase?.resetAfter ?? this.baseReset;

                const back = this.label;
                this.armedUntil = performance.now() + 700;
                this.state = 'pending';
                this.syncText();

                try {
                    await this.perform(method ?? this.method);
                    this.armedUntil = performance.now() + 700;
                    this.state = 'success';
                    this.syncText();
                    if (this.resetAfter > 0) {
                        this.resetTimer = window.setTimeout(() => {
                            this.label = back;
                            this.armedUntil = performance.now() + 700;
                            this.state = 'idle';
                            this.syncText();
                        }, this.resetAfter);
                    }
                } catch (error) {
                    this.label = back;
                    this.state = 'idle';
                    this.syncText();
                    this.$dispatch('action-error', { message: error instanceof Error ? error.message : String(error) });
                }
            },

            choose(action: SplitAction): void {
                if (this.disabled || this.state === 'pending' || action.disabled) return;
                this.closeMenu(true);
                if (action.icon) this.setIcon(action.icon);
                if (action.swap !== false && action.label) this.label = action.label;
                if (action.method) {
                    void this.run(action.method, {
                        pendingLabel: action.pendingLabel || this.basePending,
                        successLabel: action.successLabel || action.doneLabel || this.baseSuccess,
                        resetAfter: action.resetAfter ?? this.baseReset,
                    });
                    return;
                }
                if (action.swap !== false && action.label) this.setLabel(action.label);
            },
        }));
    });
}

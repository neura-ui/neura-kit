import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Expanding button group.
 *
 * One action always shows its label. Pointer or keyboard focus moves that
 * label; neighbours slide aside on the morph spring. A finished action draws
 * a check and swaps the word in place.
 */

const MORPH = 'linear(0, 0.16 12%, 0.4 24%, 0.65 40%, 0.84 56%, 0.96 72%, 1.03 86%, 1)';
const HYSTERESIS = 4;

interface ExpandingConfig {
    defaultExpanded?: string | null;
    confirmDuration?: number;
}

interface Metrics {
    h: number;
    icon: number;
    start: number;
    gap: number;
    end: number;
}

const FALLBACK: Metrics = { h: 40, icon: 18, start: 11, gap: 7, end: 14 };

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraExpandingGroup', (config: ExpandingConfig = {}) => ({
            defaultExpanded: config.defaultExpanded || null,
            confirmDuration: config.confirmDuration ?? 1400,
            intent: null as string | null,
            focused: null as string | null,
            doneId: null as string | null,
            busyId: null as string | null,
            announcement: '',
            pointerOver: null as string | null,
            pointerAt: null as { x: number; y: number } | null,
            keyFocus: null as string | null,
            touchOpen: null as string | null,
            press: null as { id: string; kind: string; wasExpanded: boolean } | null,
            timer: null as number | null,
            seq: 0,
            outside: null as ((event: PointerEvent) => void) | null,

            init(): void {
                this.$nextTick(() => this.syncWidths(true));
                this.outside = (event: PointerEvent) => {
                    if (!this.touchOpen || this.$root.contains(event.target as Node)) return;
                    this.touchOpen = null;
                    this.intent = this.doneId ?? this.keyFocus;
                    this.syncWidths(false);
                };
                document.addEventListener('pointerdown', this.outside, true);
            },

            destroy(): void {
                if (this.timer !== null) window.clearTimeout(this.timer);
                if (this.outside) document.removeEventListener('pointerdown', this.outside, true);
            },

            metrics(): Metrics {
                const styles = getComputedStyle(this.$root as HTMLElement);
                const read = (name: string, fallback: number) => {
                    const value = parseFloat(styles.getPropertyValue(name));
                    return Number.isFinite(value) ? value : fallback;
                };
                return {
                    h: read('--nk-ebg-h', FALLBACK.h),
                    icon: read('--nk-ebg-icon', FALLBACK.icon),
                    start: read('--nk-ebg-start', FALLBACK.start),
                    gap: read('--nk-ebg-gap', FALLBACK.gap),
                    end: read('--nk-ebg-end', FALLBACK.end),
                };
            },

            buttons(): HTMLButtonElement[] {
                return [...(this.$root as HTMLElement).querySelectorAll<HTMLButtonElement>('[data-ebg-item]')];
            },

            restId(): string | null {
                const buttons = this.buttons();
                if (this.defaultExpanded && buttons.some((button) => button.dataset.id === this.defaultExpanded)) {
                    return this.defaultExpanded;
                }
                return buttons.find((button) => button.getAttribute('aria-disabled') !== 'true')?.dataset.id
                    ?? buttons[0]?.dataset.id
                    ?? null;
            },

            expandedId(): string | null {
                if (this.intent && this.buttons().some((button) => button.dataset.id === this.intent)) return this.intent;
                return this.restId();
            },

            tabStop(): string | null {
                if (this.focused && this.buttons().some((button) => button.dataset.id === this.focused)) return this.focused;
                return this.restId();
            },

            syncWidths(jump: boolean): void {
                const reduced = prefersReducedMotion();
                const metrics = this.metrics();
                const expanded = this.expandedId();
                const tab = this.tabStop();
                for (const button of this.buttons()) {
                    const open = button.dataset.id === expanded;
                    const sizer = button.querySelector<HTMLElement>('[data-sizer]');
                    const text = sizer?.offsetWidth ?? 0;
                    const target = open && text > 0
                        ? metrics.start + metrics.icon + metrics.gap + text + metrics.end
                        : metrics.h;
                    this.glide(button, target, jump || reduced || button.dataset.ready !== '1');
                    button.dataset.ready = '1';
                    if (open) button.setAttribute('data-expanded', '');
                    else button.removeAttribute('data-expanded');
                    button.tabIndex = button.dataset.id === tab ? 0 : -1;
                }
            },

            glide(button: HTMLButtonElement, target: number, jump: boolean): void {
                const from = button.getBoundingClientRect().width || target;
                const anim = (button as HTMLButtonElement & { _nkAnim?: Animation })._nkAnim;
                if (jump || Math.abs(from - target) < 0.5) {
                    anim?.cancel();
                    button.style.width = `${target}px`;
                    return;
                }
                const current = getComputedStyle(button).width;
                anim?.cancel();
                const next = button.animate(
                    [{ width: current }, { width: `${target}px` }],
                    { duration: 420, easing: MORPH, fill: 'forwards' },
                );
                (button as HTMLButtonElement & { _nkAnim?: Animation })._nkAnim = next;
                next.onfinish = () => {
                    button.style.width = `${target}px`;
                    next.cancel();
                };
            },

            showGlyph(button: HTMLButtonElement, name: 'icon' | 'check' | 'busy'): void {
                button.querySelectorAll<HTMLElement>('[data-glyph]').forEach((glyph) => {
                    if (glyph.dataset.glyph === name) glyph.setAttribute('data-on', '');
                    else glyph.removeAttribute('data-on');
                });
            },

            showWord(button: HTMLButtonElement, name: 'rest' | 'done'): void {
                button.querySelectorAll<HTMLElement>('[data-word]').forEach((word) => {
                    if (word.dataset.word === name) word.setAttribute('data-on', '');
                    else word.removeAttribute('data-on');
                });
            },

            confirm(button: HTMLButtonElement): void {
                if (!button.dataset.done) return;
                if (this.timer !== null) window.clearTimeout(this.timer);
                this.seq += 1;
                this.doneId = button.dataset.id ?? null;
                this.intent = this.doneId;
                button.dataset.state = 'done';
                this.showGlyph(button, 'check');
                this.showWord(button, 'done');
                this.announcement = `${button.dataset.done}${this.seq % 2 ? '' : '\u200b'}`;
                this.syncWidths(false);
                const id = this.doneId;
                this.timer = window.setTimeout(() => {
                    this.doneId = null;
                    button.dataset.state = 'idle';
                    this.showGlyph(button, 'icon');
                    this.showWord(button, 'rest');
                    if (this.pointerOver !== id && this.keyFocus !== id) {
                        if (this.touchOpen === id) this.touchOpen = null;
                        if (this.intent === id) this.intent = this.pointerOver ?? this.keyFocus;
                    }
                    this.syncWidths(false);
                }, this.confirmDuration);
            },

            async call(method: string): Promise<void> {
                const wire = (this as unknown as { $wire?: Record<string, unknown> }).$wire;
                if (!wire) {
                    await new Promise((resolve) => window.setTimeout(resolve, 420));
                    return;
                }
                const direct = wire[method];
                if (typeof direct === 'function') {
                    await (direct as () => Promise<unknown>)();
                    return;
                }
                const call = wire.$call ?? wire.call;
                if (typeof call === 'function') {
                    await (call as (name: string) => Promise<unknown>).call(wire, method);
                }
            },

            async activate(button: HTMLButtonElement): Promise<void> {
                const id = button.dataset.id ?? '';
                if (button.getAttribute('aria-disabled') === 'true' || this.busyId === id || this.doneId === id) return;
                this.intent = id;
                this.syncWidths(false);
                const method = button.dataset.method || '';
                if (method) {
                    this.busyId = id;
                    button.setAttribute('aria-busy', 'true');
                    this.showGlyph(button, 'busy');
                    try {
                        await this.call(method);
                    } catch {
                        this.busyId = null;
                        button.removeAttribute('aria-busy');
                        this.showGlyph(button, 'icon');
                        return;
                    }
                    this.busyId = null;
                    button.removeAttribute('aria-busy');
                    this.showGlyph(button, 'icon');
                }
                this.confirm(button);
            },

            notePress(event: PointerEvent): void {
                const button = (event.currentTarget as HTMLElement).closest<HTMLButtonElement>('[data-ebg-item]');
                if (!button) return;
                this.press = {
                    id: button.dataset.id ?? '',
                    kind: event.pointerType,
                    wasExpanded: this.expandedId() === button.dataset.id,
                };
            },

            onItemClick(event: MouseEvent): void {
                const button = (event.currentTarget as HTMLElement).closest<HTMLButtonElement>('[data-ebg-item]');
                if (!button) return;
                const last = this.press;
                this.press = null;
                if (last && last.id === button.dataset.id && last.kind === 'touch' && !last.wasExpanded) {
                    this.touchOpen = button.dataset.id ?? null;
                    this.intent = this.touchOpen;
                    this.syncWidths(false);
                    return;
                }
                if (last?.kind === 'touch') this.touchOpen = button.dataset.id ?? null;
                void this.activate(button);
            },

            noteFocus(event: FocusEvent): void {
                const button = event.currentTarget as HTMLButtonElement;
                this.focused = button.dataset.id ?? null;
                let visible = true;
                try { visible = button.matches(':focus-visible'); } catch { visible = true; }
                if (!visible) {
                    this.syncWidths(false);
                    return;
                }
                this.keyFocus = button.dataset.id ?? null;
                this.intent = this.keyFocus;
                this.syncWidths(false);
            },

            onFocusOut(event: FocusEvent): void {
                const next = event.relatedTarget as Node | null;
                if (next && (this.$root as HTMLElement).contains(next)) return;
                this.keyFocus = null;
                this.focused = null;
                this.touchOpen = null;
                this.intent = this.pointerOver ?? this.doneId;
                this.syncWidths(false);
            },

            onPointerMove(event: PointerEvent): void {
                if (event.pointerType !== 'mouse') return;
                const at = this.pointerAt;
                if (at && Math.abs(at.x - event.clientX) < 1 && Math.abs(at.y - event.clientY) < 1) return;
                this.pointerAt = { x: event.clientX, y: event.clientY };
                const button = (event.target as HTMLElement).closest<HTMLElement>('[data-ebg-item]');
                const id = button?.dataset.id;
                if (!button || !id || id === this.pointerOver) return;
                if (this.pointerOver) {
                    const box = button.getBoundingClientRect();
                    if (event.clientX < box.left + HYSTERESIS || event.clientX > box.right - HYSTERESIS) return;
                }
                this.pointerOver = id;
                this.intent = id;
                this.syncWidths(false);
            },

            onPointerLeave(event: PointerEvent): void {
                if (event.pointerType !== 'mouse') return;
                this.pointerAt = null;
                this.pointerOver = null;
                this.intent = this.doneId ?? this.keyFocus;
                this.syncWidths(false);
            },

            onKeyDown(event: KeyboardEvent): void {
                const list = this.buttons();
                const index = list.findIndex((button) => button === document.activeElement);
                if (index < 0) return;
                const last = list.length - 1;
                const target = event.key === 'ArrowRight' ? (index === last ? 0 : index + 1)
                    : event.key === 'ArrowLeft' ? (index === 0 ? last : index - 1)
                        : event.key === 'Home' ? 0
                            : event.key === 'End' ? last : -1;
                if (target < 0) return;
                event.preventDefault();
                list[target]?.focus();
            },
        }));
    });
}

import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Confirm morph.
 *
 * One pill asks in place, waits, then shows the result with undo. The surface
 * width springs between faces, and each face slides in the direction of the step.
 */

type Face = 'idle' | 'confirming' | 'pending' | 'done' | 'error';

const GROW = 'linear(0, 0.14 10%, 0.36 22%, 0.6 38%, 0.8 54%, 0.94 70%, 1.04 84%, 1)';
const SHRINK = 'cubic-bezier(0.22, 1, 0.36, 1)';
const SLIDE = 'cubic-bezier(0.22, 1, 0.36, 1)';

interface ConfirmConfig {
    method?: string | null;
    undoMethod?: string | null;
    pendingLabel?: string;
    undoingLabel?: string;
    doneLabel?: string;
    errorLabel?: string;
    undoLabel?: string;
    confirmTimeout?: number;
    resultTimeout?: number;
    cancelOnOutside?: boolean;
}

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraConfirmMorph', (config: ConfirmConfig = {}) => ({
            state: 'idle' as Face,
            working: 'confirm' as 'confirm' | 'undo',
            announcement: '',
            method: config.method || null,
            undoMethod: config.undoMethod || null,
            pendingLabel: config.pendingLabel || 'Deleting',
            undoingLabel: config.undoingLabel || 'Restoring',
            doneLabel: config.doneLabel || 'Deleted',
            errorLabel: config.errorLabel || 'Could not finish',
            undoLabel: config.undoLabel || 'Undo',
            confirmTimeout: config.confirmTimeout ?? 6000,
            resultTimeout: config.resultTimeout ?? 5000,
            cancelOnOutside: config.cancelOnOutside !== false,
            runToken: 0,
            clock: null as Animation | null,
            widthAnim: null as Animation | null,
            holdHover: false,
            holdHidden: false,
            onHide: null as (() => void) | null,
            onOutside: null as ((event: PointerEvent) => void) | null,

            init(): void {
                this.onHide = () => {
                    this.holdHidden = document.hidden;
                    this.syncClock();
                };
                document.addEventListener('visibilitychange', this.onHide);
            },

            destroy(): void {
                this.clock?.cancel();
                this.widthAnim?.cancel();
                if (this.onHide) document.removeEventListener('visibilitychange', this.onHide);
                this.unbindOutside();
            },

            face(name: Face): HTMLElement | null {
                return (this.$root as HTMLElement).querySelector<HTMLElement>(`[data-face="${name}"]`);
            },

            surface(): HTMLElement | null {
                return this.$refs.surface as HTMLElement | undefined ?? null;
            },

            go(next: Face): void {
                if (next === this.state) return;
                const root = this.$root as HTMLElement;
                const inside = root.contains(document.activeElement) || document.activeElement === document.body;
                const direction = next === 'idle' ? -1 : 1;
                const previous = this.face(this.state);
                this.state = next;
                root.dataset.state = next;
                this.$nextTick(() => {
                    this.present(previous, direction);
                    if (inside) this.focusFace();
                    this.armClock();
                    this.bindOutside();
                });
            },

            present(previous: HTMLElement | null, direction: number): void {
                const surface = this.surface();
                const current = this.face(this.state);
                if (!surface || !current) return;
                const reduced = prefersReducedMotion();
                const from = surface.getBoundingClientRect().width;
                if (previous && previous !== current) previous.setAttribute('data-leaving', '');
                current.hidden = false;
                const held = current.style.cssText;
                current.style.position = 'absolute';
                current.style.width = 'max-content';
                current.style.flex = 'none';
                const target = Math.ceil(Math.max(current.offsetWidth, current.scrollWidth));
                current.style.cssText = held;

                if (previous && previous !== current) {
                    previous.hidden = false;
                    if (!reduced) {
                        previous.animate(
                            [
                                { opacity: 1, transform: 'translateX(0px)', filter: 'blur(0px)' },
                                { opacity: 0, transform: `translateX(${direction * -8}px)`, filter: 'blur(6px)' },
                            ],
                            { duration: 160, easing: SLIDE, fill: 'forwards' },
                        ).onfinish = () => {
                            previous.hidden = true;
                            previous.removeAttribute('data-leaving');
                        };
                    } else {
                        previous.hidden = true;
                        previous.removeAttribute('data-leaving');
                    }
                    if (!reduced) {
                        current.animate(
                            [
                                { opacity: 0, transform: `translateX(${direction * 12}px)`, filter: 'blur(8px)' },
                                { opacity: 1, transform: 'translateX(0px)', filter: 'blur(0px)' },
                            ],
                            { duration: 360, easing: SLIDE, fill: 'forwards' },
                        );
                    }
                }

                this.springWidth(surface, from, target, reduced || !previous || from < 1);
                (this.$root as HTMLElement).querySelectorAll<HTMLElement>('[data-face]').forEach((face) => {
                    if (face !== current && face !== previous) face.hidden = true;
                });
            },

            springWidth(surface: HTMLElement, from: number, target: number, jump: boolean): void {
                this.widthAnim?.cancel();
                if (jump || Math.abs(from - target) < 0.5) {
                    surface.style.width = `${target}px`;
                    return;
                }
                const growing = target > from;
                const anim = surface.animate(
                    [{ width: `${from}px` }, { width: `${target}px` }],
                    { duration: growing ? 440 : 340, easing: growing ? GROW : SHRINK, fill: 'forwards' },
                );
                this.widthAnim = anim;
                anim.onfinish = () => {
                    const face = this.face(this.state);
                    const needed = face ? Math.ceil(face.scrollWidth) : target;
                    surface.style.width = `${Math.max(target, needed)}px`;
                    anim.cancel();
                };
            },

            focusFace(): void {
                const face = this.face(this.state);
                const target = face?.querySelector<HTMLElement>('[data-autofocus]') ?? (this.$root as HTMLElement);
                target.focus({ preventScroll: true });
            },

            ask(): void {
                const prompt = (this.$root as HTMLElement).dataset.prompt || '';
                this.announcement = prompt;
                this.go('confirming');
            },

            cancel(): void {
                this.runToken += 1;
                this.announcement = 'Cancelled';
                this.go('idle');
            },

            async call(method: string | null): Promise<void> {
                if (!method) return;
                const wire = (this as unknown as { $wire?: Record<string, unknown> }).$wire;
                if (!wire) {
                    await new Promise((resolve) => window.setTimeout(resolve, 700));
                    return;
                }
                const direct = wire[method];
                if (typeof direct === 'function') {
                    await (direct as () => Promise<unknown>)();
                    return;
                }
                const caller = wire.$call ?? wire.call;
                if (typeof caller === 'function') {
                    await (caller as (name: string) => Promise<unknown>).call(wire, method);
                }
            },

            async perform(kind: 'confirm' | 'undo'): Promise<void> {
                const token = ++this.runToken;
                this.working = kind;
                const method = kind === 'confirm' ? this.method : this.undoMethod;
                const pending = this.call(method);
                const asyncWork = method !== null;
                if (asyncWork) {
                    this.announcement = kind === 'confirm' ? this.pendingLabel : this.undoingLabel;
                    this.go('pending');
                    try {
                        await pending;
                    } catch {
                        if (token !== this.runToken) return;
                        this.announcement = this.errorLabel;
                        this.go('error');
                        return;
                    }
                    if (token !== this.runToken) return;
                }
                if (kind === 'undo') {
                    this.announcement = 'Undone';
                    this.go('idle');
                    return;
                }
                this.announcement = this.undoMethod ? `${this.doneLabel}. ${this.undoLabel} is available.` : this.doneLabel;
                this.go('done');
            },

            retry(): void {
                void this.perform(this.working);
            },

            timeoutFor(): number {
                if (this.state === 'confirming') return this.confirmTimeout;
                if (this.state === 'done' || this.state === 'error') return this.resultTimeout;
                return 0;
            },

            armClock(): void {
                this.clock?.cancel();
                this.clock = null;
                const drain = this.$refs.drain as HTMLElement | undefined;
                const timeout = this.timeoutFor();
                if (!drain) return;
                if (!timeout || prefersReducedMotion()) {
                    drain.style.transform = 'scaleX(0)';
                    if (timeout && prefersReducedMotion()) {
                        this.clock = drain.animate(
                            [{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }],
                            { duration: timeout, easing: 'linear', fill: 'forwards' },
                        );
                        this.clock.onfinish = () => this.expire();
                        this.syncClock();
                    }
                    return;
                }
                drain.style.transform = 'scaleX(1)';
                this.clock = drain.animate(
                    [{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }],
                    { duration: timeout, easing: 'linear', fill: 'forwards' },
                );
                this.clock.onfinish = () => this.expire();
                this.syncClock();
            },

            syncClock(): void {
                if (!this.clock) return;
                if (this.holdHover || this.holdHidden) this.clock.pause();
                else this.clock.play();
            },

            expire(): void {
                if (this.state === 'confirming') this.cancel();
                else if (this.state === 'done' || this.state === 'error') this.go('idle');
            },

            hold(on: boolean): void {
                this.holdHover = on;
                this.syncClock();
            },

            bindOutside(): void {
                this.unbindOutside();
                if (this.state !== 'confirming' || !this.cancelOnOutside) return;
                this.onOutside = (event: PointerEvent) => {
                    if ((this.$root as HTMLElement).contains(event.target as Node)) return;
                    this.cancel();
                };
                document.addEventListener('pointerdown', this.onOutside);
            },

            unbindOutside(): void {
                if (!this.onOutside) return;
                document.removeEventListener('pointerdown', this.onOutside);
                this.onOutside = null;
            },

            onKey(event: KeyboardEvent): void {
                if (event.key !== 'Escape') return;
                if (this.state === 'confirming') {
                    event.preventDefault();
                    event.stopPropagation();
                    this.cancel();
                } else if (this.state === 'done' || this.state === 'error') {
                    event.preventDefault();
                    event.stopPropagation();
                    this.go('idle');
                }
            },
        }));
    });
}

import { onAlpineInit } from '../../runtime/alpine';

export interface ThinkingStateConfig {
    steps?: string[];
    delays?: number[];
    /** Override “Thought for Ns”; otherwise derived from delay sum. */
    duration?: number | null;
    autoPlay?: boolean;
    /** Start already done (all steps visible, collapsed). */
    done?: boolean;
    /** When done, whether the reasoning panel starts open. */
    open?: boolean;
    /** Controlled reveal count for Livewire (0..steps.length). */
    revealed?: number | null;
    gap?: number;
    maxHeight?: number;
    fade?: number;
    collapseBeat?: number;
    /** Template with `{seconds}`, e.g. "Thought for {seconds}s". */
    thoughtFor?: string;
}

type FadeState = { top: boolean; bottom: boolean };

if (typeof window !== 'undefined') {
    const NK_THINKING_BOOT = ((window as any).__NK_THINKING_BOOT__ ??= { booted: false });

    if (!NK_THINKING_BOOT.booted) {
        NK_THINKING_BOOT.booted = true;

        onAlpineInit(() => {
            (window as any).Alpine.data('neuraThinkingState', (config: ThinkingStateConfig = {}) => {
                const steps = Array.isArray(config.steps) ? config.steps.filter(Boolean) : [];
                const delays =
                    Array.isArray(config.delays) && config.delays.length
                        ? config.delays
                        : steps.map(() => 800);
                const gap = config.gap ?? 4;
                const maxHeight = config.maxHeight ?? 180;
                const fadePx = config.fade ?? 12;
                const collapseBeat = config.collapseBeat ?? 360;
                const thinkMs = delays.slice(0, steps.length).reduce((a, b) => a + Number(b || 0), 0);
                const elapsed =
                    config.duration != null && config.duration > 0
                        ? Math.round(config.duration)
                        : Math.max(1, Math.round(thinkMs / 1000));
                const thoughtFor =
                    typeof config.thoughtFor === 'string' && config.thoughtFor.length
                        ? config.thoughtFor
                        : 'Thought for {seconds}s';

                return {
                    steps,
                    delays,
                    gap,
                    maxHeight,
                    fadePx,
                    elapsed,
                    thoughtFor,
                    phase: (config.done ? 'done' : 'thinking') as 'thinking' | 'done',
                    revealed: (() => {
                        if (config.done) return steps.length;
                        if (typeof config.revealed === 'number') {
                            return Math.max(0, Math.min(steps.length, Math.round(config.revealed)));
                        }

                        return config.autoPlay ? 0 : steps.length;
                    })(),
                    open: Boolean(config.open),
                    measuredH: 0,
                    fade: { top: false, bottom: true } as FadeState,
                    timers: [] as ReturnType<typeof setTimeout>[],

                    init() {
                        if (!this.steps.length) {
                            this.phase = 'done';
                            this.revealed = 0;

                            return;
                        }

                        if (config.done) {
                            this.revealed = this.steps.length;
                            this.phase = 'done';
                            this.$nextTick(() => this.syncHeight());

                            return;
                        }

                        // Data-driven: parent/Livewire owns revealed + done.
                        if (!config.autoPlay) {
                            if (typeof config.revealed === 'number') {
                                this.revealed = Math.max(
                                    0,
                                    Math.min(this.steps.length, Math.round(config.revealed)),
                                );
                            } else {
                                this.revealed = this.steps.length;
                                this.phase = 'done';
                            }
                            this.$nextTick(() => this.syncHeight());

                            return;
                        }

                        const reduce =
                            typeof window !== 'undefined' &&
                            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

                        if (reduce) {
                            this.revealed = this.steps.length;
                            this.phase = 'done';
                            this.$nextTick(() => this.syncHeight());

                            return;
                        }

                        let t = 0;
                        this.delays.slice(0, this.steps.length).forEach((d: number, i: number) => {
                            t += Number(d) || 0;
                            this.timers.push(
                                setTimeout(() => {
                                    this.revealed = i + 1;
                                    this.$nextTick(() => this.syncHeight());
                                }, t),
                            );
                        });
                        this.timers.push(
                            setTimeout(() => {
                                this.phase = 'done';
                                this.$nextTick(() => this.syncHeight());
                            }, thinkMs + collapseBeat),
                        );

                        this.$nextTick(() => this.syncHeight());
                    },

                    sync(next: {
                        steps?: string[];
                        revealed?: number;
                        done?: boolean;
                        open?: boolean;
                        duration?: number;
                    } = {}) {
                        if (Array.isArray(next.steps)) {
                            this.steps = next.steps.filter(Boolean);
                        }
                        if (typeof next.open === 'boolean') this.open = next.open;
                        if (typeof next.duration === 'number' && next.duration > 0) {
                            this.elapsed = Math.round(next.duration);
                        }
                        if (next.done) {
                            this.phase = 'done';
                            this.revealed = this.steps.length;
                        } else if (typeof next.revealed === 'number') {
                            this.phase = 'thinking';
                            this.revealed = Math.max(
                                0,
                                Math.min(this.steps.length, Math.round(next.revealed)),
                            );
                        }
                        this.$nextTick(() => this.syncHeight());
                    },

                    destroy() {
                        this.timers.forEach(clearTimeout);
                        this.timers = [];
                    },

                    syncHeight() {
                        const stream = (this as any).$refs.stream as HTMLElement | undefined;
                        this.measuredH = stream ? stream.scrollHeight : 0;
                    },

                    get done(): boolean {
                        return this.phase === 'done';
                    },

                    get summaryLabel(): string {
                        return this.thoughtFor.replace('{seconds}', String(this.elapsed));
                    },

                    get expanded(): boolean {
                        return this.done ? this.open : true;
                    },

                    get count(): number {
                        return this.done ? this.steps.length : this.revealed;
                    },

                    get contentH(): number {
                        return this.measuredH;
                    },

                    get capped(): boolean {
                        return this.contentH > this.maxHeight;
                    },

                    get viewH(): number {
                        if (!this.expanded) return 0;

                        return this.capped ? this.maxHeight : this.contentH;
                    },

                    get scrollable(): boolean {
                        return this.done && this.open && this.capped;
                    },

                    get translate(): number {
                        if (!this.expanded || this.scrollable) return 0;
                        if (this.capped) return this.maxHeight - this.fadePx - this.contentH;

                        return 0;
                    },

                    get showTop(): boolean {
                        return this.scrollable ? this.fade.top : this.capped && this.expanded;
                    },

                    get showBottom(): boolean {
                        return this.scrollable ? this.fade.bottom : this.capped && this.expanded;
                    },

                    get mask(): string {
                        if (!this.capped || !this.expanded) return 'none';
                        const top = this.showTop ? this.fadePx : 0;
                        const bottom = this.showBottom ? this.fadePx : 0;

                        return `linear-gradient(to bottom, transparent 0, #000 ${top}px, #000 calc(100% - ${bottom}px), transparent 100%)`;
                    },

                    onScroll() {
                        const el = (this as any).$refs.viewport as HTMLElement | undefined;
                        if (!el) return;
                        this.fade = {
                            top: el.scrollTop > 1,
                            bottom: el.scrollTop + el.clientHeight < el.scrollHeight - 1,
                        };
                    },

                    toggle() {
                        if (!this.done) return;
                        const next = !this.open;
                        if (next) {
                            this.fade = { top: false, bottom: true };
                            const el = (this as any).$refs.viewport as HTMLElement | undefined;
                            if (el) el.scrollTop = 0;
                        }
                        this.open = next;
                        this.$nextTick(() => this.syncHeight());
                    },
                };
            });
        });
    }
}

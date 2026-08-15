import { onAlpineInit } from '../../runtime/alpine';

export type SiteState = 'pending' | 'loading' | 'done';

export interface WebSearchSource {
    title: string;
    url?: string;
    /** Controlled state from Livewire / parent. */
    state?: SiteState;
    /** Demo-only cadence when autoPlay is on. */
    discover?: number;
    finish?: number;
}

export interface WebSearchConfig {
    sources?: WebSearchSource[];
    query?: string | null;
    /** Fake timed reveal for docs — off by default for real data. */
    autoPlay?: boolean;
    loop?: boolean;
    loopGap?: number;
    open?: boolean;
    /** Overall finished flag from the parent. */
    done?: boolean;
}

function normalizeState(value: unknown, fallback: SiteState = 'pending'): SiteState {
    if (value === 'pending' || value === 'loading' || value === 'done') {
        return value;
    }

    return fallback;
}

function statesFromSources(sources: WebSearchSource[], done: boolean): SiteState[] {
    if (done) {
        return sources.map(() => 'done');
    }

    return sources.map((site) => normalizeState(site.state, 'pending'));
}

if (typeof window !== 'undefined') {
    const NK_WS_BOOT = ((window as any).__NK_WS_BOOT__ ??= { booted: false });

    if (!NK_WS_BOOT.booted) {
        NK_WS_BOOT.booted = true;

        onAlpineInit(() => {
            (window as any).Alpine.data('neuraWebSearch', (config: WebSearchConfig = {}) => {
                const sources = Array.isArray(config.sources)
                    ? config.sources.filter((s) => s?.title)
                    : [];
                const loopGap = config.loopGap ?? 2800;
                const doneGap = 800;
                const autoPlay = Boolean(config.autoPlay);

                return {
                    sources,
                    open: config.open !== false,
                    done: Boolean(config.done),
                    states: statesFromSources(sources, Boolean(config.done)),
                    timers: [] as ReturnType<typeof setTimeout>[],
                    cancelled: false,
                    emitted: false,

                    init() {
                        if (!this.sources.length) {
                            this.done = true;

                            return;
                        }

                        if (this.done || this.states.every((s: SiteState) => s === 'done')) {
                            this.done = true;
                            this.states = this.sources.map(() => 'done');
                            this.emitDoneOnce();

                            return;
                        }

                        if (!autoPlay) {
                            // Data-driven: Livewire / parent owns the timeline.
                            return;
                        }

                        const reduce =
                            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

                        if (reduce) {
                            this.states = this.sources.map(() => 'done');
                            this.done = true;
                            this.emitDoneOnce();

                            return;
                        }

                        this.run();
                    },

                    destroy() {
                        this.cancelled = true;
                        this.clearTimers();
                    },

                    /** Sync from a Livewire re-render or external Alpine patch. */
                    sync(next: {
                        sources?: WebSearchSource[];
                        done?: boolean;
                        open?: boolean;
                        query?: string | null;
                    } = {}) {
                        if (Array.isArray(next.sources)) {
                            this.sources = next.sources.filter((s) => s?.title);
                        }
                        if (typeof next.open === 'boolean') {
                            this.open = next.open;
                        }
                        if (typeof next.done === 'boolean') {
                            this.done = next.done;
                        }

                        this.states = statesFromSources(this.sources, this.done);

                        if (this.done || this.states.every((s: SiteState) => s === 'done')) {
                            this.done = true;
                            this.emitDoneOnce();
                        }
                    },

                    setSourceState(index: number, state: SiteState) {
                        if (index < 0 || index >= this.states.length) return;
                        this.states = this.states.map((v: SiteState, j: number) =>
                            j === index ? state : v,
                        );
                        if (this.sources[index]) {
                            this.sources[index] = { ...this.sources[index], state };
                        }
                        if (this.states.every((s: SiteState) => s === 'done')) {
                            this.done = true;
                            this.emitDoneOnce();
                        }
                    },

                    emitDoneOnce() {
                        if (this.emitted) return;
                        this.emitted = true;
                        (this as any).$dispatch?.('web-search-done', {
                            query: config.query ?? null,
                            sources: this.sources.map((s: WebSearchSource, i: number) => ({
                                ...s,
                                state: this.states[i] ?? s.state ?? 'done',
                            })),
                        });
                    },

                    clearTimers() {
                        this.timers.forEach(clearTimeout);
                        this.timers = [];
                    },

                    at(ms: number, fn: () => void) {
                        this.timers.push(setTimeout(fn, ms));
                    },

                    run() {
                        if (this.cancelled) return;
                        this.clearTimers();
                        this.emitted = false;
                        this.states = this.sources.map(() => 'pending');
                        this.done = false;

                        let last = 0;
                        this.sources.forEach((site: WebSearchSource, i: number) => {
                            const discover = Number(site.discover ?? 600 + i * 1000);
                            const finish = Number(site.finish ?? discover + 1800);
                            last = Math.max(last, finish);
                            this.at(discover, () => this.setSourceState(i, 'loading'));
                            this.at(finish, () => this.setSourceState(i, 'done'));
                        });

                        this.at(last + doneGap, () => {
                            this.done = true;
                            this.emitDoneOnce();
                        });

                        if (config.loop) {
                            this.at(last + doneGap + loopGap, () => {
                                if (!this.cancelled) this.run();
                            });
                        }
                    },

                    toggle() {
                        this.open = !this.open;
                    },

                    openSource(url: string) {
                        if (!url) return;
                        const href = url.startsWith('http') ? url : `https://${url}`;
                        window.open(href, '_blank', 'noopener,noreferrer');
                    },
                };
            });
        });
    }
}

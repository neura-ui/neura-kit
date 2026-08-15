import { onAlpineInit } from '../../runtime/alpine';

export type TodoStatus = 'pending' | 'active' | 'done';

export interface TodoItem {
    label: string;
    status?: TodoStatus;
}

export interface TodoListConfig {
    items?: Array<string | TodoItem>;
    /** Index of the active item; -1 = not started; >= n = all done. Ignored when items carry status. */
    current?: number;
    autoPlay?: boolean;
    startDelay?: number;
    stepMs?: number;
    collapsed?: boolean;
    done?: boolean;
    title?: string;
}

function normalizeItems(raw: Array<string | TodoItem> = []): TodoItem[] {
    return raw
        .map((item) => {
            if (typeof item === 'string') {
                return { label: item, status: 'pending' as TodoStatus };
            }
            if (!item?.label) return null;
            const status =
                item.status === 'pending' || item.status === 'active' || item.status === 'done'
                    ? item.status
                    : ('pending' as TodoStatus);

            return { label: item.label, status };
        })
        .filter(Boolean) as TodoItem[];
}

function currentFromStatuses(items: TodoItem[], done: boolean): number {
    if (done || (items.length > 0 && items.every((i) => i.status === 'done'))) {
        return items.length;
    }
    const active = items.findIndex((i) => i.status === 'active');
    if (active >= 0) return active;
    const firstPending = items.findIndex((i) => i.status !== 'done');
    if (firstPending === -1) return items.length;
    // Not started yet if nothing is active and some pending at start
    if (items.every((i) => i.status === 'pending')) return -1;

    return firstPending;
}

if (typeof window !== 'undefined') {
    const NK_TODO_BOOT = ((window as any).__NK_TODO_BOOT__ ??= { booted: false });

    if (!NK_TODO_BOOT.booted) {
        NK_TODO_BOOT.booted = true;

        onAlpineInit(() => {
            (window as any).Alpine.data('neuraTodoList', (config: TodoListConfig = {}) => {
                const items = normalizeItems(config.items ?? []);
                const n = items.length;
                const hasExplicitStatus = (config.items ?? []).some(
                    (item) => typeof item === 'object' && item && 'status' in item,
                );
                const autoPlay = Boolean(config.autoPlay) && !hasExplicitStatus;
                const startDelay = config.startDelay ?? 700;
                const stepMs = config.stepMs ?? 2250;

                let initialCurrent = -1;
                if (typeof config.current === 'number') {
                    initialCurrent = config.current;
                } else if (hasExplicitStatus || config.done) {
                    initialCurrent = currentFromStatuses(items, Boolean(config.done));
                } else if (config.done) {
                    initialCurrent = n;
                }

                return {
                    items: items.map((i) => i.label),
                    statuses: items.map((i) => i.status ?? 'pending') as TodoStatus[],
                    n,
                    collapsed: Boolean(config.collapsed),
                    current: initialCurrent,
                    timers: [] as ReturnType<typeof setTimeout>[],
                    emitted: false,

                    init() {
                        if (!this.n) return;

                        if (!autoPlay) {
                            if (this.allDone) this.emitDoneOnce();

                            return;
                        }

                        const reduce =
                            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

                        if (reduce || config.done) {
                            this.current = this.n;
                            this.emitDoneOnce();

                            return;
                        }

                        this.timers.push(
                            setTimeout(() => {
                                this.current = 0;
                            }, startDelay),
                        );

                        for (let i = 0; i < this.n; i++) {
                            this.timers.push(
                                setTimeout(() => {
                                    this.current = i + 1;
                                    if (i + 1 >= this.n) this.emitDoneOnce();
                                }, startDelay + (i + 1) * stepMs),
                            );
                        }
                    },

                    destroy() {
                        this.timers.forEach(clearTimeout);
                        this.timers = [];
                    },

                    sync(next: {
                        items?: Array<string | TodoItem>;
                        current?: number;
                        done?: boolean;
                        collapsed?: boolean;
                    } = {}) {
                        if (Array.isArray(next.items)) {
                            const normalized = normalizeItems(next.items);
                            this.items = normalized.map((i) => i.label);
                            this.statuses = normalized.map((i) => i.status ?? 'pending');
                            this.n = normalized.length;
                            this.current = currentFromStatuses(normalized, Boolean(next.done));
                        } else if (typeof next.current === 'number') {
                            this.current = next.current;
                        } else if (next.done) {
                            this.current = this.n;
                        }
                        if (typeof next.collapsed === 'boolean') {
                            this.collapsed = next.collapsed;
                        }
                        if (this.allDone) this.emitDoneOnce();
                    },

                    emitDoneOnce() {
                        if (this.emitted) return;
                        this.emitted = true;
                        (this as any).$dispatch?.('todo-list-done', {
                            items: this.items,
                            current: this.current,
                        });
                    },

                    get started(): boolean {
                        return this.current >= 0;
                    },

                    get allDone(): boolean {
                        return this.n > 0 && this.current >= this.n;
                    },

                    get running(): boolean {
                        return this.started && !this.allDone;
                    },

                    get pct(): number {
                        if (!this.n) return 0;

                        return Math.round(
                            (Math.min(Math.max(this.current, 0), this.n) / this.n) * 100,
                        );
                    },

                    get countLabel(): string {
                        return `${Math.min(Math.max(this.current, 0), this.n)}/${this.n}`;
                    },

                    toggle() {
                        this.collapsed = !this.collapsed;
                    },

                    itemDone(i: number): boolean {
                        if (this.statuses[i] === 'done') return true;

                        return this.started && i < this.current;
                    },

                    itemActive(i: number): boolean {
                        if (this.statuses[i] === 'active') return true;
                        if (this.statuses[i] === 'done') return false;

                        return this.started && i === this.current && !this.allDone;
                    },
                };
            });
        });
    }
}

import { onAlpineInit } from '../../../runtime/alpine';
import { cellsBetween, coverage, paint, parse, serialize, toClock } from './model';
import type { Span, Spans } from './model';

/**
 * Time grid.
 *
 * The Blade draws the empty cells; this paints the value into them and turns pointer,
 * keyboard and touch gestures into spans. Listeners sit on the grid (no Alpine binding per
 * cell), the heavy state lives outside Alpine's reactivity, and cells are repainted by diff
 * once per frame: only a cell whose state changed is touched.
 */

interface Column {
    key: string;
    label: string;
    name: string;
}

interface Level {
    key: string;
    label: string;
    color: string;
}

interface Phrases {
    add: string;
    erase: string;
    clear: string;
    level: string;
}

interface TimeGridConfig {
    value?: unknown;
    columns?: Column[];
    keyName?: 'day' | 'column';
    slots?: number[];
    step?: number;
    start?: number;
    end?: number;
    orientation?: 'rows' | 'columns';
    selection?: 'rectangle' | 'paint';
    levels?: Level[];
    disabled?: unknown;
    readonly?: boolean;
    format?: 'ranges' | 'days';
    hourCycle?: 12 | 24;
    name?: string | null;
    phrases?: Partial<Phrases>;
}

interface Gesture {
    c0: number;
    s0: number;
    c1: number;
    s1: number;
    erase: boolean;
    level: string;
    mode: 'rectangle' | 'paint';
    keyboard: boolean;
}

interface Press {
    timer: number;
    x: number;
    y: number;
    ci: number;
    si: number;
    pointer: number;
}

/** A finger held this long on a cell starts a selection; moving sooner scrolls the page. */
const LONG_PRESS = 300;
const PRESS_SLOP = 8;

function between(value: number, a: number, b: number): boolean {
    return value >= Math.min(a, b) && value <= Math.max(a, b);
}

onAlpineInit(() => {
    window.Alpine.data('neuraTimeGrid', (config: TimeGridConfig = {}) => {
        const columns = config.columns ?? [];
        const slots = config.slots ?? [];
        const step = config.step ?? 60;
        const levels: Level[] = config.levels?.length ? config.levels : [{ key: 'on', label: '', color: '' }];
        const levelIndex = new Map(levels.map((level, index) => [level.key, index]));
        const defaultLevel = levels[0].key;
        const order = columns.map((column) => column.key);
        const keyName = config.keyName === 'column' ? 'column' : 'day';
        const format = config.format === 'days' ? 'days' : 'ranges';
        const withLevel = levels.length > 1;
        const byRows = config.orientation !== 'columns';
        const phrases: Phrases = { add: 'Add', erase: 'Erase', clear: 'Clear all', level: 'Level', ...config.phrases };
        // `disabled: true` locks the whole grid and keeps its value in view; a list blocks those ranges.
        const locked = config.disabled === true || config.readonly === true;
        const blocked: Spans = config.disabled === true ? new Map() : parse(config.disabled, 'blocked');

        let component: any = null;
        let spans: Spans = new Map();
        let grid: HTMLElement | null = null;
        let cells: HTMLElement[] = [];
        let states: string[] = [];
        let gesture: Gesture | null = null;
        let press: Press | null = null;
        let tabbable: HTMLElement | null = null;
        let frame = 0;
        let lastWritten = '';
        const listeners: Array<[EventTarget, string, EventListener, AddEventListenerOptions | undefined]> = [];

        const on = (target: EventTarget, type: string, handler: EventListener, options?: AddEventListenerOptions) => {
            target.addEventListener(type, handler, options);
            listeners.push([target, type, handler, options]);
        };

        const at = (ci: number, si: number): HTMLElement | undefined => cells[ci * slots.length + si];

        const clock = (minutes: number): string => {
            if (config.hourCycle !== 12) return toClock(minutes);
            const hours = Math.floor(minutes / 60);
            const rest = minutes % 60;
            return (hours % 12 || 12) + (rest ? ':' + String(rest).padStart(2, '0') : '') + (hours % 24 < 12 ? ' AM' : ' PM');
        };

        /** Levels the grid doesn't list show (and are replaced) as the first one. */
        const shown = (level: string | null): string | null =>
            level === null ? null : levelIndex.has(level) ? level : defaultLevel;

        const isBlocked = (ci: number, si: number): boolean => {
            const list = blocked.get(columns[ci].key);
            if (!list) return false;
            const cover = coverage(list, slots[si], slots[si] + step);
            return cover.level !== null || cover.partial;
        };

        const cellLevel = (ci: number, si: number): string | null =>
            shown(coverage(spans.get(columns[ci].key) ?? [], slots[si], slots[si] + step).level);

        const firstLevelIn = (list: Span[], start: number, end: number): string =>
            shown(list.find((span) => span.end > start && span.start < end)?.level ?? defaultLevel) ?? defaultLevel;

        const inGesture = (ci: number, si: number): boolean =>
            gesture !== null && gesture.mode === 'rectangle' && between(ci, gesture.c0, gesture.c1) && between(si, gesture.s0, gesture.s1);

        /** `kind:level`, with a `!` in front when the cell is blocked (hatched over its fill). */
        const stateOf = (ci: number, si: number): string => {
            const blockedCell = isBlocked(ci, si);
            return (blockedCell ? '!' : '') + fillOf(ci, si, blockedCell);
        };

        const fillOf = (ci: number, si: number, blockedCell: boolean): string => {
            const list = spans.get(columns[ci].key) ?? [];
            const cover = coverage(list, slots[si], slots[si] + step);

            if (gesture && !blockedCell && inGesture(ci, si)) {
                if (!gesture.erase) return 'add:' + (levelIndex.get(gesture.level) ?? 0);
                if (cover.level === null && !cover.partial) return 'free';
                return 'erase:' + (levelIndex.get(firstLevelIn(list, slots[si], slots[si] + step)) ?? 0);
            }
            if (cover.level !== null) return 'full:' + (levelIndex.get(shown(cover.level) ?? defaultLevel) ?? 0);
            if (cover.partial) return 'partial:' + (levelIndex.get(firstLevelIn(list, slots[si], slots[si] + step)) ?? 0);

            return 'free';
        };

        const repaint = (): void => {
            frame = 0;
            cells.forEach((cell, index) => {
                const state = stateOf(Math.floor(index / slots.length), index % slots.length);
                if (states[index] === state) return;

                states[index] = state;
                const blockedCell = state.startsWith('!');
                const [kind, level] = (blockedCell ? state.slice(1) : state).split(':');
                cell.dataset.state = kind;
                cell.toggleAttribute('data-blocked', blockedCell);
                cell.setAttribute('aria-selected', kind === 'full' ? 'true' : 'false');
                if (level === undefined) cell.style.removeProperty('--nk-tg-fill');
                else cell.style.setProperty('--nk-tg-fill', `var(--nk-tg-level-${level})`);
            });
        };

        const schedule = (): void => {
            if (!frame) frame = requestAnimationFrame(repaint);
        };

        const apply = (ci: number, si: number, level: string | null): void => {
            if (isBlocked(ci, si)) return;
            const key = columns[ci].key;
            spans.set(key, paint(spans.get(key) ?? [], slots[si], slots[si] + step, level));
        };

        const commit = (): void => {
            const value = serialize(spans, { format, keyName, order, withLevel });
            lastWritten = JSON.stringify(value);
            component.value = value;
            component.$dispatch('time-grid-change', { value });
        };

        const describe = (): void => {
            if (!gesture) {
                component.status = '';
                return;
            }
            const first = columns[Math.min(gesture.c0, gesture.c1)];
            const last = columns[Math.max(gesture.c0, gesture.c1)];
            const from = slots[Math.min(gesture.s0, gesture.s1)];
            const to = slots[Math.max(gesture.s0, gesture.s1)] + step;
            component.status = `${gesture.erase ? phrases.erase : phrases.add} ${first.label}${first === last ? '' : ' – ' + last.label} · ${clock(from)} – ${clock(to)}`;
        };

        const begin = (ci: number, si: number, keyboard: boolean): void => {
            if (isBlocked(ci, si)) return;
            const brush: string = component.brush;
            gesture = {
                c0: ci, s0: si, c1: ci, s1: si,
                erase: cellLevel(ci, si) === brush,
                level: brush,
                mode: keyboard ? 'rectangle' : config.selection === 'paint' ? 'paint' : 'rectangle',
                keyboard,
            };
            if (gesture.mode === 'paint') apply(ci, si, gesture.erase ? null : brush);
            describe();
            schedule();
        };

        const extend = (ci: number, si: number): void => {
            if (!gesture || (gesture.c1 === ci && gesture.s1 === si)) return;
            if (gesture.mode === 'paint') {
                const level = gesture.erase ? null : gesture.level;
                cellsBetween([gesture.c1, gesture.s1], [ci, si]).forEach(([c, s]) => apply(c, s, level));
            }
            gesture.c1 = ci;
            gesture.s1 = si;
            describe();
            schedule();
        };

        const finish = (): void => {
            if (!gesture) return;
            const done = gesture;
            if (done.mode === 'rectangle') {
                for (let ci = Math.min(done.c0, done.c1); ci <= Math.max(done.c0, done.c1); ci++) {
                    for (let si = Math.min(done.s0, done.s1); si <= Math.max(done.s0, done.s1); si++) {
                        apply(ci, si, done.erase ? null : done.level);
                    }
                }
            }
            gesture = null;
            commit();
            describe();
            schedule();
        };

        /** A rectangle is dropped; cells already painted in paint mode stay painted. */
        const cancel = (): void => {
            if (!gesture) return;
            const painted = gesture.mode === 'paint';
            gesture = null;
            if (painted) commit();
            describe();
            schedule();
        };

        const cancelPress = (): void => {
            if (!press) return;
            clearTimeout(press.timer);
            press = null;
        };

        const toggle = (ci: number, si: number): void => {
            if (isBlocked(ci, si)) return;
            apply(ci, si, cellLevel(ci, si) === component.brush ? null : component.brush);
            commit();
            schedule();
        };

        const fillLine = (header: HTMLElement): void => {
            const pairs: Array<[number, number]> = header.dataset.tgColumn !== undefined
                ? slots.map((_, si) => [Number(header.dataset.tgColumn), si])
                : columns.map((_, ci) => [ci, Number(header.dataset.tgSlot)]);
            const open = pairs.filter(([ci, si]) => !isBlocked(ci, si));
            if (!open.length) return;

            const full = open.every(([ci, si]) => cellLevel(ci, si) === component.brush);
            open.forEach(([ci, si]) => apply(ci, si, full ? null : component.brush));
            commit();
            schedule();
        };

        /** One cell is in the tab order: the last one focused, however it got the focus. */
        const makeTabbable = (cell: HTMLElement): void => {
            if (tabbable && tabbable !== cell) tabbable.setAttribute('tabindex', '-1');
            cell.setAttribute('tabindex', '0');
            tabbable = cell;
        };

        const focusCell = (ci: number, si: number): void => {
            const cell = at(ci, si);
            if (!cell) return;
            makeTabbable(cell);
            cell.focus();
        };

        const cellFrom = (target: EventTarget | null): HTMLElement | null => {
            const cell = (target as Element | null)?.closest?.('[role="gridcell"]') as HTMLElement | null;
            return cell && grid?.contains(cell) ? cell : null;
        };

        const indexOf = (cell: HTMLElement): [number, number] => [Number(cell.dataset.ci), Number(cell.dataset.si)];

        /**
         * Past the edge of the grid, over a header or a gap, the rectangle follows the pointer
         * to the nearest cell in view, like a spreadsheet selection.
         */
        const nearestCell = (x: number, y: number): HTMLElement | null => {
            const view = grid?.parentElement?.getBoundingClientRect();
            const first = cells[0]?.getBoundingClientRect();
            const last = cells[cells.length - 1]?.getBoundingClientRect();
            if (!view || !first || !last) return null;

            const left = Math.max(first.left, view.left) + 1;
            const right = Math.min(last.right, view.right) - 1;
            const top = Math.max(first.top, view.top, 0) + 1;
            const bottom = Math.min(last.bottom, view.bottom, window.innerHeight) - 1;
            const cx = Math.min(Math.max(x, left), right);
            const cy = Math.min(Math.max(y, top), bottom);

            for (const [dx, dy] of [[0, 0], [-3, 0], [3, 0], [0, -3], [0, 3], [-3, -3], [3, 3]]) {
                const hit = cellFrom(document.elementFromPoint(cx + dx, cy + dy));
                if (hit) return hit;
            }
            return null;
        };

        const onPointerDown = (event: PointerEvent): void => {
            const cell = cellFrom(event.target);
            if (!cell || (event.pointerType === 'mouse' && event.button !== 0)) return;
            const [ci, si] = indexOf(cell);

            if (event.pointerType === 'touch') {
                cancelPress();
                press = {
                    timer: window.setTimeout(() => {
                        if (!press) return;
                        const { ci: pc, si: ps } = press;
                        press = null;
                        begin(pc, ps, false);
                    }, LONG_PRESS),
                    x: event.clientX,
                    y: event.clientY,
                    ci,
                    si,
                    pointer: event.pointerId,
                };
                return;
            }

            event.preventDefault();
            focusCell(ci, si);
            begin(ci, si, false);
            try {
                grid?.setPointerCapture(event.pointerId);
            } catch {
                // The pointer may already be gone.
            }
        };

        const onPointerMove = (event: PointerEvent): void => {
            if (press) {
                if (Math.hypot(event.clientX - press.x, event.clientY - press.y) > PRESS_SLOP) cancelPress();
                return;
            }
            if (!gesture || gesture.keyboard) return;
            const cell = cellFrom(document.elementFromPoint(event.clientX, event.clientY))
                ?? (gesture.mode === 'rectangle' ? nearestCell(event.clientX, event.clientY) : null);
            if (cell) extend(...indexOf(cell));
        };

        const onPointerUp = (): void => {
            if (press) {
                const { ci, si } = press;
                cancelPress();
                toggle(ci, si);
                return;
            }
            if (gesture && !gesture.keyboard) finish();
        };

        const onPointerCancel = (): void => {
            cancelPress();
            if (gesture && !gesture.keyboard) cancel();
        };

        /** Keeps the page still while a finger draws; before the long press it scrolls as usual. */
        const onTouchMove = (event: TouchEvent): void => {
            if (gesture && !gesture.keyboard) event.preventDefault();
        };

        const onClick = (event: MouseEvent): void => {
            const header = (event.target as Element | null)?.closest?.('[data-tg-column], [data-tg-slot]') as HTMLElement | null;
            if (header && grid?.contains(header)) fillLine(header);
        };

        const onKeyDown = (event: KeyboardEvent): void => {
            const cell = cellFrom(event.target);
            if (!cell) return;
            const [ci, si] = indexOf(cell);

            // Arrows move along what the eye sees: across the hours in rows, across the days in columns.
            const across = byRows ? { ArrowLeft: [0, -1], ArrowRight: [0, 1], ArrowUp: [-1, 0], ArrowDown: [1, 0] }
                : { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
            const move = (across as Record<string, number[]>)[event.key];

            if (move || event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                let next: [number, number];
                if (move) {
                    next = [
                        Math.max(0, Math.min(columns.length - 1, ci + move[0])),
                        Math.max(0, Math.min(slots.length - 1, si + move[1])),
                    ];
                } else if (byRows) {
                    next = [ci, event.key === 'Home' ? 0 : slots.length - 1];
                } else {
                    next = [event.key === 'Home' ? 0 : columns.length - 1, si];
                }

                if (event.shiftKey) {
                    if (!gesture) begin(ci, si, true);
                    extend(next[0], next[1]);
                } else if (gesture?.keyboard) {
                    cancel();
                }
                focusCell(next[0], next[1]);
                return;
            }

            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                if (gesture?.keyboard) finish();
                else toggle(ci, si);
                return;
            }

            if (event.key === 'Escape' && gesture) {
                event.preventDefault();
                event.stopPropagation();
                cancel();
            }
        };

        return {
            value: config.value ?? [],
            brush: defaultLevel,
            status: '',

            /** Hidden inputs for a plain form post, one set per range. */
            get fields(): Array<{ name: string; value: string }> {
                if (!config.name) return [];
                const ranges = serialize(parse(this.value, defaultLevel), { format: 'ranges', keyName, order, withLevel }) as Array<Record<string, string>>;
                return ranges.flatMap((range, index) => Object.entries(range).map(([field, value]) => ({
                    name: `${config.name}[${index}][${field}]`,
                    value,
                })));
            },

            init(this: any) {
                component = this;
                spans = parse(this.value, defaultLevel);
                lastWritten = JSON.stringify(this.value);

                grid = (this.$el as HTMLElement).querySelector<HTMLElement>('[data-tg-grid]');
                if (!grid) return;

                cells = new Array(columns.length * slots.length);
                grid.querySelectorAll<HTMLElement>('[role="gridcell"]').forEach((cell) => {
                    const [ci, si] = indexOf(cell);
                    cells[ci * slots.length + si] = cell;
                });
                // Unknown states, so the first paint sets every cell, whatever the DOM was left showing.
                states = new Array(cells.length);
                tabbable = grid.querySelector<HTMLElement>('[role="gridcell"][tabindex="0"]');
                repaint();

                // A value set from outside (wire:model, x-model) repaints; our own writes come back unchanged.
                this.$watch('value', (value: unknown) => {
                    const text = JSON.stringify(value);
                    if (text === lastWritten) return;
                    lastWritten = text;
                    spans = parse(value, defaultLevel);
                    schedule();
                });

                if (locked) return;

                on(grid, 'pointerdown', onPointerDown as EventListener);
                on(grid, 'pointermove', onPointerMove as EventListener);
                on(grid, 'pointerup', onPointerUp as EventListener);
                on(grid, 'pointercancel', onPointerCancel as EventListener);
                on(grid, 'lostpointercapture', onPointerCancel as EventListener);
                on(grid, 'touchmove', onTouchMove as EventListener, { passive: false });
                on(grid, 'contextmenu', ((event: Event) => {
                    if (press || gesture) event.preventDefault();
                }) as EventListener);
                on(grid, 'click', onClick as EventListener);
                on(grid, 'keydown', onKeyDown as EventListener);
                // A keyboard rectangle is dropped when the focus leaves the grid.
                on(grid, 'focusout', ((event: FocusEvent) => {
                    if (gesture?.keyboard && !grid?.contains(event.relatedTarget as Node | null)) cancel();
                }) as EventListener);
                on(grid, 'focusin', ((event: FocusEvent) => {
                    const cell = cellFrom(event.target);
                    if (cell) makeTabbable(cell);
                }) as EventListener);
                on(window, 'blur', onPointerCancel as EventListener);
            },

            destroy() {
                listeners.forEach(([target, type, handler, options]) => target.removeEventListener(type, handler, options));
                listeners.length = 0;
                cancelPress();
                if (frame) cancelAnimationFrame(frame);
                gesture = null;
            },

            clear() {
                columns.forEach((_, ci) => slots.forEach((_, si) => apply(ci, si, null)));
                commit();
                schedule();
            },

            setBrush(this: any, key: string) {
                if (levelIndex.has(key)) this.brush = key;
            },
        };
    });
});

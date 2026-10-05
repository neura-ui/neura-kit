import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Button group.
 *
 * One highlight travels between the segments under the pointer or the keyboard.
 * It fades in where it lands when it arrives from outside the group, and only
 * springs when it moves from one segment to another. The chevron never scales,
 * so the menu opens from a still anchor.
 */

const MENU = 'button-group-menu';

/** Short spring with a little settle, close to a 260ms snappy curve. */
const SNAPPY =
    'linear(0, 0.18 12%, 0.42 24%, 0.64 38%, 0.82 52%, 0.94 66%, 1.02 78%, 1.035 88%, 1.01 94%, 1)';

const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

export interface ButtonGroupItem {
    id: string;
    label?: string;
    method?: string | null;
    disabled?: boolean;
    confirm?: string | null;
    resetAfter?: number;
    step?: number | null;
    reel?: string | null;
}

export interface ButtonGroupConfig {
    disabled?: boolean;
    orientation?: 'horizontal' | 'vertical';
    collapse?: boolean;
}

interface Box {
    x: number;
    y: number;
    w: number;
    h: number;
}

interface DigitColumn {
    el: HTMLElement;
    glyphs: HTMLElement[];
    digit: number;
    position: number;
    target: number;
    frame: number;
}

interface Reel {
    node: HTMLElement;
    track: HTMLElement;
    key: string;
    value: number;
    columns: DigitColumn[];
    stop: () => void;
    nudge: (step: number) => number | null;
}

const reels = new WeakMap<HTMLElement, Reel>();
const reelMemory = new Map<string, number>();
const DIGIT_MS = 400;
const PLACE_MS = 420;
const PLACE_EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

function reelKey(node: HTMLElement): string {
    return node.closest('[data-key]')?.getAttribute('data-key') || 'reel';
}

function placesOf(value: number): number[] {
    return [...String(Math.abs(Math.trunc(value)))].map((char) => Number(char));
}

function glyphOffset(digit: number, position: number): number {
    return ((((digit - position) % 10) + 15) % 10) - 5;
}

function paintColumn(column: DigitColumn): void {
    column.glyphs.forEach((glyph, digit) => {
        const offset = glyphOffset(digit, column.position);
        const distance = Math.abs(offset);
        glyph.style.transform = `translateY(${offset}em)`;
        glyph.style.opacity = String(Math.max(0, 1 - distance));
        glyph.style.visibility = distance >= 1 ? 'hidden' : 'visible';
        glyph.style.filter = distance < 0.02 || distance >= 1 ? 'none' : `blur(${(distance * 4).toFixed(2)}px)`;
    });
}

function springColumn(column: DigitColumn): void {
    window.cancelAnimationFrame(column.frame);
    const from = column.position;
    const to = column.target;
    if (prefersReducedMotion() || from === to) {
        column.position = to;
        paintColumn(column);
        return;
    }

    const start = performance.now();
    const tick = (now: number) => {
        const progress = Math.min(1, (now - start) / DIGIT_MS);
        const time = progress * 5.8;
        const eased = 1 - (1 + time) * Math.exp(-time);
        column.position = from + (to - from) * eased;
        paintColumn(column);
        column.frame = progress < 1 ? window.requestAnimationFrame(tick) : 0;
        if (progress >= 1) {
            column.position = to;
            paintColumn(column);
        }
    };
    column.frame = window.requestAnimationFrame(tick);
}

function stepColumn(column: DigitColumn, digit: number, direction: number): void {
    if (column.digit === digit) return;
    const delta = direction < 0
        ? -((column.digit - digit + 10) % 10)
        : (digit - column.digit + 10) % 10;
    column.target += delta;
    column.digit = digit;
    column.el.dataset.digit = String(digit);
    springColumn(column);
}

function createColumn(digit: number): DigitColumn {
    const el = document.createElement('span');
    el.className = 'nk-group-reel-column';
    el.dataset.digit = String(digit);
    const sizer = document.createElement('span');
    sizer.className = 'nk-group-reel-sizer';
    sizer.textContent = '0';
    el.append(sizer);
    const glyphs = Array.from({ length: 10 }, (_, index) => {
        const glyph = document.createElement('span');
        glyph.className = 'nk-group-reel-glyph';
        glyph.textContent = String(index);
        el.append(glyph);
        return glyph;
    });
    const column: DigitColumn = { el, glyphs, digit, position: digit, target: digit, frame: 0 };
    paintColumn(column);
    return column;
}

function adoptColumn(el: HTMLElement): DigitColumn {
    const digit = Number(el.dataset.digit || 0);
    const glyphs = [...el.querySelectorAll<HTMLElement>('.nk-group-reel-glyph')];
    const column: DigitColumn = { el, glyphs, digit, position: digit, target: digit, frame: 0 };
    paintColumn(column);
    return column;
}

function presentColumn(el: HTMLElement): void {
    if (prefersReducedMotion()) return;
    const width = el.getBoundingClientRect().width;
    const animation = el.animate(
        [{ width: '0px', opacity: 0 }, { width: `${width}px`, opacity: 1 }],
        { duration: PLACE_MS, easing: PLACE_EASE, fill: 'both' },
    );
    animation.onfinish = () => {
        animation.cancel();
        el.style.width = '';
        el.style.opacity = '';
    };
}

function dismissColumn(column: DigitColumn): void {
    window.cancelAnimationFrame(column.frame);
    const el = column.el;
    if (prefersReducedMotion()) {
        el.remove();
        return;
    }
    const width = el.getBoundingClientRect().width;
    const animation = el.animate(
        [{ width: `${width}px`, opacity: 1 }, { width: '0px', opacity: 0 }],
        { duration: PLACE_MS, easing: PLACE_EASE, fill: 'forwards' },
    );
    animation.onfinish = () => el.remove();
}

function mountReel(node: HTMLElement): Reel {
    const track = node.querySelector<HTMLElement>('.nk-group-reel-track') ?? node;
    const key = reelKey(node);
    const existing = [...track.querySelectorAll<HTMLElement>(':scope > .nk-group-reel-column')];
    const reel: Reel = {
        node,
        track,
        key,
        value: Number(node.dataset.reel || 0),
        columns: existing.map(adoptColumn),
        stop: () => undefined,
        nudge: () => null,
    };

    const go = (next: number): void => {
        if (!Number.isFinite(next) || next === reel.value) return;
        const direction = next > reel.value ? 1 : -1;
        const digits = placesOf(next);

        while (reel.columns.length < digits.length) {
            const digit = digits[digits.length - reel.columns.length - 1] ?? 0;
            const column = createColumn(0);
            track.insertBefore(column.el, track.firstChild);
            reel.columns.unshift(column);
            presentColumn(column.el);
            stepColumn(column, digit, direction);
        }

        while (reel.columns.length > digits.length) {
            const column = reel.columns.shift();
            if (column) dismissColumn(column);
        }

        reel.columns.forEach((column, index) => stepColumn(column, digits[index] ?? 0, direction));
        reel.value = next;
        reelMemory.set(key, next);
        if (node.dataset.reel !== String(next)) node.dataset.reel = String(next);
    };

    reel.nudge = (step: number): number | null => {
        const min = node.dataset.min === undefined ? -Infinity : Number(node.dataset.min);
        const max = node.dataset.max === undefined ? Infinity : Number(node.dataset.max);
        const next = Math.min(max, Math.max(min, reel.value + step));
        if (next === reel.value) return null;
        go(next);
        return next;
    };

    const remembered = reelMemory.get(key);
    if (remembered !== undefined && remembered !== reel.value) {
        const target = reel.value;
        reel.value = remembered;
        reel.columns.forEach((column) => window.cancelAnimationFrame(column.frame));
        reel.columns.forEach((column) => column.el.remove());
        reel.columns = placesOf(remembered).map((digit) => {
            const column = createColumn(digit);
            track.append(column.el);
            return column;
        });
        go(target);
    } else {
        reelMemory.set(key, reel.value);
    }

    const observer = new MutationObserver(() => {
        const next = Number(node.dataset.reel);
        if (Number.isFinite(next)) go(next);
    });
    observer.observe(node, { attributes: true, attributeFilter: ['data-reel'] });

    reel.stop = () => {
        observer.disconnect();
        reel.columns.forEach((column) => window.cancelAnimationFrame(column.frame));
        reels.delete(node);
    };
    reels.set(node, reel);
    return reel;
}

interface TextMorph {
    nudge: (step: number) => boolean;
    stop: () => void;
}

const morphs = new WeakMap<HTMLElement, TextMorph>();
const MORPH_EASE = 'linear(0, 0.16 12%, 0.4 24%, 0.65 40%, 0.84 56%, 0.96 72%, 1.03 86%, 1)';
const ENTER_EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';

function morphGlyphsFor(text: string): { char: string; key: string }[] {
    const seen = new Map<string, number>();
    return [...text].map((char) => {
        const count = seen.get(char) ?? 0;
        seen.set(char, count + 1);
        return { char, key: `${char}-${count}` };
    });
}

function morphNode(root: HTMLElement, id?: string | null): HTMLElement | null {
    if (id) return root.querySelector<HTMLElement>(`[data-key="${CSS.escape(id)}"] [data-morph]`);
    return root.querySelector<HTMLElement>('[data-morph]');
}

function reelNode(root: HTMLElement, id?: string | null): HTMLElement | null {
    if (id) return root.querySelector<HTMLElement>(`[data-key="${CSS.escape(id)}"] [data-reel]`);
    return root.querySelector<HTMLElement>('[data-reel]');
}

function mountMorph(node: HTMLElement): TextMorph {
    const frame = node.querySelector<HTMLElement>('.nk-group-morph-frame');
    const track = node.querySelector<HTMLElement>('.nk-group-morph-track');
    let text = node.dataset.morph ?? '';
    let width = 0;
    let settling = false;
    let generation = 0;

    const fit = (next: number, animateWidth: boolean, done?: () => void): void => {
        if (!frame) {
            done?.();
            return;
        }
        const from = width || frame.getBoundingClientRect().width;
        width = next;
        if (!animateWidth || prefersReducedMotion() || Math.abs(next - from) < 0.5) {
            frame.getAnimations().forEach((animation) => animation.cancel());
            frame.style.width = `${next}px`;
            done?.();
            return;
        }
        frame.style.width = `${from}px`;
        const animation = frame.animate(
            [{ width: `${from}px` }, { width: `${next}px` }],
            { duration: PLACE_MS, easing: MORPH_EASE, fill: 'forwards' },
        );
        animation.onfinish = () => {
            frame.style.width = `${next}px`;
            animation.cancel();
            done?.();
        };
    };

    const go = (next: string): void => {
        if (!track || next === text) return;
        const mine = ++generation;
        const previous = morphGlyphsFor(text);
        const upcoming = morphGlyphsFor(next);
        const previousKeys = new Set(previous.map((glyph) => glyph.key));
        const nextKeys = new Set(upcoming.map((glyph) => glyph.key));
        const existing = new Map<string, HTMLElement>();
        track.querySelectorAll<HTMLElement>('.nk-group-morph-glyph').forEach((el) => {
            existing.set(el.dataset.glyph ?? '', el);
        });
        const spots = new Map<string, { left: number; top: number }>();
        existing.forEach((el, key) => spots.set(key, { left: el.offsetLeft, top: el.offsetTop }));

        settling = true;
        existing.forEach((el, key) => {
            if (nextKeys.has(key)) {
                el.getAnimations().forEach((animation) => animation.cancel());
                return;
            }
            const spot = spots.get(key);
            el.style.position = 'absolute';
            el.style.left = `${spot?.left ?? 0}px`;
            el.style.top = `${spot?.top ?? 0}px`;
            if (prefersReducedMotion()) {
                el.remove();
                return;
            }
            const animation = el.animate(
                [
                    { opacity: 1, transform: 'translateY(0) scale(1)', filter: 'blur(0px)' },
                    { opacity: 0, transform: 'translateY(-0.1em) scale(0.86)', filter: 'blur(8px)' },
                ],
                { duration: 140, easing: EASE, fill: 'forwards' },
            );
            animation.onfinish = () => el.remove();
        });

        let entering = 0;
        for (const glyph of upcoming) {
            let el = existing.get(glyph.key);
            const fresh = !el || !previousKeys.has(glyph.key);
            if (!el) {
                el = document.createElement('span');
                el.className = 'nk-group-morph-glyph';
                el.dataset.glyph = glyph.key;
                el.textContent = glyph.char === ' ' ? '\u00a0' : glyph.char;
            }
            track.append(el);
            if (fresh && !prefersReducedMotion()) {
                const order = entering;
                entering += 1;
                el.animate(
                    [
                        { opacity: 0, transform: 'translateY(0.14em) scale(0.8)', filter: 'blur(8px)' },
                        { opacity: 1, transform: 'translateY(0) scale(1)', filter: 'blur(0px)' },
                    ],
                    {
                        duration: 280,
                        delay: 50 + Math.min(order, 8) * 24,
                        easing: ENTER_EASE,
                        fill: 'backwards',
                    },
                );
            }
        }

        if (!prefersReducedMotion()) {
            for (const glyph of upcoming) {
                if (!previousKeys.has(glyph.key)) continue;
                const el = existing.get(glyph.key);
                const spot = spots.get(glyph.key);
                if (!el || !spot) continue;
                const dx = spot.left - el.offsetLeft;
                const dy = spot.top - el.offsetTop;
                if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) continue;
                el.animate(
                    [{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'translate(0px, 0px)' }],
                    { duration: PLACE_MS, easing: MORPH_EASE, fill: 'both' },
                );
            }
        }

        text = next;
        if (node.dataset.morph !== next) node.dataset.morph = next;
        fit(track.offsetWidth, true, () => {
            if (mine === generation) settling = false;
        });
    };

    if (frame && track) fit(track.offsetWidth, false);

    const observer = new MutationObserver(() => {
        if (settling) return;
        const next = node.dataset.morph ?? '';
        if (next !== text) go(next);
    });
    observer.observe(node, { attributes: true, attributeFilter: ['data-morph'] });

    let resizeObserver: ResizeObserver | null = null;
    if (track && typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(() => {
            if (settling || !track) return;
            const next = track.offsetWidth;
            if (Math.abs(next - width) < 0.5) return;
            fit(next, false);
        });
        resizeObserver.observe(track);
    }

    const morph: TextMorph = {
        nudge: (step: number): boolean => {
            let values: string[] = [];
            try {
                const parsed = JSON.parse(node.dataset.values || '[]') as unknown;
                if (Array.isArray(parsed)) values = parsed.map((value) => String(value));
            } catch {
                values = [];
            }
            if (values.length < 2) return false;
            const index = values.indexOf(text);
            const from = index < 0 ? 0 : index;
            const next = values[((from + step) % values.length + values.length) % values.length] ?? text;
            if (next === text) return false;
            go(next);
            return true;
        },
        stop: () => {
            observer.disconnect();
            resizeObserver?.disconnect();
            morphs.delete(node);
        },
    };
    morphs.set(node, morph);
    return morph;
}

function mountReels(root: HTMLElement): () => void {
    const mounted = [...root.querySelectorAll<HTMLElement>('[data-reel]')].map(mountReel);
    const mountedMorphs = [...root.querySelectorAll<HTMLElement>('[data-morph]')].map(mountMorph);
    return () => {
        mounted.forEach((reel) => reel.stop());
        mountedMorphs.forEach((morph) => morph.stop());
    };
}

function inside(node: HTMLElement, x: number, y: number): boolean {
    const rect = node.getBoundingClientRect();
    return x >= rect.left && x < rect.right && y >= rect.top && y < rect.bottom;
}

function boxOf(group: HTMLElement, node: HTMLElement): Box {
    const outer = group.getBoundingClientRect();
    const inner = node.getBoundingClientRect();
    const scale = group.offsetWidth && Math.abs(outer.width - group.offsetWidth) > 1
        ? outer.width / group.offsetWidth
        : 1;

    return {
        x: (inner.left - outer.left) / scale - group.clientLeft + group.scrollLeft,
        y: (inner.top - outer.top) / scale - group.clientTop + group.scrollTop,
        w: inner.width / scale,
        h: inner.height / scale,
    };
}

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraButtonGroup', (config: ButtonGroupConfig = {}) => ({
            disabled: config.disabled ?? false,
            orientation: config.orientation === 'vertical' ? 'vertical' : 'horizontal',
            collapse: config.collapse !== false,
            hover: null as string | null,
            pressed: null as string | null,
            focused: null as string | null,
            open: false,
            compact: false,
            announcement: '',
            confirmId: null as string | null,
            shown: null as string | null,
            fullWidth: 0,
            blockedAt: 0,
            highlightAnim: null as Animation | null,
            timers: {} as Record<string, number>,
            reelStop: null as (() => void) | null,
            resizeObserver: null as ResizeObserver | null,
            collapseObserver: null as ResizeObserver | null,
            release: null as (() => void) | null,

            init(): void {
                const release = () => {
                    this.pressed = null;
                };
                this.release = release;
                window.addEventListener('pointerup', release);
                window.addEventListener('pointercancel', release);

                this.$watch('hover', () => this.syncHighlight());
                this.$watch('pressed', () => this.syncHighlight());
                this.$watch('focused', () => this.syncHighlight());
                this.$watch('open', () => this.syncHighlight());

                const group = this.$root as HTMLElement;
                this.syncRadius();
                this.reelStop = mountReels(group);
                if (typeof ResizeObserver === 'undefined') return;

                this.resizeObserver = new ResizeObserver(() => {
                    this.syncRadius();
                    if (!this.shown) return;
                    const node = this.segment(this.shown);
                    const highlight = this.$refs.highlight as HTMLElement | undefined;
                    if (!node || !highlight) return;
                    this.jump(highlight, boxOf(group, node));
                });
                this.resizeObserver.observe(group);
                this.segments().forEach((node) => this.resizeObserver?.observe(node));

                this.watchCollapse();
            },

            destroy(): void {
                if (this.release) {
                    window.removeEventListener('pointerup', this.release);
                    window.removeEventListener('pointercancel', this.release);
                }
                this.reelStop?.();
                this.resizeObserver?.disconnect();
                this.collapseObserver?.disconnect();
                this.highlightAnim?.cancel();
                for (const timer of Object.values(this.timers)) window.clearTimeout(timer);
            },

            watchCollapse(): void {
                const group = this.$root as HTMLElement;
                const parent = group.parentElement;
                if (!parent || !this.collapse || this.orientation === 'vertical') return;
                if (!this.segments().some((node) => node.hasAttribute('data-collapsible'))) return;

                const check = () => {
                    const box = getComputedStyle(parent);
                    const available = parent.clientWidth - parseFloat(box.paddingLeft) - parseFloat(box.paddingRight);
                    if (!this.compact) {
                        if (group.scrollWidth > group.clientWidth + 1) {
                            this.fullWidth = group.scrollWidth + 2;
                            this.blockedAt = available;
                            this.compact = true;
                        }
                        return;
                    }
                    if (available > this.blockedAt && available >= this.fullWidth) this.compact = false;
                };

                this.collapseObserver = new ResizeObserver(check);
                this.collapseObserver.observe(group);
                this.collapseObserver.observe(parent);
            },

            segments(): HTMLElement[] {
                return [...(this.$root as HTMLElement).querySelectorAll<HTMLElement>(':scope > [data-key]')];
            },

            segment(key: string): HTMLElement | undefined {
                return this.segments().find((node) => node.dataset.key === key);
            },

            activeKey(): string | null {
                return this.hover ?? this.pressed ?? (this.open ? MENU : null) ?? this.focused;
            },

            quiet(key: string): '' | null {
                const keys = this.segments().map((node) => node.dataset.key ?? '');
                const index = keys.indexOf(this.activeKey() ?? '');
                const mine = keys.indexOf(key);
                if (index < 0) return null;
                return mine === index || mine === index + 1 ? '' : null;
            },

            syncRadius(): void {
                const group = this.$root as HTMLElement;
                const style = getComputedStyle(group);
                const radius = parseFloat(style.borderTopLeftRadius) || 0;
                const border = parseFloat(style.borderTopWidth) || 0;
                group.style.setProperty('--nk-group-radius', `${radius}px`);
                group.style.setProperty('--nk-group-inset', `${border + 3}px`);
            },

            inert(node: HTMLElement): boolean {
                return node.hasAttribute('disabled') || node.getAttribute('aria-disabled') === 'true';
            },

            hit(event: PointerEvent): string | null {
                for (const node of this.segments()) {
                    if (!inside(node, event.clientX, event.clientY)) continue;
                    return this.inert(node) ? null : node.dataset.key ?? null;
                }
                return null;
            },

            onPointerMove(event: PointerEvent): void {
                if (event.pointerType === 'touch') return;
                this.hover = this.hit(event);
            },

            onPointerDown(event: PointerEvent): void {
                if (event.button !== 0) return;
                this.pressed = this.hit(event);
            },

            onFocus(event: FocusEvent): void {
                const target = event.target as HTMLElement | null;
                const key = target?.dataset?.key;
                if (!target || key === undefined) return;
                let visible = true;
                try {
                    visible = target.matches(':focus-visible');
                } catch {
                    visible = true;
                }
                this.focused = visible ? key : null;
            },

            onBlur(event: FocusEvent): void {
                const next = event.relatedTarget as Node | null;
                if (!(this.$root as HTMLElement).contains(next)) this.focused = null;
            },

            onKeyDown(event: KeyboardEvent): void {
                if (event.defaultPrevented || this.disabled) return;
                const active = document.activeElement as HTMLElement | null;
                if (event.key === 'ArrowDown' && active?.dataset.key === MENU) {
                    event.preventDefault();
                    if (this.open) this.focusEdge('first');
                    else this.openMenu('first');
                    return;
                }

                const list = this.segments().filter((node) => !node.hasAttribute('disabled'));
                const index = list.findIndex((node) => node === active);
                if (index < 0) return;

                const vertical = this.orientation === 'vertical';
                const rtl = !vertical && getComputedStyle(this.$root as HTMLElement).direction === 'rtl';
                const forward = vertical ? 'ArrowDown' : rtl ? 'ArrowLeft' : 'ArrowRight';
                const backward = vertical ? 'ArrowUp' : rtl ? 'ArrowRight' : 'ArrowLeft';
                const last = list.length - 1;
                const target = event.key === forward
                    ? (index === last ? 0 : index + 1)
                    : event.key === backward
                        ? (index === 0 ? last : index - 1)
                        : event.key === 'Home'
                            ? 0
                            : event.key === 'End'
                                ? last
                                : -1;
                if (target < 0) return;
                event.preventDefault();
                list[target]?.focus();
            },

            syncHighlight(): void {
                const highlight = this.$refs.highlight as HTMLElement | undefined;
                if (!highlight) return;
                const key = this.activeKey();
                const node = key ? this.segment(key) : undefined;
                const reduced = prefersReducedMotion();
                const skip = node && this.inert(node) && key !== this.focused;

                if (!node || skip) {
                    if (this.shown) this.fade(highlight, 0, reduced);
                    this.shown = null;
                    highlight.removeAttribute('data-pressed');
                    return;
                }

                const box = boxOf(this.$root as HTMLElement, node);
                if (!this.shown || reduced) this.jump(highlight, box, this.shown === null);
                else this.glide(highlight, box);
                this.shown = key;

                if (this.pressed && this.pressed === key) highlight.setAttribute('data-pressed', '');
                else highlight.removeAttribute('data-pressed');
            },

            jump(highlight: HTMLElement, box: Box, fadeIn = false): void {
                this.highlightAnim?.cancel();
                highlight.style.transform = `translate(${box.x}px, ${box.y}px)`;
                highlight.style.width = `${box.w}px`;
                highlight.style.height = `${box.h}px`;
                if (!fadeIn) {
                    highlight.style.opacity = '1';
                    return;
                }
                this.fade(highlight, 1, prefersReducedMotion());
            },

            glide(highlight: HTMLElement, box: Box): void {
                const from = getComputedStyle(highlight);
                const fromTransform = from.transform === 'none' ? 'translate(0px, 0px)' : from.transform;
                const fromWidth = `${highlight.offsetWidth}px`;
                const fromHeight = `${highlight.offsetHeight}px`;
                this.highlightAnim?.cancel();
                const next = {
                    transform: `translate(${box.x}px, ${box.y}px)`,
                    width: `${box.w}px`,
                    height: `${box.h}px`,
                    opacity: '1',
                };
                this.highlightAnim = highlight.animate(
                    [
                        {
                            transform: fromTransform,
                            width: fromWidth,
                            height: fromHeight,
                            opacity: 1,
                        },
                        next,
                    ],
                    { duration: 260, easing: SNAPPY, fill: 'forwards' },
                );
                this.highlightAnim.onfinish = () => {
                    highlight.style.transform = next.transform;
                    highlight.style.width = next.width;
                    highlight.style.height = next.height;
                    highlight.style.opacity = '1';
                    this.highlightAnim?.cancel();
                };
            },

            fade(highlight: HTMLElement, opacity: number, reduced: boolean): void {
                const from = getComputedStyle(highlight).opacity;
                this.highlightAnim?.cancel();
                this.highlightAnim = highlight.animate(
                    [{ opacity: from }, { opacity }],
                    { duration: reduced ? 0 : 160, easing: EASE, fill: 'forwards' },
                );
                this.highlightAnim.onfinish = () => {
                    highlight.style.opacity = String(opacity);
                    this.highlightAnim?.cancel();
                };
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
                if (this.disabled) return;
                this.open = true;
                if (focus) this.$nextTick(() => this.focusEdge(focus));
            },

            closeMenu(restore = false): void {
                if (!this.open) return;
                this.open = false;
                if (restore) (this.$refs.trigger as HTMLElement | undefined)?.focus();
            },

            onTriggerClick(event: MouseEvent): void {
                if (this.disabled) return;
                if (this.open) {
                    this.closeMenu(false);
                    return;
                }
                this.openMenu(event.detail === 0 ? 'first' : false);
            },

            roll(id: string | null | undefined, step: number): number | null {
                const node = reelNode(this.$root as HTMLElement, id);
                return node ? reels.get(node)?.nudge(step) ?? null : null;
            },

            morph(id: string | null | undefined, step: number): boolean {
                const node = morphNode(this.$root as HTMLElement, id);
                return node ? morphs.get(node)?.nudge(step) ?? false : false;
            },

            async perform(method: string | null | undefined, value?: number): Promise<void> {
                if (!method) return;
                const wire = (this as unknown as { $wire?: Record<string, unknown> }).$wire;
                if (!wire) return;
                const direct = wire[method];
                if (typeof direct === 'function') {
                    if (value === undefined) await (direct as () => Promise<unknown>)();
                    else await (direct as (next: number) => Promise<unknown>)(value);
                    return;
                }
                const call = wire.$call ?? wire.call;
                if (typeof call === 'function') {
                    if (value === undefined) await (call as (name: string) => Promise<unknown>).call(wire, method);
                    else await (call as (name: string, next: number) => Promise<unknown>).call(wire, method, value);
                }
            },

            select(item: ButtonGroupItem): void {
                if (this.disabled || item.disabled) return;
                let rolled: number | undefined;
                if (typeof item.step === 'number') {
                    const root = this.$root as HTMLElement;
                    if (reelNode(root, item.reel)) {
                        const next = this.roll(item.reel, item.step);
                        if (next === null) return;
                        rolled = next;
                    } else if (!this.morph(item.reel, item.step)) {
                        return;
                    }
                }
                if (item.confirm) {
                    this.confirmId = item.id;
                    this.announcement = item.confirm;
                    window.clearTimeout(this.timers[item.id]);
                    this.timers[item.id] = window.setTimeout(() => {
                        if (this.confirmId === item.id) this.confirmId = null;
                    }, item.resetAfter || 1800);
                }
                void this.perform(item.method, rolled);
            },

            choose(action: { method?: string | null; disabled?: boolean }): void {
                if (this.disabled || action.disabled) return;
                this.closeMenu(true);
                void this.perform(action.method);
            },
        }));
    });
}

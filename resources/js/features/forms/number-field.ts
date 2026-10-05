import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Number field.
 *
 * A spinbutton with stepper buttons, rolling digits, hold-to-repeat, and an
 * optional sideways scrub on the label. Typed drafts commit on Enter or blur.
 */

const DIGIT_MS = 400;

interface NumberFieldConfig {
    value?: number;
    min?: number;
    max?: number | null;
    step?: number;
    largeStep?: number | null;
    prefix?: string;
    suffix?: string;
    suffixOne?: string;
    suffixMany?: string;
    scrub?: boolean;
    disabled?: boolean;
    limitHint?: boolean;
    decimals?: number | null;
    wireProperty?: string | null;
    fieldId?: string;
    hintId?: string;
    descriptionId?: string | null;
}

interface DigitColumn {
    el: HTMLElement;
    glyphs: HTMLElement[];
    digit: number;
    position: number;
    target: number;
    frame: number;
}

interface ReelState {
    structure: string;
    columns: DigitColumn[];
}

interface MotionState {
    strain: Animation | null;
    shake: Animation | null;
    pushes: number;
    warning: number;
}

type Part =
    | { kind: 'sign' | 'dot' }
    | { kind: 'digit'; digit: number };

const reels = new WeakMap<HTMLElement, ReelState>();
const motions = new WeakMap<HTMLElement, MotionState>();

function motionOf(root: HTMLElement): MotionState {
    let current = motions.get(root);
    if (!current) {
        current = { strain: null, shake: null, pushes: 0, warning: 0 };
        motions.set(root, current);
    }
    return current;
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
    el.className = 'nk-num-column';
    el.dataset.digit = String(digit);
    const sizer = document.createElement('span');
    sizer.className = 'nk-num-column-sizer';
    sizer.textContent = '0';
    el.append(sizer);
    const glyphs = Array.from({ length: 10 }, (_, index) => {
        const glyph = document.createElement('span');
        glyph.className = 'nk-num-glyph';
        glyph.textContent = String(index);
        el.append(glyph);
        return glyph;
    });
    const column: DigitColumn = { el, glyphs, digit, position: digit, target: digit, frame: 0 };
    paintColumn(column);
    return column;
}

function partsOf(text: string): Part[] {
    const parts: Part[] = [];
    for (const char of text) {
        if (char === '-') parts.push({ kind: 'sign' });
        else if (char === '.') parts.push({ kind: 'dot' });
        else if (char >= '0' && char <= '9') parts.push({ kind: 'digit', digit: Number(char) });
    }
    return parts.length ? parts : [{ kind: 'digit', digit: 0 }];
}

function syncReels(root: HTMLElement, text: string, direction: number): void {
    const parts = partsOf(text);
    const structure = parts.map((part) => (part.kind === 'digit' ? 'd' : part.kind)).join('');
    const state = reels.get(root);
    if (!state || state.structure !== structure) {
        state?.columns.forEach((column) => window.cancelAnimationFrame(column.frame));
        root.replaceChildren();
        const columns: DigitColumn[] = [];
        for (const part of parts) {
            if (part.kind === 'digit') {
                const column = createColumn(part.digit);
                columns.push(column);
                root.append(column.el);
            } else {
                const mark = document.createElement('span');
                mark.className = 'nk-num-mark';
                mark.textContent = part.kind === 'sign' ? '-' : '.';
                root.append(mark);
            }
        }
        reels.set(root, { structure, columns });
        return;
    }

    const digits = parts.filter((part): part is { kind: 'digit'; digit: number } => part.kind === 'digit');
    state.columns.forEach((column, index) => {
        stepColumn(column, digits[index]?.digit ?? column.digit, direction || 1);
    });
}

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraNumberField', (config: NumberFieldConfig = {}) => ({
            value: Number(config.value ?? 0),
            min: Number(config.min ?? 0),
            max: config.max === null || config.max === undefined ? null as number | null : Number(config.max),
            step: Math.abs(Number(config.step ?? 1)) || 1,
            largeStep: config.largeStep === null || config.largeStep === undefined ? null as number | null : Number(config.largeStep),
            prefix: config.prefix || '',
            suffix: config.suffix || '',
            suffixOne: config.suffixOne || '',
            suffixMany: config.suffixMany || '',
            scrub: config.scrub === true,
            disabled: config.disabled === true,
            limitHint: config.limitHint !== false,
            decimals: config.decimals === null || config.decimals === undefined ? null as number | null : Number(config.decimals),
            wireProperty: config.wireProperty || null,
            fieldId: config.fieldId || '',
            hintId: config.hintId || '',
            descriptionId: config.descriptionId || null,
            editing: false,
            draft: '',
            hint: '',
            hintSticky: false,
            invalid: false,
            live: '',
            booted: false,
            pendingEdge: null as 'min' | 'max' | null,
            hintTimer: 0,
            holdTimer: 0,
            repeatTimer: 0,

            init() {
                if (this.wireProperty && this.$wire) {
                    const current = this.$wire.get(this.wireProperty);
                    if (current !== null && current !== undefined && current !== '') {
                        const next = Number(current);
                        if (!Number.isNaN(next)) this.value = this.clamp(next);
                    }
                }

                this.$watch('value', (next: number, prev: number) => {
                    if (!this.booted) return;
                    const direction = Number(next) > Number(prev) ? 1 : -1;
                    this.paint(direction);
                    this.pushWire();
                    if (this.pendingEdge) {
                        const edge = this.pendingEdge;
                        this.pendingEdge = null;
                        this.showHint(edge);
                        this.announce(edge);
                    } else {
                        this.clearHint();
                        this.announce(null);
                    }
                });

                this.$nextTick(() => {
                    this.paint(0);
                    this.booted = true;
                    if (this.wireProperty && this.$wire) {
                        this.$watch(() => this.$wire.get(this.wireProperty), (next: unknown) => {
                            const parsed = Number(next);
                            if (Number.isNaN(parsed) || parsed === this.value) return;
                            this.value = this.clamp(parsed);
                        });
                    }
                });
            },

            destroy() {
                this.release();
                window.clearTimeout(this.hintTimer);
                const root = this.$refs.reels as HTMLElement | undefined;
                const state = root ? reels.get(root) : undefined;
                state?.columns.forEach((column) => window.cancelAnimationFrame(column.frame));
            },

            precision(): number {
                if (this.decimals !== null && !Number.isNaN(this.decimals)) {
                    return Math.max(0, this.decimals);
                }
                const text = String(this.step);
                const index = text.indexOf('.');
                return index === -1 ? 0 : text.length - index - 1;
            },

            round(n: number): number {
                const factor = 10 ** this.precision();
                return Math.round((n + Number.EPSILON) * factor) / factor;
            },

            clamp(n: number): number {
                let next = this.round(Number(n));
                if (Number.isNaN(next)) next = this.min;
                if (next < this.min) next = this.round(this.min);
                if (this.max !== null && next > this.max) next = this.round(this.max);
                return next;
            },

            displayDigits(): string {
                const negative = this.value < 0;
                const body = Math.abs(this.value).toFixed(this.precision());
                return (negative ? '-' : '') + body;
            },

            figureText(): string {
                if (this.editing) return this.draft === '' ? '0' : this.draft;
                return this.displayDigits();
            },

            affixFor(n: number): string {
                if (this.suffixOne || this.suffixMany) {
                    return Math.abs(n) === 1
                        ? (this.suffixOne || this.suffixMany)
                        : (this.suffixMany || this.suffixOne);
                }
                return this.suffix;
            },

            suffixLabel(): string {
                return this.affixFor(this.value);
            },

            valueText(): string {
                return `${this.prefix}${this.displayDigits()}${this.suffixLabel()}`;
            },

            atMin(): boolean {
                return this.value <= this.min;
            },

            atMax(): boolean {
                return this.max !== null && this.value >= this.max;
            },

            describedBy(): string | null {
                const ids: string[] = [];
                if (this.hint && this.hintId) ids.push(this.hintId);
                if (this.descriptionId) ids.push(this.descriptionId);
                return ids.length ? ids.join(' ') : null;
            },

            paint(direction = 0) {
                const root = this.$refs.reels as HTMLElement | undefined;
                if (root) syncReels(root, this.displayDigits(), direction);
                const input = this.$refs.input as HTMLInputElement | undefined;
                if (input && !this.editing) input.value = this.displayDigits();
                this.$root.classList.add('is-ready');
            },

            pushWire() {
                if (!this.booted || !this.wireProperty || !this.$wire) return;
                if (this.$wire.get(this.wireProperty) === this.value) return;
                this.$wire.set(this.wireProperty, this.value);
            },

            announce(edge: 'min' | 'max' | null) {
                if (!this.booted) return;
                const text = this.valueText();
                this.live = edge === 'min' ? `${text}, minimum` : edge === 'max' ? `${text}, maximum` : text;
            },

            hintText(edge: 'min' | 'max'): string {
                const limit = edge === 'min' ? this.min : (this.max ?? this.min);
                const word = edge === 'min' ? 'Min' : 'Max';
                return `${word} ${this.prefix}${limit}${this.affixFor(limit)}`;
            },

            showHint(edge: 'min' | 'max', sticky = false) {
                if (!this.limitHint) return;
                if (edge === 'max' && this.max === null) return;
                this.hint = this.hintText(edge);
                this.hintSticky = sticky;
                window.clearTimeout(this.hintTimer);
                if (!sticky) {
                    this.hintTimer = window.setTimeout(() => {
                        this.hint = '';
                        this.hintSticky = false;
                    }, 1500);
                }
            },

            clearHint() {
                window.clearTimeout(this.hintTimer);
                this.hint = '';
                this.hintSticky = false;
            },

            commit(next: number) {
                const clamped = this.clamp(next);
                const edge: 'min' | 'max' | null = next < this.min
                    ? 'min'
                    : (this.max !== null && next > this.max ? 'max' : null);
                this.editing = false;
                this.invalid = false;
                this.draft = '';
                if (clamped === this.value) {
                    this.paint(0);
                    if (edge) this.refuse(edge === 'min' ? -1 : 1);
                    else this.clearHint();
                    return;
                }
                this.pendingEdge = edge;
                this.value = clamped;
            },

            stepBy(direction: number, amount?: number) {
                if (this.disabled) return;
                const distance = amount ?? this.step;
                const next = this.round(this.value + direction * distance);
                if ((direction < 0 && this.atMin()) || (direction > 0 && this.atMax())) {
                    this.refuse(direction);
                    return;
                }
                this.commit(next);
            },

            refuse(direction: number) {
                const edge: 'min' | 'max' = direction < 0 ? 'min' : 'max';
                this.showHint(edge);
                this.announce(edge);
                const figure = this.$refs.figure as HTMLElement | undefined;
                const button = (direction < 0 ? this.$refs.dec : this.$refs.inc) as HTMLElement | undefined;
                if (prefersReducedMotion()) {
                    figure?.classList.add('is-warning');
                    const state = motionOf(this.$root);
                    window.clearTimeout(state.warning);
                    state.warning = window.setTimeout(() => figure?.classList.remove('is-warning'), 180);
                    return;
                }
                const state = motionOf(this.$root);
                const px = Math.min(7, 3 + state.pushes * 1.4);
                state.pushes += 1;
                if (figure) {
                    state.strain?.cancel();
                    state.strain = figure.animate(
                        [
                            { transform: 'translateX(0px)' },
                            { transform: `translateX(${direction * px}px)`, offset: 0.38 },
                            { transform: 'translateX(0px)' },
                        ],
                        { duration: 340, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
                    );
                }
                if (button) {
                    state.shake?.cancel();
                    state.shake = button.animate(
                        [
                            { transform: 'translateX(0px)' },
                            { transform: 'translateX(-2px)' },
                            { transform: 'translateX(2px)' },
                            { transform: 'translateX(0px)' },
                        ],
                        { duration: 180, easing: 'ease-out' },
                    );
                }
            },

            hold(event: PointerEvent, direction: number) {
                if (this.disabled || event.button !== 0) return;
                event.preventDefault();
                (event.currentTarget as HTMLElement).setPointerCapture?.(event.pointerId);
                this.release();
                if (this.editing) this.commitDraft();
                this.stepBy(direction, this.step);
                if (prefersReducedMotion()) return;
                let gap = 150;
                const tick = () => {
                    this.stepBy(direction, this.step);
                    gap = Math.max(40, Math.round(gap * 0.82));
                    this.repeatTimer = window.setTimeout(tick, gap);
                };
                this.holdTimer = window.setTimeout(tick, 400);
            },

            release() {
                window.clearTimeout(this.holdTimer);
                window.clearTimeout(this.repeatTimer);
                this.holdTimer = 0;
                this.repeatTimer = 0;
                motionOf(this.$root).pushes = 0;
            },

            onKey(event: KeyboardEvent) {
                if (this.disabled) return;
                if (event.metaKey || event.ctrlKey || event.altKey) return;
                const key = event.key;
                if ((event.target as HTMLElement | null)?.closest?.('.nk-num-step') && (key === 'Enter' || key === ' ')) {
                    return;
                }
                if (this.editing) {
                    if (key === 'Enter') {
                        event.preventDefault();
                        this.commitDraft();
                    } else if (key === 'Escape') {
                        event.preventDefault();
                        this.cancelDraft();
                    }
                    return;
                }
                const large = this.largeStep ?? this.step * 10;
                if (key === 'ArrowUp') {
                    event.preventDefault();
                    this.stepBy(1, event.shiftKey ? large : this.step);
                } else if (key === 'ArrowDown') {
                    event.preventDefault();
                    this.stepBy(-1, event.shiftKey ? large : this.step);
                } else if (key === 'PageUp') {
                    event.preventDefault();
                    this.stepBy(1, large);
                } else if (key === 'PageDown') {
                    event.preventDefault();
                    this.stepBy(-1, large);
                } else if (key === 'Home') {
                    event.preventDefault();
                    this.commit(this.min);
                } else if (key === 'End' && this.max !== null) {
                    event.preventDefault();
                    this.commit(this.max);
                } else if (key === 'Enter') {
                    event.preventDefault();
                    (this.$refs.input as HTMLInputElement | undefined)?.select();
                }
            },

            onFocus() {
                if (this.disabled || this.editing) return;
                (this.$refs.input as HTMLInputElement | undefined)?.select();
            },

            onInput(event: Event) {
                const text = (event.target as HTMLInputElement).value;
                this.editing = true;
                this.draft = text;
                const parsed = Number(text);
                const incomplete = text === '' || text === '-' || text === '.' || text === '-.' || text.endsWith('.');
                const bad = !incomplete && !Number.isNaN(parsed) && (parsed < this.min || (this.max !== null && parsed > this.max));
                this.invalid = bad;
                if (bad) this.showHint(parsed < this.min ? 'min' : 'max', true);
                else if (this.hintSticky) this.clearHint();
            },

            commitDraft() {
                if (!this.editing) return;
                const text = this.draft.trim();
                if (text === '' || text === '-' || text === '.' || text === '-.' || Number.isNaN(Number(text))) {
                    this.cancelDraft();
                    return;
                }
                this.commit(Number(text));
            },

            cancelDraft() {
                this.editing = false;
                this.invalid = false;
                this.draft = '';
                this.clearHint();
                this.paint(0);
            },

            scrubStart(event: PointerEvent) {
                if (!this.scrub || this.disabled || event.button !== 0) return;
                event.preventDefault();
                const target = event.currentTarget as HTMLElement;
                const startX = event.clientX;
                const origin = this.editing ? this.clamp(Number(this.draft) || this.value) : this.value;
                if (this.editing) this.cancelDraft();
                let moved = false;
                target.setPointerCapture?.(event.pointerId);
                const move = (moveEvent: PointerEvent) => {
                    const steps = Math.round((moveEvent.clientX - startX) / 14);
                    if (steps === 0) return;
                    moved = true;
                    const next = this.round(origin + steps * this.step);
                    if (next === this.value) {
                        if ((steps < 0 && this.atMin()) || (steps > 0 && this.atMax())) this.refuse(steps < 0 ? -1 : 1);
                        return;
                    }
                    this.commit(next);
                };
                const end = () => {
                    target.removeEventListener('pointermove', move);
                    target.removeEventListener('pointerup', end);
                    target.removeEventListener('pointercancel', end);
                    if (!moved) (this.$refs.input as HTMLInputElement | undefined)?.focus();
                };
                target.addEventListener('pointermove', move);
                target.addEventListener('pointerup', end);
                target.addEventListener('pointercancel', end);
            },
        }));
    });
}

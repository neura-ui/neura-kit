import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Signature pad.
 *
 * Ink lives in a 600 by 260 space so a stroke scales with the pad.
 * The live stroke writes its path on each pointer move. History keeps
 * the last 60 states. Clear wipes the ink; replay redraws it.
 */

const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
const SVG_NS = 'http://www.w3.org/2000/svg';
const PAD_WIDTH = 600;
const PAD_HEIGHT = 260;
const HISTORY_LIMIT = 60;
const REPLAY_BUDGET = 3200;

const INKS = ['black', 'blue', 'violet'] as const;
const NIBS = ['fine', 'medium', 'bold'] as const;

type Ink = (typeof INKS)[number];
type Nib = (typeof NIBS)[number];

interface Point {
    x: number;
    y: number;
    t: number;
    p: number;
}

interface Stroke {
    color: Ink;
    width: Nib;
    points: Point[];
}

interface Box {
    x: number;
    y: number;
    w: number;
    h: number;
}

interface SignaturePadConfig {
    signer?: string;
    hint?: string;
    ink?: string;
    nib?: string;
    fileName?: string;
    label?: string;
    disabled?: boolean;
    wireProperty?: string | null;
}

const NIB_SIZE: Record<Nib, number> = {
    fine: 3.4,
    medium: 6.4,
    bold: 10.4,
};

const PRINT: Record<Ink, string> = {
    black: '#111111',
    blue: '#1d4ed8',
    violet: '#5b21b6',
};

function isInk(value: string | undefined): value is Ink {
    return INKS.includes(value as Ink);
}

function isNib(value: string | undefined): value is Nib {
    return NIBS.includes(value as Nib);
}

function num(value: number): string {
    return (Math.round(value * 10) / 10).toString();
}

function clone<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

function safeName(name: string): string {
    const clean = name.replace(/[^\w.-]+/g, '-').replace(/^-+|-+$/g, '');
    return clean || 'signature';
}

function streamline(points: Point[]): Point[] {
    if (points.length < 3) return points.map((point) => ({ ...point }));
    const out: Point[] = [{ ...points[0] }];
    let prev = { ...points[0] };
    for (let i = 1; i < points.length; i++) {
        const point = points[i];
        prev = {
            x: prev.x + (point.x - prev.x) * 0.45,
            y: prev.y + (point.y - prev.y) * 0.45,
            t: point.t,
            p: point.p,
        };
        out.push(prev);
    }
    return out;
}

function withPressure(points: Point[]): Point[] {
    const next = points.map((point, index) => {
        if (point.p > 0 && point.p < 0.99) return { ...point };
        const prev = points[Math.max(0, index - 1)];
        const after = points[Math.min(points.length - 1, index + 1)];
        const dist = Math.hypot(after.x - prev.x, after.y - prev.y);
        const dt = Math.max(8, after.t - prev.t);
        const speed = dist / dt;
        return { ...point, p: Math.max(0.16, Math.min(1, 1.18 - speed * 1.05)) };
    });
    for (let i = 1; i < next.length; i++) {
        next[i].p = next[i - 1].p * 0.42 + next[i].p * 0.58;
    }
    return next;
}

function outline(points: Point[], size: number): Point[] {
    const smoothed = withPressure(streamline(points));
    const left: Point[] = [];
    const right: Point[] = [];
    for (let i = 0; i < smoothed.length; i++) {
        const prev = smoothed[Math.max(0, i - 1)];
        const after = smoothed[Math.min(smoothed.length - 1, i + 1)];
        let dx = after.x - prev.x;
        let dy = after.y - prev.y;
        const len = Math.hypot(dx, dy) || 1;
        dx /= len;
        dy /= len;
        const t = i / Math.max(1, smoothed.length - 1);
        const taper = 0.2 + 0.8 * Math.min(Math.min(1, t / 0.07), Math.min(1, (1 - t) / 0.14));
        const radius = (size / 2) * (0.32 + 0.68 * smoothed[i].p) * taper;
        left.push({ x: smoothed[i].x - dy * radius, y: smoothed[i].y + dx * radius, t: 0, p: 0 });
        right.push({ x: smoothed[i].x + dy * radius, y: smoothed[i].y - dx * radius, t: 0, p: 0 });
    }
    return left.concat(right.reverse());
}

export function strokePath(points: Point[], size: number): string {
    if (!points.length) return '';
    if (points.length < 2) {
        const point = points[0];
        const radius = size * 0.34;
        return `M ${num(point.x - radius)} ${num(point.y)} A ${num(radius)} ${num(radius)} 0 1 0 ${num(point.x + radius)} ${num(point.y)} A ${num(radius)} ${num(radius)} 0 1 0 ${num(point.x - radius)} ${num(point.y)} Z`;
    }
    const shape = outline(points, size);
    if (shape.length < 2) return '';
    let d = `M ${num(shape[0].x)} ${num(shape[0].y)}`;
    for (let i = 1; i < shape.length - 1; i++) {
        const mx = (shape[i].x + shape[i + 1].x) / 2;
        const my = (shape[i].y + shape[i + 1].y) / 2;
        d += ` Q ${num(shape[i].x)} ${num(shape[i].y)} ${num(mx)} ${num(my)}`;
    }
    const last = shape[shape.length - 1];
    d += ` L ${num(last.x)} ${num(last.y)} Z`;
    return d;
}

function inkBounds(strokes: Stroke[]): Box {
    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;
    for (const stroke of strokes) {
        const pad = NIB_SIZE[stroke.width] / 2 + 10;
        for (const point of stroke.points) {
            minX = Math.min(minX, point.x - pad);
            minY = Math.min(minY, point.y - pad);
            maxX = Math.max(maxX, point.x + pad);
            maxY = Math.max(maxY, point.y + pad);
        }
    }
    if (!Number.isFinite(minX)) {
        return { x: 0, y: 0, w: PAD_WIDTH, h: PAD_HEIGHT };
    }
    return {
        x: minX,
        y: minY,
        w: Math.max(1, maxX - minX),
        h: Math.max(1, maxY - minY),
    };
}

export function signatureToSvg(strokes: Stroke[]): string {
    if (!strokes.length) return '';
    const box = inkBounds(strokes);
    const paths = strokes
        .map((stroke) => `<path fill="${PRINT[stroke.color]}" d="${strokePath(stroke.points, NIB_SIZE[stroke.width])}"/>`)
        .join('');
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${num(box.x)} ${num(box.y)} ${num(box.w)} ${num(box.h)}" width="${num(box.w)}" height="${num(box.h)}">${paths}</svg>`;
}

export function signatureToPng(strokes: Stroke[], scale = 3): Promise<Blob> {
    const svg = signatureToSvg(strokes);
    if (!svg) return Promise.reject(new Error('empty'));
    const box = inkBounds(strokes);
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
        const image = new Image();
        image.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(box.w * scale));
            canvas.height = Math.max(1, Math.round(box.h * scale));
            const context = canvas.getContext('2d');
            if (!context) {
                URL.revokeObjectURL(url);
                reject(new Error('canvas'));
                return;
            }
            context.scale(scale, scale);
            context.drawImage(image, 0, 0, box.w, box.h);
            URL.revokeObjectURL(url);
            canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('blob'))), 'image/png');
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('svg'));
        };
        image.src = url;
    });
}

onAlpineInit(() => {
    window.Alpine.data('neuraSignaturePad', (config: SignaturePadConfig = {}) => ({
        signer: config.signer ?? '',
        hint: config.hint || 'Sign here',
        ink: (isInk(config.ink) ? config.ink : 'black') as Ink,
        nib: (isNib(config.nib) ? config.nib : 'medium') as Nib,
        fileName: safeName(config.fileName || 'signature'),
        label: config.label || 'Signature pad',
        disabled: !!config.disabled,
        wireProperty: config.wireProperty ?? null,
        strokes: [] as Stroke[],
        history: [[]] as Stroke[][],
        step: 0,
        svgText: '',
        live: '',
        drawing: false,
        replaying: false,
        wiping: false,
        _pointer: -1,
        _current: [] as Point[],
        _live: null as SVGPathElement | null,
        _move: null as ((event: PointerEvent) => void) | null,
        _up: null as ((event: PointerEvent) => void) | null,
        _frame: 0,
        _glideObserver: null as ResizeObserver | null,

        get signed(): boolean {
            return this.drawing || this.strokes.length > 0;
        },

        get padLabel(): string {
            const count = this.strokes.length;
            return `${this.label}, ${count} ${count === 1 ? 'stroke' : 'strokes'}`;
        },

        init() {
            this.$nextTick(() => {
                this.placeGlide(this.$refs.colors, this.$refs.colorGlide, false);
                this.placeGlide(this.$refs.widths, this.$refs.widthGlide, false);
                this._glideObserver = new ResizeObserver(() => {
                    this.placeGlide(this.$refs.colors, this.$refs.colorGlide, false);
                    this.placeGlide(this.$refs.widths, this.$refs.widthGlide, false);
                });
                this._glideObserver.observe(this.$refs.colors);
            });
        },

        destroy() {
            if (this._frame) cancelAnimationFrame(this._frame);
            this.replaying = false;
            this.detachPointer();
            this._glideObserver?.disconnect();
        },

        screenInk(color: Ink): string {
            const pad = this.$refs.pad as HTMLElement | undefined;
            if (!pad) return PRINT[color];
            const token = color === 'black' ? '--nk-sign-ink' : `--nk-sign-${color}`;
            return getComputedStyle(pad).getPropertyValue(token).trim() || PRINT[color];
        },

        placeGlide(group: HTMLElement | undefined, glide: HTMLElement | undefined, animate: boolean) {
            if (!group || !glide) return;
            const active = group.querySelector<HTMLElement>('[aria-checked="true"]');
            if (!active) return;
            const next = {
                transform: `translate(${active.offsetLeft}px, ${active.offsetTop}px)`,
                width: `${active.offsetWidth}px`,
                height: `${active.offsetHeight}px`,
            };
            if (animate && !prefersReducedMotion() && glide.offsetWidth > 0) {
                glide.animate(
                    [{ transform: glide.style.transform || next.transform, width: glide.style.width || next.width, height: glide.style.height || next.height }, next],
                    { duration: 180, easing: EASE, fill: 'forwards' },
                );
            }
            glide.style.transform = next.transform;
            glide.style.width = next.width;
            glide.style.height = next.height;
        },

        setInk(color: Ink) {
            if (this.ink === color) return;
            this.ink = color;
            this.say(`${color} ink`);
            this.$nextTick(() => this.placeGlide(this.$refs.colors, this.$refs.colorGlide, true));
        },

        setNib(nib: Nib) {
            if (this.nib === nib) return;
            this.nib = nib;
            this.say(`${nib} nib`);
            this.$nextTick(() => this.placeGlide(this.$refs.widths, this.$refs.widthGlide, true));
        },

        pointFrom(event: PointerEvent): Point {
            const pad = this.$refs.pad as HTMLElement;
            const rect = pad.getBoundingClientRect();
            const pen = event.pointerType === 'pen' && event.pressure > 0 && event.pressure < 1;
            return {
                x: rect.width ? ((event.clientX - rect.left) / rect.width) * PAD_WIDTH : 0,
                y: rect.height ? ((event.clientY - rect.top) / rect.height) * PAD_HEIGHT : 0,
                t: event.timeStamp,
                p: pen ? event.pressure : 0,
            };
        },

        pushPoint(point: Point) {
            const last = this._current[this._current.length - 1];
            if (last && Math.hypot(point.x - last.x, point.y - last.y) < 0.45) return;
            this._current.push(point);
            this._live?.setAttribute('d', strokePath(this._current, NIB_SIZE[this.nib as Nib]));
            this._live?.setAttribute('fill', this.screenInk(this.ink as Ink));
        },

        start(event: PointerEvent) {
            if (this.disabled || this.wiping || event.button !== 0) return;
            if (this.replaying) this.stopReplay(false);
            event.preventDefault();
            (this.$refs.pad as HTMLElement).focus({ preventScroll: true });
            this.drawing = true;
            this._pointer = event.pointerId;
            this._current = [];
            const path = document.createElementNS(SVG_NS, 'path');
            path.setAttribute('fill', this.screenInk(this.ink as Ink));
            (this.$refs.ink as SVGGElement).append(path);
            this._live = path;
            const coalesced = event.getCoalescedEvents?.() ?? [];
            this.pushPoint(this.pointFrom(event));
            for (const sample of coalesced) this.pushPoint(this.pointFrom(sample));
            this._move = (sample) => {
                if (!this.drawing || sample.pointerId !== this._pointer) return;
                sample.preventDefault();
                const list = sample.getCoalescedEvents?.() ?? [sample];
                for (const item of list) this.pushPoint(this.pointFrom(item));
            };
            this._up = (sample) => {
                if (sample.pointerId !== this._pointer) return;
                this.finish();
            };
            window.addEventListener('pointermove', this._move, { passive: false });
            window.addEventListener('pointerup', this._up);
            window.addEventListener('pointercancel', this._up);
        },

        finish() {
            if (!this.drawing) return;
            const points = this._current.slice();
            this.cancelStroke();
            if (!points.length) return;
            const stroke: Stroke = { color: this.ink, width: this.nib, points };
            this.commit(this.strokes.concat([stroke]), 'Stroke added');
        },

        cancelStroke() {
            this.drawing = false;
            this._current = [];
            this._live?.remove();
            this._live = null;
            this.detachPointer();
        },

        detachPointer() {
            if (this._move) window.removeEventListener('pointermove', this._move);
            if (this._up) {
                window.removeEventListener('pointerup', this._up);
                window.removeEventListener('pointercancel', this._up);
            }
            this._move = null;
            this._up = null;
        },

        commit(next: Stroke[], message: string) {
            const strokes = clone(next);
            let past = this.history.slice(0, this.step + 1).map((item) => clone(item));
            past.push(strokes);
            if (past.length > HISTORY_LIMIT) past = past.slice(past.length - HISTORY_LIMIT);
            this.history = past;
            this.step = past.length - 1;
            this.strokes = strokes;
            this.syncOutput(message);
        },

        undo() {
            if (this.drawing) this.cancelStroke();
            if (this.replaying) this.stopReplay(false);
            if (this.wiping || this.step <= 0) return;
            this.step -= 1;
            this.strokes = clone(this.history[this.step]);
            this.syncOutput('Undone');
        },

        redo() {
            if (this.drawing) this.cancelStroke();
            if (this.replaying) this.stopReplay(false);
            if (this.wiping || this.step >= this.history.length - 1) return;
            this.step += 1;
            this.strokes = clone(this.history[this.step]);
            this.syncOutput('Redone');
        },

        clear() {
            if (this.disabled || this.wiping) return;
            if (this.drawing) this.cancelStroke();
            if (this.replaying) this.stopReplay(false);
            if (!this.strokes.length) return;
            const stage = this.$refs.stage as HTMLElement;
            const wipe = this.$refs.wipe as HTMLElement;
            this.wiping = true;
            if (prefersReducedMotion()) {
                stage.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 120, fill: 'forwards' }).finished.finally(() => {
                    stage.getAnimations().forEach((animation) => animation.cancel());
                    stage.style.opacity = '';
                    this.wiping = false;
                    this.commit([], 'Signature cleared');
                });
                return;
            }
            const motion = { duration: 560, easing: EASE, fill: 'forwards' as FillMode };
            stage.animate(
                [{ clipPath: 'inset(0% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 100%)' }],
                motion,
            );
            wipe.animate(
                [
                    { left: '-16%', opacity: 0 },
                    { left: '6%', opacity: 1, offset: 0.14 },
                    { left: '108%', opacity: 0 },
                ],
                motion,
            ).finished.finally(() => {
                stage.getAnimations().forEach((animation) => animation.cancel());
                wipe.getAnimations().forEach((animation) => animation.cancel());
                stage.style.clipPath = '';
                this.wiping = false;
                this.commit([], 'Signature cleared');
            });
        },

        replay() {
            if (this.disabled || this.wiping || this.replaying || !this.strokes.length) return;
            if (this.drawing) this.cancelStroke();
            const strokes = clone(this.strokes) as Stroke[];
            this.replaying = true;
            this.say('Replaying');
            const ink = this.$refs.ink as SVGGElement;
            const nib = this.$refs.nib as SVGCircleElement;
            if (prefersReducedMotion()) {
                this.paint();
                ink.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 120, fill: 'forwards' }).finished.finally(() => {
                    ink.style.opacity = '';
                    this.replaying = false;
                    this.say('Replay finished');
                });
                return;
            }
            const gap = 120;
            const pieces = strokes.map((stroke) => {
                const pts = stroke.points;
                const raw = pts.length < 2 ? 180 : Math.min(900, Math.max(180, (pts[pts.length - 1]?.t || 0) - (pts[0]?.t || 0) || 280));
                return { stroke, raw };
            });
            const rawTotal = pieces.reduce((sum, piece) => sum + piece.raw, 0) + gap * Math.max(0, pieces.length - 1);
            const scale = rawTotal > REPLAY_BUDGET ? REPLAY_BUDGET / rawTotal : 1;
            let cursor = 0;
            const schedule = pieces.map((piece, index) => {
                const start = cursor;
                const duration = piece.raw * scale;
                cursor += duration + (index < pieces.length - 1 ? gap * scale : 0);
                return { stroke: piece.stroke, start, duration };
            });
            const total = Math.max(cursor, 1);
            const started = performance.now();
            ink.replaceChildren();
            const frame = (now: number) => {
                if (!this.replaying) return;
                const elapsed = now - started;
                if (elapsed >= total) {
                    this.replaying = false;
                    this._frame = 0;
                    nib.style.opacity = '0';
                    this.paint();
                    this.say('Replay finished');
                    return;
                }
                ink.replaceChildren();
                let tip: Point | null = null;
                let tipColor = PRINT.black;
                for (const piece of schedule) {
                    if (elapsed + 0.01 < piece.start) break;
                    const local = piece.duration <= 0 ? 1 : Math.min(1, (elapsed - piece.start) / piece.duration);
                    const count = Math.max(1, Math.ceil(piece.stroke.points.length * local));
                    const partial = piece.stroke.points.slice(0, count);
                    const path = document.createElementNS(SVG_NS, 'path');
                    path.setAttribute('fill', this.screenInk(piece.stroke.color));
                    path.setAttribute('d', strokePath(partial, NIB_SIZE[piece.stroke.width]));
                    ink.append(path);
                    if (local < 1) {
                        tip = partial[partial.length - 1];
                        tipColor = this.screenInk(piece.stroke.color);
                    }
                }
                if (tip) {
                    nib.setAttribute('cx', String(tip.x));
                    nib.setAttribute('cy', String(tip.y));
                    nib.setAttribute('fill', tipColor);
                    nib.style.opacity = '1';
                } else {
                    nib.style.opacity = '0';
                }
                this._frame = requestAnimationFrame(frame);
            };
            this._frame = requestAnimationFrame(frame);
        },

        stopReplay(announce: boolean) {
            this.replaying = false;
            if (this._frame) cancelAnimationFrame(this._frame);
            this._frame = 0;
            const nib = this.$refs.nib as SVGCircleElement | undefined;
            if (nib) nib.style.opacity = '0';
            if (this.$refs.ink) this.paint();
            if (announce) this.say('Replay stopped');
        },

        paint() {
            const ink = this.$refs.ink as SVGGElement | undefined;
            if (!ink) return;
            ink.replaceChildren();
            ink.style.opacity = '';
            for (const stroke of this.strokes) {
                const path = document.createElementNS(SVG_NS, 'path');
                path.setAttribute('fill', this.screenInk(stroke.color));
                path.setAttribute('d', strokePath(stroke.points, NIB_SIZE[stroke.width]));
                ink.append(path);
            }
        },

        syncOutput(message: string) {
            this.svgText = signatureToSvg(this.strokes);
            this.paint();
            this.say(message);
            this.$dispatch('signature-change', { svg: this.svgText, strokes: clone(this.strokes) });
            if (this.wireProperty && this.$wire) {
                this.$wire.set(this.wireProperty, this.svgText);
            }
        },

        say(message: string) {
            this.live = '';
            this.$nextTick(() => {
                this.live = message;
            });
        },

        save(kind: 'png' | 'svg') {
            if (this.replaying) this.stopReplay(false);
            const strokes = clone(this.strokes) as Stroke[];
            if (!strokes.length) {
                this.say('Nothing to export');
                return;
            }
            if (kind === 'svg') {
                this.download(new Blob([signatureToSvg(strokes)], { type: 'image/svg+xml' }), `${this.fileName}.svg`);
                this.say('SVG saved');
                return;
            }
            signatureToPng(strokes)
                .then((blob) => {
                    this.download(blob, `${this.fileName}.png`);
                    this.say('PNG saved');
                })
                .catch(() => this.say('Could not save PNG'));
        },

        download(blob: Blob, name: string) {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = name;
            link.click();
            URL.revokeObjectURL(link.href);
        },

        onKey(event: KeyboardEvent) {
            if (this.disabled) return;
            const key = event.key;
            const meta = event.metaKey || event.ctrlKey;
            if (meta && key.toLowerCase() === 'z') {
                event.preventDefault();
                if (event.shiftKey) this.redo();
                else this.undo();
                return;
            }
            if (meta && key.toLowerCase() === 'y') {
                event.preventDefault();
                this.redo();
                return;
            }
            if (key === 'Escape' && this.replaying) {
                event.preventDefault();
                this.stopReplay(true);
                return;
            }
            if ((key === 'Delete' || key === 'Backspace') && event.target === this.$refs.pad) {
                event.preventDefault();
                this.clear();
                return;
            }
            const target = event.target as HTMLElement | null;
            const radio = target?.closest?.('[role="radio"]') as HTMLElement | null;
            if (!radio) return;
            if (!['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'].includes(key)) return;
            const group = radio.closest('[role="radiogroup"]');
            if (!group) return;
            const radios = [...group.querySelectorAll<HTMLElement>('[role="radio"]')];
            const index = radios.indexOf(radio);
            if (index < 0) return;
            const dir = key === 'ArrowRight' || key === 'ArrowDown' ? 1 : -1;
            const next = radios[(index + dir + radios.length) % radios.length];
            event.preventDefault();
            next.focus();
            next.click();
        },
    }));
});

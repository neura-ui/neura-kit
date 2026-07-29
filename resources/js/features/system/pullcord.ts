/**
 * Ceiling pull-cord theme switch — Verlet rope + distance constraints.
 * Native Alpine + TypeScript (no React).
 */

import { onAlpineInit } from '../../runtime/alpine';

export interface PullCordConfig {
  /** Hang tension / fall speed. */
  gravity: number;
  /** Higher = snappier retract. */
  damping: number;
  /** Rope stiffness (constraint solver passes). */
  iterations: number;
  /** How deep you can pull past rest. */
  stretchMax: number;
  /** Depth at which onPull fires (mid-pull detent). */
  stretchToggle: number;
  /** Cap release flick velocity. */
  maxVelocity: number;
  /** Idle sleep threshold. */
  sleepVelocity: number;
}

export const PULLCORD_DEFAULTS: PullCordConfig = {
  gravity: 1250,
  damping: 0.94,
  iterations: 20,
  stretchMax: 26,
  stretchToggle: 20,
  maxVelocity: 22,
  sleepVelocity: 0.15,
};

const WIDTH = 64;
const CX = WIDTH / 2;
const REST_Y = 176;
const HEIGHT = 340;
const SEGMENTS = 16;
const SEG_LEN = REST_Y / SEGMENTS;
const KNOB_R = 6.5;
const HIT = 46;

type Node = { x: number; y: number; ox: number; oy: number; fixed: boolean };

type ThemeApi = {
  toggle: (origin?: Event | HTMLElement) => void;
  isResolvedToLight: boolean;
};

function themeApi(): ThemeApi | null {
  return (window as unknown as { Theme?: ThemeApi }).Theme ?? null;
}

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function createNodes(): Node[] {
  const nodes: Node[] = [];
  for (let i = 0; i <= SEGMENTS; i++) {
    const y = SEG_LEN * i;
    nodes.push({ x: CX, y, ox: CX, oy: y, fixed: i === 0 });
  }
  return nodes;
}

function pathFromNodes(nodes: Node[]): string {
  let d = `M ${nodes[0].x.toFixed(1)} ${nodes[0].y.toFixed(1)}`;
  for (let i = 1; i < nodes.length - 1; i++) {
    const mx = (nodes[i].x + nodes[i + 1].x) / 2;
    const my = (nodes[i].y + nodes[i + 1].y) / 2;
    d += ` Q ${nodes[i].x.toFixed(1)} ${nodes[i].y.toFixed(1)} ${mx.toFixed(1)} ${my.toFixed(1)}`;
  }
  const last = nodes.length - 1;
  d += ` L ${nodes[last].x.toFixed(1)} ${nodes[last].y.toFixed(1)}`;
  return d;
}

interface PullCordAlpine {
  $el: HTMLElement;
  $refs: {
    path?: SVGPathElement;
    knobGroup?: SVGGElement;
    knob?: HTMLButtonElement;
    inner?: HTMLElement;
  };
  config: PullCordConfig;
  dropping: boolean;
  pulled: boolean;
  ariaLabel: string;
  noEntrance: boolean;
  _nodes: Node[];
  _dragging: boolean;
  _pointerActive: boolean;
  _fired: boolean;
  _grab: { x: number; y: number };
  _panOrigin: { x: number; y: number };
  _raf: number;
  _running: boolean;
  _lastTs: number;
  _lastDt: number;
  _wake: () => void;
  _render: () => void;
  _tick: (ts: number) => void;
  _fire: () => void;
  _impulseClick: () => void;
  _onPointerDown: (e: PointerEvent) => void;
  _onPointerMove: (e: PointerEvent) => void;
  _onPointerUp: (e: PointerEvent) => void;
  _onKeyDown: (e: KeyboardEvent) => void;
  _onClick: (e: MouseEvent) => void;
  _finishEntrance: () => void;
  _syncPulled: () => void;
  _onThemeChanged: () => void;
  _entranceTimer: number;
  init: () => void;
  destroy: () => void;
}

if (typeof document !== 'undefined') {
  const boot = ((window as unknown as Record<string, { booted: boolean }>).__NK_PULLCORD_BOOT__ ??= {
    booted: false,
  });

  if (!boot.booted) {
    boot.booted = true;

    onAlpineInit(() => {
      window.Alpine.data(
        'neuraPullCord',
        (options: unknown = {}) => {
          const opts = (options ?? {}) as {
            gravity?: number;
            damping?: number;
            iterations?: number;
            stretchMax?: number;
            stretchToggle?: number;
            maxVelocity?: number;
            sleepVelocity?: number;
            ariaLabel?: string;
            noEntrance?: boolean;
          };

          return {
            config: { ...PULLCORD_DEFAULTS, ...opts },
            dropping: !(opts.noEntrance ?? false),
            pulled: false,
            ariaLabel: opts.ariaLabel ?? 'Pull the cord',
            noEntrance: opts.noEntrance ?? false,
            _nodes: createNodes(),
            _dragging: false,
            _pointerActive: false,
            _fired: false,
            _grab: { x: CX, y: REST_Y },
            _panOrigin: { x: 0, y: 0 },
            _raf: 0,
            _running: false,
            _lastTs: 0,
            _lastDt: 0,
            _entranceTimer: 0,

            _render(this: PullCordAlpine): void {
              const path = this.$refs.path;
              const group = this.$refs.knobGroup;
              if (path) path.setAttribute('d', pathFromNodes(this._nodes));
              if (group) {
                const tip = this._nodes[this._nodes.length - 1];
                group.setAttribute(
                  'transform',
                  `translate(${(tip.x - CX).toFixed(2)} ${(tip.y - REST_Y).toFixed(2)})`,
                );
              }
            },

            _wake(this: PullCordAlpine): void {
              if (this._running) return;
              this._running = true;
              this._lastTs = 0;
              this._lastDt = 0;
              this._raf = requestAnimationFrame((ts) => this._tick(ts));
            },

            _tick(this: PullCordAlpine, ts: number): void {
              const cfg = this.config;
              const nodes = this._nodes;
              const tip = nodes.length - 1;

              const dt = this._lastTs
                ? Math.min(0.04, Math.max(0.004, (ts - this._lastTs) / 1000))
                : 1 / 60;
              this._lastTs = ts;

              const damp =
                (this._lastDt > 0 ? dt / this._lastDt : 1) *
                Math.pow(cfg.damping, dt * 60);
              const gDt = dt * dt;
              this._lastDt = dt;

              nodes[tip].fixed = this._dragging;

              for (let i = 1; i < nodes.length; i++) {
                const n = nodes[i];
                if (n.fixed) continue;
                const vx = n.x - n.ox;
                const vy = n.y - n.oy;
                n.ox = n.x;
                n.oy = n.y;
                n.x += vx * damp;
                n.y += vy * damp + cfg.gravity * gDt;
              }

              nodes[0].x = CX;
              nodes[0].y = 0;

              if (this._dragging) {
                const end = nodes[tip];
                end.ox = end.x;
                end.oy = end.y;
                end.x = this._grab.x;
                end.y = this._grab.y;
              }

              for (let iter = 0; iter < cfg.iterations; iter++) {
                for (let i = 0; i < tip; i++) {
                  const a = nodes[i];
                  const b = nodes[i + 1];
                  const dx = b.x - a.x;
                  const dy = b.y - a.y;
                  const dist = Math.hypot(dx, dy) || 1e-4;
                  const diff = ((SEG_LEN - dist) / dist) * 0.5;
                  const ox = dx * diff;
                  const oy = dy * diff;
                  if (!a.fixed) {
                    a.x -= ox;
                    a.y -= oy;
                  }
                  if (!b.fixed) {
                    b.x += ox;
                    b.y += oy;
                  }
                }
              }

              this._render();

              let motion = 0;
              for (let i = 1; i < nodes.length; i++) {
                motion +=
                  Math.abs(nodes[i].x - nodes[i].ox) +
                  Math.abs(nodes[i].y - nodes[i].oy);
              }

              if (!this._dragging && motion < cfg.sleepVelocity * dt * 60) {
                this._render();
                this._running = false;
                return;
              }

              this._raf = requestAnimationFrame((next) => this._tick(next));
            },

            _fire(this: PullCordAlpine): void {
              themeApi()?.toggle(this.$refs.knob ?? this.$el);
              this._syncPulled();
            },

            _impulseClick(this: PullCordAlpine): void {
              this._fire();
              if (prefersReducedMotion()) return;
              const tip = this._nodes[this._nodes.length - 1];
              tip.oy -= 22;
              this._wake();
            },

            _onPointerDown(this: PullCordAlpine, e: PointerEvent): void {
              if (prefersReducedMotion()) return;
              if (e.button !== 0) return;
              const knob = this.$refs.knob;
              if (!knob) return;
              knob.setPointerCapture(e.pointerId);
              this._dragging = true;
              this._pointerActive = true;
              this._fired = false;
              this._panOrigin = { x: e.clientX, y: e.clientY };
              this._grab = { x: CX, y: REST_Y };
              this._wake();
            },

            _onPointerMove(this: PullCordAlpine, e: PointerEvent): void {
              if (!this._dragging) return;
              const ox = e.clientX - this._panOrigin.x;
              const oy = REST_Y + (e.clientY - this._panOrigin.y);
              const dist = Math.hypot(ox, oy) || 1e-4;
              const max = REST_Y + this.config.stretchMax;
              const scale = dist > max ? max / dist : 1;
              this._grab = { x: CX + ox * scale, y: oy * scale };

              const detent = Math.min(
                this.config.stretchToggle,
                this.config.stretchMax - 1,
              );
              if (!this._fired && dist - REST_Y >= detent) {
                this._fired = true;
                this._fire();
              }
            },

            _onPointerUp(this: PullCordAlpine, e: PointerEvent): void {
              if (!this._dragging) return;
              this._dragging = false;
              const tip = this._nodes[this._nodes.length - 1];
              const vx = tip.x - tip.ox;
              const vy = tip.y - tip.oy;
              const speed = Math.hypot(vx, vy);
              if (speed > this.config.maxVelocity) {
                const s = this.config.maxVelocity / speed;
                tip.ox = tip.x - vx * s;
                tip.oy = tip.y - vy * s;
              }
              this._wake();
              requestAnimationFrame(() => {
                this._pointerActive = false;
              });
              try {
                this.$refs.knob?.releasePointerCapture(e.pointerId);
              } catch {
                /* already released */
              }
            },

            _onClick(this: PullCordAlpine, e: MouseEvent): void {
              if (this._pointerActive) return;
              // Ignore synthetic clicks after pointer drag; detail===0 is keyboard in some browsers
              if (e.detail === 0) return;
              this._impulseClick();
            },

            _onKeyDown(this: PullCordAlpine, e: KeyboardEvent): void {
              if ((e.key === 'Enter' || e.key === ' ') && !e.repeat) {
                e.preventDefault();
                this._impulseClick();
              }
            },

            _finishEntrance(this: PullCordAlpine): void {
              if (!this.dropping) return;
              this.dropping = false;
              if (prefersReducedMotion()) return;
              const tip = this._nodes[this._nodes.length - 1];
              tip.oy -= 13;
              tip.ox -= 6;
              this._wake();
            },

            _syncPulled(this: PullCordAlpine): void {
              this.pulled = themeApi()?.isResolvedToLight ?? false;
            },

            _onThemeChanged(this: PullCordAlpine): void {
              this._syncPulled();
            },

            init(this: PullCordAlpine): void {
              this._syncPulled();
              this._render();

              const knob = this.$refs.knob;
              if (knob) {
                knob.addEventListener('pointerdown', (e) => this._onPointerDown(e));
                knob.addEventListener('pointermove', (e) => this._onPointerMove(e));
                knob.addEventListener('pointerup', (e) => this._onPointerUp(e));
                knob.addEventListener('pointercancel', (e) => this._onPointerUp(e));
                knob.addEventListener('click', (e) => this._onClick(e));
                knob.addEventListener('keydown', (e) => this._onKeyDown(e));
              }

              window.addEventListener('theme-changed', this._onThemeChanged);

              if (this.noEntrance || prefersReducedMotion()) {
                this.dropping = false;
              } else {
                const inner = this.$refs.inner;
                const onEnd = (e: AnimationEvent): void => {
                  if (e.animationName === 'nk-pullcord-drop') this._finishEntrance();
                };
                inner?.addEventListener('animationend', onEnd);
                this._entranceTimer = window.setTimeout(() => this._finishEntrance(), 1700);
              }
            },

            destroy(this: PullCordAlpine): void {
              if (this._raf) cancelAnimationFrame(this._raf);
              if (this._entranceTimer) clearTimeout(this._entranceTimer);
              window.removeEventListener('theme-changed', this._onThemeChanged);
            },
          };
        },
      );
    });
  }
}

export {};

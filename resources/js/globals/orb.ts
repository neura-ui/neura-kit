/**
 * NeuraKit Orb — an animated "thinking" orb.
 *
 * Six per-state geometries rendered as monochrome dots on a <canvas>:
 *   orbits · globe · rubik · wave · ribbon · morph
 * The dot colour follows the element's CSS `color` (currentColor) and is
 * re-read every frame, so it stays theme-aware in light and dark mode.
 * Registered as an Alpine component: `x-data="neuraOrb({...})"`.
 *
 * Concept & per-state design after the MIT-licensed `thinking-orbs`
 * (github.com/Jakubantalik/thinking-orbs) by Jakub Antalik; this rendering
 * is an original implementation.
 */

const TAU = Math.PI * 2;
const ease = (x: number): number => x * x * (3 - 2 * x);

type Pt = [number, number];
function circlePt(s: number): Pt { const a = s * TAU; return [Math.cos(a), Math.sin(a)]; }
function polyPt(sides: number, rot: number): (s: number) => Pt {
  return (s: number): Pt => {
    const seg = s * sides, i = Math.floor(seg), f = seg - i;
    const a0 = rot + (i / sides) * TAU, a1 = rot + ((i + 1) / sides) * TAU;
    const p0x = Math.cos(a0), p0y = Math.sin(a0), p1x = Math.cos(a1), p1y = Math.sin(a1);
    return [p0x + (p1x - p0x) * f, p0y + (p1y - p0y) * f];
  };
}
const trianglePt = polyPt(3, -Math.PI / 2);
const squarePt = polyPt(4, Math.PI / 4);

type C2 = CanvasRenderingContext2D;

function dot(ctx: C2, x: number, y: number, r: number, a: number, ink: string): void {
  if (a <= 0.015) return;
  ctx.globalAlpha = Math.min(0.8, a);
  ctx.beginPath();
  ctx.arc(x, y, Math.max(0.45, r), 0, TAU);
  ctx.fillStyle = ink;
  ctx.fill();
}

type Mode = (ctx: C2, L: number, t: number, ink: string) => void;

const MODES: Record<string, Mode> = {
  // working — bright particles riding tilted orbits, faint ghost rings behind
  orbits(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.40, K = L / 64, spin = t * 0.5;
    const planes: Pt[] = [[0.4, 0.30], [1.7, -0.55], [2.7, 0.65]];
    const arr: Array<{ x: number; y: number; z: number; ghost: boolean; dim?: boolean }> = [];
    planes.forEach((pl, pi) => {
      const cy = Math.cos(pl[0] + spin * 0.25), sy = Math.sin(pl[0] + spin * 0.25);
      const cx = Math.cos(pl[1]), sx = Math.sin(pl[1]);
      const place = (a: number) => {
        const x = Math.cos(a), y = Math.sin(a), z = 0;
        const y1 = y * cx - z * sx, z1 = y * sx + z * cx;
        const x2 = x * cy - z1 * sy, z2 = x * sy + z1 * cy;
        return { x: x2, y: y1, z: z2 };
      };
      for (let i = 0; i < 30; i++) arr.push({ ...place((i / 30) * TAU), ghost: true });
      arr.push({ ...place(t * 1.25 + pi * 2.1), ghost: false });
      arr.push({ ...place(t * 1.25 + pi * 2.1 + Math.PI), ghost: false, dim: true });
    });
    arr.sort((a, b) => a.z - b.z);
    for (const p of arr) {
      const nd = (p.z + 1) / 2, px = c + p.x * R, py = c - p.y * R;
      if (p.ghost) dot(ctx, px, py, (0.4 + 0.5 * nd) * K, 0.07 + 0.18 * nd, ink);
      else dot(ctx, px, py, (0.9 + 1.2 * nd) * K, (p.dim ? 0.2 : 0.4) + 0.45 * nd, ink);
    }
  },

  // searching — a dotted globe with a bright scan meridian sweeping round
  globe(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.40, K = L / 64, rings = 13, lonD = 30;
    const spin = t * 0.32, scan = (t * 1.1) % TAU, tilt = 0.42;
    const cxT = Math.cos(tilt), sxT = Math.sin(tilt);
    const arr: Array<{ x: number; y: number; z: number; dl: number }> = [];
    for (let i = 0; i < rings; i++) {
      const lat = -1.32 + (i / (rings - 1)) * 2.64, yy = Math.sin(lat), rr = Math.cos(lat);
      const dots = Math.max(4, Math.round(lonD * rr));
      for (let j = 0; j < dots; j++) {
        const lo = (j / dots) * TAU + spin;
        const x = rr * Math.cos(lo), y = yy, z = rr * Math.sin(lo);
        const y2 = y * cxT - z * sxT, z2 = y * sxT + z * cxT;
        const dl = Math.abs(((lo - scan + Math.PI * 3) % TAU) - Math.PI);
        arr.push({ x, y: y2, z: z2, dl });
      }
    }
    arr.sort((a, b) => a.z - b.z);
    for (const p of arr) {
      const nd = (p.z + 1) / 2, px = c + p.x * R, py = c - p.y * R;
      const hi = Math.max(0, 1 - p.dl / 0.46);
      const a = 0.42 * (0.25 + 0.75 * nd) + hi * 0.6 * (0.4 + 0.6 * nd);
      dot(ctx, px, py, (0.5 + 1.15 * nd + hi * 0.5) * K, a, ink);
    }
  },

  // solving — latitude bands scramble in eased quarter-turns
  rubik(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.40, K = L / 64, rings = 12, lonD = 26;
    const spin = t * 0.26, tilt = 0.42, cxT = Math.cos(tilt), sxT = Math.sin(tilt);
    const arr: Array<{ x: number; y: number; z: number }> = [];
    for (let i = 0; i < rings; i++) {
      const lat = -1.28 + (i / (rings - 1)) * 2.56, yy = Math.sin(lat), rr = Math.cos(lat);
      const clock = t * 0.8 + i * 0.37, seg = Math.floor(clock), f = clock - seg;
      const ph = seg * (Math.PI / 2) + (Math.PI / 2) * ease(Math.min(1, f * 2.2));
      const dots = Math.max(4, Math.round(lonD * rr));
      for (let j = 0; j < dots; j++) {
        const lo = (j / dots) * TAU + spin + ph;
        const x = rr * Math.cos(lo), y = yy, z = rr * Math.sin(lo);
        const y2 = y * cxT - z * sxT, z2 = y * sxT + z * cxT;
        arr.push({ x, y: y2, z: z2 });
      }
    }
    arr.sort((a, b) => a.z - b.z);
    for (const p of arr) {
      const nd = (p.z + 1) / 2;
      dot(ctx, c + p.x * R, c - p.y * R, (0.5 + 1.15 * nd) * K, 0.12 + 0.6 * nd, ink);
    }
  },

  // listening — a waveform rolls pole-to-pole across latitude rings
  wave(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.40, K = L / 64, rings = 15, lonD = 22;
    const spin = t * 0.24, tilt = 0.38, cxT = Math.cos(tilt), sxT = Math.sin(tilt);
    const arr: Array<{ x: number; y: number; z: number; bright: number }> = [];
    for (let i = 0; i < rings; i++) {
      const u = i / (rings - 1), lat = -1.3 + u * 2.6;
      const w = Math.sin(u * Math.PI * 3 - t * 2.3);
      const bulge = 1 + 0.10 * w;
      const yy = Math.sin(lat) * bulge, rr = Math.cos(lat) * bulge;
      const bright = 0.30 + 0.55 * (0.5 + 0.5 * w);
      const dots = Math.max(4, Math.round(lonD * Math.cos(lat)));
      for (let j = 0; j < dots; j++) {
        const lo = (j / dots) * TAU + spin;
        const x = rr * Math.cos(lo), y = yy, z = rr * Math.sin(lo);
        const y2 = y * cxT - z * sxT, z2 = y * sxT + z * cxT;
        arr.push({ x, y: y2, z: z2, bright });
      }
    }
    arr.sort((a, b) => a.z - b.z);
    for (const p of arr) {
      const nd = (p.z + 1) / 2;
      dot(ctx, c + p.x * R, c - p.y * R, (0.5 + 1.0 * nd) * K, p.bright * (0.4 + 0.6 * nd), ink);
    }
  },

  // composing — an undulating multi-lane ribbon crossing the field
  ribbon(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.42, K = L / 64, lanes = 3, segs = 48;
    const arr: Array<{ x: number; y: number; z: number }> = [];
    for (let li = 0; li < lanes; li++) {
      const off = (li - (lanes - 1) / 2) * 0.16;
      for (let s = 0; s < segs; s++) {
        const u = s / (segs - 1), ang = u * TAU;
        const wob = 0.5 + 0.22 * Math.sin(ang * 2 + t * 1.4) + 0.12 * Math.sin(ang * 3 - t * 1.1);
        const rad = wob + off;
        const x = Math.cos(ang) * rad;
        const y = Math.sin(ang) * rad * 0.62;
        const z = Math.sin(ang * 2 + t * 1.4) * 0.5;
        arr.push({ x, y, z });
      }
    }
    arr.sort((a, b) => a.z - b.z);
    for (const p of arr) {
      const nd = (p.z + 1) / 2;
      dot(ctx, c + p.x * R, c - p.y * R, (0.55 + 0.9 * nd) * K, 0.18 + 0.55 * nd, ink);
    }
  },

  // shaping — a dotted outline morphs circle → triangle → square
  morph(ctx, L, t, ink) {
    const c = L / 2, R = L * 0.38, K = L / 64, N = 58;
    const shapes = [circlePt, trianglePt, squarePt];
    const clock = t * 0.32, idx = Math.floor(clock) % 3, nx = (idx + 1) % 3;
    const f = ease(clock - Math.floor(clock)), rot = t * 0.12;
    const cr = Math.cos(rot), sr = Math.sin(rot);
    for (let i = 0; i < N; i++) {
      const s = i / N, a = shapes[idx](s), b = shapes[nx](s);
      const x = a[0] + (b[0] - a[0]) * f, y = a[1] + (b[1] - a[1]) * f;
      const xr = x * cr - y * sr, yr = x * sr + y * cr;
      const tw = 0.55 + 0.45 * Math.sin(i * 0.5 + t * 1.6);
      dot(ctx, c + xr * R, c - yr * R, 1.15 * K, 0.3 + 0.45 * tw, ink);
    }
  },
};

if (typeof document !== 'undefined') {
  const boot = ((window as unknown as Record<string, { booted: boolean }>).__NK_ORB_BOOT__ ??= { booted: false });
  if (!boot.booted) {
    boot.booted = true;
    document.addEventListener('alpine:init', () => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const Alpine = (window as any).Alpine;

      Alpine.data('neuraOrb', (config: { mode?: string; speed?: number }) => ({
        raf: 0,
        booted: false,

        init(this: { $el: HTMLElement; raf: number; booted: boolean }) {
          if (this.booted) return;
          this.booted = true;
          const el = this.$el;
          const canvas = (el instanceof HTMLCanvasElement ? el : el.querySelector('canvas')) as HTMLCanvasElement | null;
          if (!canvas) return;

          const dpr = Math.min(2, window.devicePixelRatio || 1);
          const L = 80;
          canvas.width = L * dpr;
          canvas.height = L * dpr;
          const ctx = canvas.getContext('2d');
          if (!ctx) return;
          ctx.scale(dpr, dpr);

          const mode = MODES[config.mode || 'globe'] || MODES.globe;
          const speed = config.speed || 1;
          const reduce = typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches;
          const start = performance.now();

          // Re-read every frame so dots follow theme / text-* color changes (light ↔ dark).
          const readInk = (): string =>
            getComputedStyle(canvas).color
            || getComputedStyle(document.documentElement).color
            || '#737373';

          const frame = (now: number): void => {
            const t = ((now - start) / 1000) * speed;
            ctx.clearRect(0, 0, L, L);
            mode(ctx, L, t, readInk());
            this.raf = requestAnimationFrame(frame);
          };

          if (reduce) { ctx.clearRect(0, 0, L, L); mode(ctx, L, 0.6, readInk()); }
          else this.raf = requestAnimationFrame(frame);
        },

        destroy(this: { raf: number }) {
          if (this.raf) cancelAnimationFrame(this.raf);
        },
      }));
    });
  }
}

export {};

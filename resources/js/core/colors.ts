import type { TailwindColorName, ColorScale, ColorBase, ColorStep } from './types';

export const TAILWIND_COLORS: Record<TailwindColorName, ColorBase> = {
  slate: { h: 215, c: 0.025 },
  gray: { h: 220, c: 0.015 },
  zinc: { h: 240, c: 0.01 },
  neutral: { h: 0, c: 0 },
  stone: { h: 30, c: 0.015 },
  red: { h: 25, c: 0.25 },
  orange: { h: 50, c: 0.22 },
  amber: { h: 70, c: 0.2 },
  yellow: { h: 85, c: 0.19 },
  lime: { h: 125, c: 0.22 },
  green: { h: 145, c: 0.2 },
  emerald: { h: 160, c: 0.18 },
  teal: { h: 175, c: 0.16 },
  cyan: { h: 195, c: 0.17 },
  sky: { h: 220, c: 0.18 },
  blue: { h: 245, c: 0.22 },
  indigo: { h: 270, c: 0.2 },
  violet: { h: 290, c: 0.22 },
  purple: { h: 300, c: 0.24 },
  fuchsia: { h: 320, c: 0.26 },
  pink: { h: 345, c: 0.22 },
  rose: { h: 10, c: 0.24 },
} as const;

const LIGHTNESS: Record<ColorStep, number> = {
  50: 0.97,
  100: 0.93,
  200: 0.86,
  300: 0.76,
  400: 0.66,
  500: 0.58,
  600: 0.52,
  700: 0.44,
  800: 0.36,
  900: 0.3,
  950: 0.2,
} as const;

const CHROMA_SCALE: Record<ColorStep, number> = {
  50: 0.08,
  100: 0.12,
  200: 0.25,
  300: 0.45,
  400: 0.7,
  500: 1,
  600: 0.95,
  700: 0.8,
  800: 0.6,
  900: 0.45,
  950: 0.35,
} as const;

export function generateColorScale(color: TailwindColorName): ColorScale {
  const base = TAILWIND_COLORS[color] ?? TAILWIND_COLORS.neutral;

  const scale = {} as ColorScale;
  const steps: ColorStep[] = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
  for (const step of steps) {
    const l = LIGHTNESS[step];
    const c = base.c * CHROMA_SCALE[step];
    scale[step] = `oklch(${l.toFixed(2)} ${c.toFixed(4)} ${base.h})`;
  }

  return scale;
}

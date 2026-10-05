import { TAILWIND_COLORS } from './colors.ts';
import { DEFAULT_THEME } from './defaults.ts';
import type { NeuraKitConfig, SurfaceStyle, TailwindColorName } from './types.ts';

/** How much of the hue is allowed into surfaces. Neutral stays gray. */
const STYLE_MIX: Record<SurfaceStyle, number> = {
  neutral: 0,
  muted: 0.35,
  tint: 0.7,
  vivid: 1,
};

const SURFACE_STYLES = new Set<string>(Object.keys(STYLE_MIX));

function clamp01(value: number | undefined, fallback: number): number {
  if (value === undefined || Number.isNaN(value)) return fallback;
  return Math.min(1, Math.max(0, value));
}

function channel(value: number, digits: number): string {
  return value
    .toFixed(digits)
    .replace(/(\.\d*?)0+$/, '$1')
    .replace(/\.$/, '');
}

function paint(l: number, c: number, h: number, alpha?: number): string {
  const body = `oklch(${channel(Math.min(1, Math.max(0, l)), 3)} ${channel(Math.max(0, c), 4)} ${channel(((h % 360) + 360) % 360, 0)}`;
  if (alpha === undefined) return `${body})`;
  return `${body} / ${channel(alpha, 2)})`;
}

function chroma(baseC: number, cap: number, style: SurfaceStyle): number {
  const mix = STYLE_MIX[style];
  if (mix === 0) return 0;
  const strength = baseC > 0.01 ? Math.min(1, baseC / 0.18) : 1;
  return cap * mix * strength;
}

function accentChroma(baseC: number, target: number): number {
  if (baseC <= 0.01) return target;
  return target * Math.min(1, baseC / 0.16);
}

function resolveColor(name: TailwindColorName | undefined): { h: number; c: number } {
  if (name && name in TAILWIND_COLORS) return TAILWIND_COLORS[name];
  return TAILWIND_COLORS[DEFAULT_THEME.colors.primary];
}

/**
 * Build the light and dark surface tokens from one hue.
 * `light` and `dark` shift the whole ramp together, so borders and text
 * stay in step with the canvas instead of being tuned one variable at a time.
 */
export function generateSurfaceCSS(config: NeuraKitConfig = {}): string {
  if (!config.appearance) return '';

  const style: SurfaceStyle = SURFACE_STYLES.has(config.appearance.style ?? '')
    ? (config.appearance.style as SurfaceStyle)
    : 'neutral';
  const light = clamp01(config.appearance.light, 1);
  const dark = clamp01(config.appearance.dark, 0.7);
  const picked = resolveColor(
    config.appearance.color ?? config.colors?.primary ?? DEFAULT_THEME.colors.primary,
  );
  // A gray primary has no hue to follow. Styles other than neutral still
  // need one, otherwise muted / tint / vivid look identical to the stock gray.
  const h = picked.c > 0.01 || style === 'neutral' ? picked.h : 250;
  const baseC = picked.c > 0.01 || style === 'neutral' ? picked.c : 0.18;

  // 1 stays white. 0.92 lands near 0.97, enough to read as paper rather than pure white.
  const lightL = Math.max(0.9, 1 - (1 - light) * 0.4);
  // 0.7 stays near the stock dark (0.14). 0.62 lifts it; 1 pushes toward black.
  const darkL = 0.26 - dark * 0.17;
  const canvasC = {
    light: chroma(baseC, 0.028, style),
    dark: chroma(baseC, 0.046, style),
  };
  const edgeC = chroma(baseC, 0.032, style);
  const fgC = {
    light: chroma(baseC, 0.02, style),
    dark: chroma(baseC, 0.015, style),
  };

  const lightVars: Record<string, string> = {
    '--nk-surface': paint(lightL, canvasC.light, h),
    '--nk-surface-raised': paint(lightL - 0.01, canvasC.light * 0.8, h),
    '--nk-surface-inset': paint(lightL - 0.038, canvasC.light * 1.1, h),
    '--nk-overlay': paint(0, 0, 0, 0.4),
    '--nk-hover': paint(lightL - 0.03, canvasC.light, h),
    '--nk-active': paint(lightL - 0.055, canvasC.light * 1.15, h),
    '--nk-edge': paint(lightL - 0.12, edgeC, h),
    '--nk-edge-hover': paint(lightL - 0.18, edgeC * 1.1, h),
    '--nk-edge-focus': paint(lightL - 0.3, edgeC * 1.25, h),
    '--nk-separator': paint(lightL - 0.07, edgeC * 0.7, h),
    '--nk-fg': paint(0.145, fgC.light, h),
    '--nk-fg-secondary': paint(0.44, fgC.light * 0.7, h),
    '--nk-fg-muted': paint(0.556, fgC.light * 0.45, h),
    '--nk-fg-disabled': paint(0.652, fgC.light * 0.25, h),
    '--nk-fg-on-emphasis': paint(1, 0, h),
    '--nk-accent-surface': paint(0.97, accentChroma(baseC, 0.012), h),
    '--nk-accent-surface-dark': paint(0.16, accentChroma(baseC, 0.025), h),
    '--nk-accent-separator': paint(0.9, accentChroma(baseC, 0.02), h),
  };

  const darkVars: Record<string, string> = {
    '--nk-surface': paint(darkL, canvasC.dark, h),
    '--nk-surface-raised': paint(darkL + 0.04, canvasC.dark * 0.85, h),
    '--nk-surface-inset': paint(darkL - 0.005, canvasC.dark * 1.05, h),
    '--nk-overlay': paint(0, 0, 0, 0.65),
    '--nk-hover': paint(1, 0, h, 0.06),
    '--nk-active': paint(1, 0, h, 0.09),
    '--nk-edge': paint(1, 0, h, 0.09),
    '--nk-edge-hover': paint(1, 0, h, 0.13),
    '--nk-edge-focus': paint(1, 0, h, 0.18),
    '--nk-separator': paint(1, 0, h, 0.06),
    '--nk-fg': paint(0.93, fgC.dark, h),
    '--nk-fg-secondary': paint(0.652, fgC.dark * 0.65, h),
    '--nk-fg-muted': paint(0.556, fgC.dark * 0.4, h),
    '--nk-fg-disabled': paint(0.44, fgC.dark * 0.25, h),
    '--nk-fg-on-emphasis': paint(1, 0, h),
    '--nk-accent-surface': paint(0.97, accentChroma(baseC, 0.012), h),
    '--nk-accent-surface-dark': paint(0.16, accentChroma(baseC, 0.025), h),
    '--nk-accent-separator': paint(0.9, accentChroma(baseC, 0.02), h),
  };

  // Higher specificity than the kit's :root / .dark, so this ramp wins.
  return `${block('html:root', lightVars)}\n${block(':root.dark, html.dark', darkVars)}`;
}

function block(selector: string, vars: Record<string, string>): string {
  const lines = Object.entries(vars).map(([name, value]) => `  ${name}: ${value};`);
  return `${selector} {\n${lines.join('\n')}\n}`;
}

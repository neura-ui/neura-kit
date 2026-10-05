import type { Plugin } from 'vite';

export type TailwindColorName =
  | 'slate'
  | 'gray'
  | 'zinc'
  | 'neutral'
  | 'stone'
  | 'red'
  | 'orange'
  | 'amber'
  | 'yellow'
  | 'lime'
  | 'green'
  | 'emerald'
  | 'teal'
  | 'cyan'
  | 'sky'
  | 'blue'
  | 'indigo'
  | 'violet'
  | 'purple'
  | 'fuchsia'
  | 'pink'
  | 'rose';

export type ColorStep = 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 | 950;

export type RadiusValue = 'none' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | 'full';

export type SemanticColorName = 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info';

/** How strongly surfaces borrow the chosen hue. */
export type SurfaceStyle = 'neutral' | 'muted' | 'tint' | 'vivid';

export interface NeuraKitAppearance {
  /** neutral stays gray. muted, tint and vivid pull surfaces toward the hue. */
  style?: SurfaceStyle;
  /**
   * Light canvas, from 0 (softer paper) to 1 (white).
   * Stock Neura is 1.
   */
  light?: number;
  /**
   * Dark canvas, from 0 (lifted charcoal) to 1 (near black).
   * Stock Neura is 0.7.
   */
  dark?: number;
  /** Hue used to tint surfaces. Defaults to the primary color. */
  color?: TailwindColorName;
}

export type RadiusName =
  | 'field'
  | 'box'
  | 'button'
  | 'badge'
  | 'input'
  | 'card'
  | 'modal'
  | 'dropdown';

export type FontName = 'sans' | 'serif' | 'mono';

export type ColorScale = {
  [K in ColorStep]: string;
};

export interface ColorBase {
  readonly h: number;
  readonly c: number;
}

export interface NeuraKitColors {
  primary: TailwindColorName;
  secondary: TailwindColorName;
  success: TailwindColorName;
  warning: TailwindColorName;
  danger: TailwindColorName;
  info: TailwindColorName;
}

export type NeuraKitRadius = {
  [K in RadiusName]: RadiusValue;
};

export type NeuraKitFont = {
  [K in FontName]?: string;
};

export interface NeuraKitTheme {
  colors: NeuraKitColors;
  radius: NeuraKitRadius;
  font: NeuraKitFont;
}

export type NeuraKitConfig = Partial<{
  colors: Partial<NeuraKitColors>;
  radius: Partial<NeuraKitRadius>;
  font: Partial<NeuraKitFont>;
  appearance: NeuraKitAppearance;
}>;

export type NeuraKitUserConfig = NeuraKitConfig;

export interface CSSVars {
  readonly [key: string]: string;
}

export interface TransformResult {
  readonly code: string;
  readonly map: null;
}

export type NeuraKitPlugin = (userConfig?: NeuraKitUserConfig) => Plugin;


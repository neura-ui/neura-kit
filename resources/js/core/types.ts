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


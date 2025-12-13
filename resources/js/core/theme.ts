import { generateColorScale, TAILWIND_COLORS } from './colors';
import { DEFAULT_THEME } from './defaults';
import type { NeuraKitConfig, NeuraKitTheme, SemanticColorName, TailwindColorName } from './types';

export function generateThemeCSS(userConfig: NeuraKitConfig = {}): string {
  const config: NeuraKitTheme = {
    colors: { ...DEFAULT_THEME.colors, ...userConfig.colors },
    radius: { ...DEFAULT_THEME.radius, ...userConfig.radius },
    font: { ...DEFAULT_THEME.font, ...userConfig.font },
  };

  let css = '@theme {\n';

  for (const [name, value] of Object.entries(config.font)) {
    if (value) {
      css += `  --font-${name}: ${value};\n`;
    }
  }

  css += '\n';

  for (const [semantic, color] of Object.entries(config.colors)) {
    const scale = generateColorScale(color as TailwindColorName);
    for (const [step, value] of Object.entries(scale)) {
      css += `  --color-${semantic}-${step}: ${value};\n`;
    }
    css += '\n';
  }

  for (const [name, value] of Object.entries(config.radius)) {
    css += `  --radius-${name}: var(--radius-${value});\n`;
  }

  return css + '}\n';
}

export function generateSemanticColors(
  colors: Partial<Record<SemanticColorName, TailwindColorName>> = {}
): Record<string, string> {
  const defaults: Record<SemanticColorName, TailwindColorName> = {
    primary: 'neutral',
    secondary: 'slate',
    success: 'green',
    warning: 'amber',
    danger: 'red',
    info: 'blue',
  };

  const merged = { ...defaults, ...colors };
  const cssVars: Record<string, string> = {};

  for (const [semantic, colorName] of Object.entries(merged)) {
    const scale = generateColorScale(colorName);
    for (const [step, value] of Object.entries(scale)) {
      cssVars[`--color-${semantic}-${step}`] = value;
    }
  }

  return cssVars;
}

export function cssVarsToString(
  vars: Record<string, string>,
  selector = ':root'
): string {
  let css = `${selector} {\n`;
  for (const [name, value] of Object.entries(vars)) {
    css += `  ${name}: ${value};\n`;
  }
  css += '}\n';
  return css;
}

export function getAvailableColors(): TailwindColorName[] {
  return Object.keys(TAILWIND_COLORS) as TailwindColorName[];
}

export function isValidColor(colorName: string): colorName is TailwindColorName {
  return colorName in TAILWIND_COLORS;
}

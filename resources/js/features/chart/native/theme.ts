import type { NativeThemeColors } from './types';

export const DEFAULT_PALETTE = [
  'rgb(99, 102, 241)',
  'rgb(16, 185, 129)',
  'rgb(244, 63, 94)',
  'rgb(245, 158, 11)',
  'rgb(59, 130, 246)',
  'rgb(168, 85, 247)',
];

export function resolveTheme(isDark: boolean, override: Partial<NativeThemeColors> = {}): NativeThemeColors {
  const base: NativeThemeColors = isDark
    ? {
        text: 'rgba(255,255,255,0.85)',
        muted: 'rgba(255,255,255,0.45)',
        grid: 'rgba(255,255,255,0.06)',
        tooltipBg: 'rgba(24,24,27,0.95)',
        tooltipText: 'rgba(255,255,255,0.9)',
        tooltipBorder: 'rgba(255,255,255,0.1)',
        surface: 'rgba(24,24,27,1)',
      }
    : {
        text: 'rgba(0,0,0,0.8)',
        muted: 'rgba(0,0,0,0.45)',
        grid: 'rgba(0,0,0,0.06)',
        tooltipBg: 'rgba(255,255,255,0.97)',
        tooltipText: 'rgba(0,0,0,0.85)',
        tooltipBorder: 'rgba(0,0,0,0.08)',
        surface: 'rgba(255,255,255,1)',
      };

  return { ...base, ...override };
}

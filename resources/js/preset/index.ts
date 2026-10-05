export { generateColorScale, TAILWIND_COLORS } from '../core/colors.ts';
export {
  generateSemanticColors,
  generateThemeCSS,
  cssVarsToString,
  getAvailableColors,
  isValidColor,
} from '../core/theme.ts';
export { generateSurfaceCSS } from '../core/surfaces.ts';
export type { SurfaceStyle, NeuraKitAppearance } from '../core/types.ts';
export { DEFAULT_THEME } from '../core/defaults.ts';
export {
  motionTokens,
  prefersReducedMotion,
  motionDuration,
} from '../shared/motion-tokens.ts';
export type {
  MotionSpring,
  MotionDuration,
  MotionEase,
} from '../shared/motion-tokens.ts';

/**
 * Theme switch View Transition animations.
 *
 * Adapted for Alpine / Neura Kit from the MIT-licensed
 * https://github.com/MinhOmega/react-theme-switch-animation
 * (polygon / GIF ideas also inspired by rudrodip/theme-toggle-effect).
 */

export type ThemeAnimationType =
  | 'circle'
  | 'blur-circle'
  | 'qr-scan'
  | 'polygon'
  | 'polygon-gradient'
  | 'gif'
  | 'none';

export type ThemeOrigin = Event | HTMLElement | { x: number; y: number } | null | undefined;

export interface ThemeAnimationConfig {
  duration?: number;
  easing?: string;
  pseudoElement?: string;
  globalClassName?: string;
  animationType?: ThemeAnimationType;
  blurAmount?: number;
  gifUrl?: string;
  styleId?: string;
}

export interface ThemeTransitionOptions extends ThemeAnimationConfig {
  origin?: ThemeOrigin;
  /** Resolved theme after the update — used by polygon direction. */
  willBeDark?: boolean;
}

type Point = { x: number; y: number };

const EXPO_OUT_EASING =
  'linear(' +
  '0 0%, 0.1684 2.66%, 0.3165 5.49%, 0.446 8.52%,' +
  '0.5581 11.78%, 0.6535 15.29%, 0.7341 19.11%,' +
  '0.8011 23.3%, 0.8557 27.93%, 0.8962 32.68%,' +
  '0.9283 38.01%, 0.9529 44.08%, 0.9711 51.14%,' +
  '0.9833 59.06%, 0.9915 68.74%, 1 100%' +
  ')';

const EXPO_IN_EASING =
  'linear(' +
  '0 0%, 0.0085 31.26%, 0.0167 40.94%, 0.0289 48.86%,' +
  '0.0471 55.92%, 0.0717 61.99%, 0.1038 67.32%,' +
  '0.1443 72.07%, 0.1989 76.7%, 0.2659 80.89%,' +
  '0.3465 84.71%, 0.4419 88.22%, 0.554 91.48%,' +
  '0.6835 94.51%, 0.8316 97.34%, 1 100%' +
  ')';

const defaults: Required<
  Pick<
    ThemeAnimationConfig,
    | 'duration'
    | 'easing'
    | 'pseudoElement'
    | 'globalClassName'
    | 'animationType'
    | 'blurAmount'
    | 'styleId'
  >
> & { gifUrl?: string } = {
  duration: 750,
  easing: 'ease-in-out',
  pseudoElement: '::view-transition-new(root)',
  globalClassName: 'dark',
  animationType: 'circle',
  blurAmount: 2,
  styleId: 'nk-theme-switch-style',
  gifUrl: undefined,
};

let baseStylesInjected = false;

type ViewTransitionLike = { ready: Promise<void> };

function isHighResolution(): boolean {
  return window.innerWidth >= 3000 || window.innerHeight >= 2000;
}

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function injectBaseStyles(): void {
  if (baseStylesInjected || typeof document === 'undefined') return;
  const id = 'nk-theme-switch-base-style';
  if (document.getElementById(id)) {
    baseStylesInjected = true;
    return;
  }

  const style = document.createElement('style');
  style.id = id;
  const hi = isHighResolution();
  style.textContent = `
    ::view-transition-old(root),
    ::view-transition-new(root) {
      animation: none;
      mix-blend-mode: normal;
      ${hi ? 'transform: translateZ(0);' : ''}
    }
    ${
      hi
        ? `
    ::view-transition-group(root),
    ::view-transition-image-pair(root),
    ::view-transition-old(root),
    ::view-transition-new(root) {
      backface-visibility: hidden;
      perspective: 1000px;
      transform: translate3d(0, 0, 0);
    }`
        : ''
    }
  `;
  document.head.appendChild(style);
  baseStylesInjected = true;
}

function resolveOrigin(origin: ThemeOrigin): Point {
  if (origin && typeof origin === 'object' && 'x' in origin && 'y' in origin && !('target' in origin)) {
    return { x: origin.x, y: origin.y };
  }

  let el: HTMLElement | null = null;
  if (origin instanceof HTMLElement) el = origin;
  else if (origin instanceof Event) {
    const t = origin.currentTarget ?? origin.target;
    if (t instanceof HTMLElement) el = t;
  }

  if (el) {
    const { top, left, width, height } = el.getBoundingClientRect();
    return { x: left + width / 2, y: top + height / 2 };
  }

  return { x: window.innerWidth / 2, y: window.innerHeight / 2 };
}

function resolveAnimationType(
  origin: ThemeOrigin,
  override?: ThemeAnimationType,
): ThemeAnimationType {
  if (override) return override;

  let el: HTMLElement | null = null;
  if (origin instanceof HTMLElement) el = origin;
  else if (origin instanceof Event) {
    const t = origin.currentTarget ?? origin.target;
    if (t instanceof HTMLElement) el = t;
  }

  const attr = el?.closest('[data-nk-theme-animation]')?.getAttribute('data-nk-theme-animation');
  if (attr) return attr as ThemeAnimationType;

  return defaults.animationType;
}

function createBlurCircleMask(blur: number): string {
  const circleRadius = isHighResolution() ? 20 : 25;
  const blurFilter = `<filter id="blur"><feGaussianBlur stdDeviation="${blur}" /></filter>`;
  return `url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="-50 -50 100 100"><defs>${blurFilter}</defs><circle cx="0" cy="0" r="${circleRadius}" fill="white" filter="url(%23blur)"/></svg>')`;
}

function createPolygonGradientMask(): string {
  const gradient =
    '<linearGradient id="g" x1="0" y1="0" x2="20.5" y2="20.5" gradientUnits="userSpaceOnUse">' +
    '<stop stop-color="white"/>' +
    '<stop offset="0.84506" stop-color="white" stop-opacity="0.99"/>' +
    '<stop offset="0.9506" stop-color="white" stop-opacity="0"/>' +
    '<stop offset="1" stop-color="white" stop-opacity="0"/>' +
    '</linearGradient>';

  return `url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><defs>${gradient}</defs><path d="M0 0H40L0 40V0Z" fill="url(%23g)"/></svg>')`;
}

function resolveDuration(type: ThemeAnimationType, custom?: number): number {
  const base =
    custom ??
    (type === 'gif' ? 2000 : type === 'polygon-gradient' ? 1500 : defaults.duration);
  return isHighResolution() ? Math.max(base * 0.8, 500) : base;
}

function resolveEasing(type: ThemeAnimationType, custom?: string): string {
  if (custom) return custom;
  if (type === 'polygon' || type === 'polygon-gradient') return EXPO_OUT_EASING;
  if (type === 'gif') return EXPO_IN_EASING;
  return defaults.easing;
}

function removeStyle(styleId: string): void {
  document.getElementById(styleId)?.remove();
}

function injectStyle(styleId: string, css: string): void {
  removeStyle(styleId);
  const el = document.createElement('style');
  el.id = styleId;
  el.textContent = css;
  document.head.appendChild(el);
}

function scheduleStyleCleanup(styleId: string, duration: number): void {
  window.setTimeout(() => removeStyle(styleId), duration);
}

/** Merge kit-wide animation defaults (call from `$theme.configure`). */
export function configureThemeAnimation(options: ThemeAnimationConfig): void {
  if (options.duration !== undefined) defaults.duration = options.duration;
  if (options.easing !== undefined) defaults.easing = options.easing;
  if (options.pseudoElement !== undefined) defaults.pseudoElement = options.pseudoElement;
  if (options.globalClassName !== undefined) defaults.globalClassName = options.globalClassName;
  if (options.animationType !== undefined) defaults.animationType = options.animationType;
  if (options.blurAmount !== undefined) defaults.blurAmount = options.blurAmount;
  if (options.styleId !== undefined) defaults.styleId = options.styleId;
  if (options.gifUrl !== undefined) defaults.gifUrl = options.gifUrl;
}

export function getThemeAnimationConfig(): Readonly<typeof defaults> {
  return { ...defaults };
}

/**
 * Apply a theme DOM update inside a View Transition when supported.
 * Falls back to an immediate update when VT / motion is unavailable.
 */
export async function withThemeTransition(
  update: () => void,
  options: ThemeTransitionOptions = {},
): Promise<void> {
  if (typeof document === 'undefined') {
    update();
    return;
  }

  injectBaseStyles();

  const animationType = resolveAnimationType(options.origin, options.animationType);
  const transitionStarter = (
    document as Document & {
      startViewTransition?: (cb: () => void) => ViewTransitionLike;
    }
  ).startViewTransition;

  if (
    animationType === 'none' ||
    typeof transitionStarter !== 'function' ||
    prefersReducedMotion()
  ) {
    update();
    return;
  }

  const styleId = options.styleId ?? defaults.styleId;
  const globalClassName = options.globalClassName ?? defaults.globalClassName;
  const blurAmount = options.blurAmount ?? defaults.blurAmount;
  const gifUrl = options.gifUrl ?? defaults.gifUrl;
  const duration = resolveDuration(animationType, options.duration);
  const easing = resolveEasing(animationType, options.easing);
  const pseudoElement = options.pseudoElement ?? defaults.pseudoElement;
  const { x, y } = resolveOrigin(options.origin);

  const topLeft = Math.hypot(x, y);
  const topRight = Math.hypot(window.innerWidth - x, y);
  const bottomLeft = Math.hypot(x, window.innerHeight - y);
  const bottomRight = Math.hypot(window.innerWidth - x, window.innerHeight - y);
  const maxRadius = Math.max(topLeft, topRight, bottomLeft, bottomRight);

  const viewportSize = Math.max(window.innerWidth, window.innerHeight) + 200;
  const hi = isHighResolution();
  const scaleFactor = hi ? 2.5 : 4;
  const optimalMaskSize = hi
    ? Math.min(viewportSize * scaleFactor, 5000)
    : viewportSize * scaleFactor;

  let effectiveType = animationType;
  if (effectiveType === 'gif' && !gifUrl) {
    console.warn(
      '[neura-kit] theme animation `gif` requires `gifUrl`; falling back to `circle`.',
    );
    effectiveType = 'circle';
  }

  if (effectiveType === 'blur-circle') {
    const blurFactor = hi ? 1.5 : 1.2;
    const finalMaskSize = Math.max(optimalMaskSize, maxRadius * 2.5);
    const hiEasing =
      'cubic-bezier(0.2, 0, 0.2, 1)';
    const loEasing =
      'linear(0 0%, 0.2342 12.49%, 0.4374 24.99%, 0.6093 37.49%, 0.6835 43.74%, 0.7499 49.99%, 0.8086 56.25%, 0.8593 62.5%, 0.9023 68.75%, 0.9375 75%, 0.9648 81.25%, 0.9844 87.5%, 0.9961 93.75%, 1 100%)';

    injectStyle(
      styleId,
      `
      ::view-transition-group(root) {
        animation-duration: ${duration}ms;
        animation-timing-function: ${hi ? hiEasing : loEasing};
        will-change: transform;
      }
      ::view-transition-new(root) {
        mask: ${createBlurCircleMask(blurAmount * blurFactor)} 0 0 / 100% 100% no-repeat;
        mask-position: ${x}px ${y}px;
        animation: nk-theme-mask-scale ${duration}ms ${easing};
        transform-origin: ${x}px ${y}px;
        will-change: mask-size, mask-position;
      }
      ::view-transition-old(root),
      .${globalClassName}::view-transition-old(root) {
        animation: nk-theme-mask-scale ${duration}ms ${easing};
        transform-origin: ${x}px ${y}px;
        z-index: -1;
        will-change: mask-size, mask-position;
      }
      @keyframes nk-theme-mask-scale {
        0% { mask-size: 0px; mask-position: ${x}px ${y}px; }
        100% { mask-size: ${finalMaskSize}px; mask-position: ${x - finalMaskSize / 2}px ${y - finalMaskSize / 2}px; }
      }
    `,
    );
  }

  if (effectiveType === 'polygon-gradient') {
    injectStyle(
      styleId,
      `
      ::view-transition-group(root) {
        animation-duration: ${duration}ms;
        animation-timing-function: ease;
        animation-timing-function: ${easing};
      }
      ::view-transition-new(root) {
        mask: ${createPolygonGradientMask()} top left / 0 no-repeat;
        animation: nk-theme-polygon-gradient ${duration}ms ease;
        animation: nk-theme-polygon-gradient ${duration}ms ${easing};
        animation-fill-mode: both;
        will-change: mask-size;
      }
      ::view-transition-old(root),
      .${globalClassName}::view-transition-old(root) {
        animation: nk-theme-polygon-gradient ${duration}ms ease;
        animation: nk-theme-polygon-gradient ${duration}ms ${easing};
        animation-fill-mode: both;
        z-index: -1;
        transform-origin: top left;
      }
      @keyframes nk-theme-polygon-gradient {
        to { mask-size: 200vmax; }
      }
    `,
    );
  }

  if (effectiveType === 'gif' && gifUrl) {
    injectStyle(
      styleId,
      `
      ::view-transition-group(root) {
        animation-duration: ${duration}ms;
        animation-timing-function: ease;
        animation-timing-function: ${easing};
      }
      ::view-transition-new(root) {
        mask: url('${gifUrl}') center / 0 no-repeat;
        animation: nk-theme-gif-mask ${duration}ms ease;
        animation: nk-theme-gif-mask ${duration}ms ${easing};
        animation-fill-mode: both;
        will-change: mask-size;
      }
      ::view-transition-old(root),
      .${globalClassName}::view-transition-old(root) {
        animation: nk-theme-gif-mask ${duration}ms ease;
        animation: nk-theme-gif-mask ${duration}ms ${easing};
        animation-fill-mode: both;
        z-index: -1;
      }
      @keyframes nk-theme-gif-mask {
        0% { mask-size: 0; }
        10% { mask-size: 50vmax; }
        90% { mask-size: 50vmax; }
        100% { mask-size: 2000vmax; }
      }
    `,
    );
  }

  const transition = transitionStarter.call(document, () => {
    update();
  });

  try {
    await transition.ready;
  } catch {
    // Transition aborted (e.g. interrupted) — update already ran.
    removeStyle(styleId);
    return;
  }

  if (effectiveType === 'circle') {
    document.documentElement.animate(
      {
        clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${maxRadius}px at ${x}px ${y}px)`],
      },
      { duration, easing, pseudoElement },
    );
  }

  if (effectiveType === 'polygon') {
    const willBeDark = options.willBeDark ?? true;
    const clipPath = willBeDark
      ? ['polygon(50% -71%, -50% 71%, -50% 71%, 50% -71%)', 'polygon(50% -71%, -50% 71%, 50% 171%, 171% 50%)']
      : ['polygon(171% 50%, 50% 171%, 50% 171%, 171% 50%)', 'polygon(171% 50%, 50% 171%, -50% 71%, 50% -71%)'];

    try {
      document.documentElement.animate({ clipPath }, { duration, easing, pseudoElement });
    } catch {
      document.documentElement.animate(
        { clipPath },
        { duration, easing: 'ease-in-out', pseudoElement },
      );
    }
  }

  if (effectiveType === 'qr-scan') {
    const scanLineWidth = hi ? 8 : 4;
    document.documentElement.animate(
      {
        clipPath: [
          `polygon(0% 0%, ${scanLineWidth}px 0%, ${scanLineWidth}px 100%, 0% 100%)`,
          `polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)`,
        ],
      },
      { duration, easing, pseudoElement },
    );
  }

  if (
    effectiveType === 'blur-circle' ||
    effectiveType === 'polygon-gradient' ||
    (effectiveType === 'gif' && gifUrl)
  ) {
    scheduleStyleCleanup(styleId, duration);
  }
}

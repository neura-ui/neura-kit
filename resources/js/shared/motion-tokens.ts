/**
 * Shared motion scale.
 *
 * CSS transitions use the variables in resources/css/motion.css.
 * Springs are for scripted motion (View Transitions, direct manipulation).
 * When reduced motion is requested, keep the end state and drop travel.
 */

export const motionTokens = {
  spring: {
    /** Presses, toggles, thumbs, small indicators. */
    snappy: { visualDuration: 0.26, bounce: 0.12 },
    /** Panels, height changes, layout shifts. Never overshoots. */
    smooth: { visualDuration: 0.4, bounce: 0 },
    /** Shared highlights, shape changes, widths that follow content. */
    morph: { visualDuration: 0.42, bounce: 0.16 },
    /** Direct manipulation that must track the pointer. */
    responsive: { stiffness: 520, damping: 38 },
    /** Larger layout changes that should feel calm. */
    gentle: { stiffness: 340, damping: 34 },
  },
  ease: {
    standard: 'cubic-bezier(0.22, 1, 0.36, 1)',
    enter: 'cubic-bezier(0.16, 1, 0.3, 1)',
    spring: 'var(--ease-spring)',
  },
  /** Milliseconds. Mirrors the CSS duration tokens. */
  duration: {
    fast: 160,
    standard: 240,
    considered: 480,
    spring: 580,
  },
} as const

export type MotionSpring = keyof typeof motionTokens.spring
export type MotionDuration = keyof typeof motionTokens.duration
export type MotionEase = keyof typeof motionTokens.ease

export function prefersReducedMotion(): boolean {
  return (
    typeof window !== 'undefined' &&
    window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true
  )
}

/**
 * Duration in ms, or near-zero when the user asked for reduced motion.
 * The end state still applies; only the travel is removed.
 */
export function motionDuration(duration: number): number {
  return prefersReducedMotion() ? 0 : duration
}

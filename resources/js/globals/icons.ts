/**
 * Neura Icons — draw-on (once) helper
 *
 * Icons with data-animate="draw" stay hidden (stroke-dashoffset) until
 * data-play is set. For data-trigger="once", we wait until the icon enters
 * the viewport so the animation is visible (e.g. docs below the fold).
 */

export function playDrawIcon(el: Element): void {
  const strokes = Array.from(
    el.querySelectorAll<SVGElement>('[data-part="stroke"]'),
  )

  el.removeAttribute('data-play')

  strokes.forEach((stroke) => {
    stroke.style.animation = 'none'
    // Force the path back to the hidden start state (forwards fill leaves it drawn).
    stroke.style.strokeDashoffset = 'var(--nk-icon-len, 40)'
  })

  // SVG elements often report offsetWidth as 0 — use getBoundingClientRect for reflow.
  if (strokes[0]) {
    void strokes[0].getBoundingClientRect()
  } else {
    void (el as HTMLElement).offsetWidth
  }

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      strokes.forEach((stroke) => {
        stroke.style.animation = ''
        stroke.style.strokeDashoffset = ''
      })
      el.setAttribute('data-play', '')
    })
  })
}

function observeDrawOnce(el: HTMLElement): void {
  ;(el as HTMLElement & { _nkDrawPlay?: () => void })._nkDrawPlay = () => playDrawIcon(el)

  const trigger = el.getAttribute('data-trigger')
  if (trigger !== 'once') {
    playDrawIcon(el)
    return
  }

  if (!('IntersectionObserver' in window)) {
    playDrawIcon(el)
    return
  }

  const io = new IntersectionObserver(
    ([entry]) => {
      if (entry?.isIntersecting) {
        playDrawIcon(el)
        io.disconnect()
      }
    },
    { threshold: 0.25 },
  )
  io.observe(el)
}

function scan(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>(
    '[data-slot="icon"][data-animate="draw"]:not([data-draw-bound])',
  ).forEach((el) => {
    el.setAttribute('data-draw-bound', '')
    observeDrawOnce(el)
  })
}

export function initIconAnimations(): void {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return
  }

  const run = () => scan(document)

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true })
  } else {
    run()
  }

  document.addEventListener('livewire:navigated', run)
  document.addEventListener('livewire:init', () => {
    window.Livewire?.hook?.('morph.updated', ({ el }: { el: HTMLElement }) => {
      if (el?.querySelectorAll) {
        scan(el)
      }
    })
  })

  if ('MutationObserver' in window) {
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        m.addedNodes.forEach((node) => {
          if (node instanceof HTMLElement) {
            if (node.matches?.('[data-slot="icon"][data-animate="draw"]')) {
              scan(node.parentNode ?? document)
            } else {
              scan(node)
            }
          }
        })
      }
    })
    mo.observe(document.documentElement, { childList: true, subtree: true })
  }

  window.NeuraKitIcons = {
    play: playDrawIcon,
  }
}

initIconAnimations()

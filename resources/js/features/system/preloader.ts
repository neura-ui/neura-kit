type PreloaderOptions = {
  autoHide?: boolean;
  minDuration?: number;
  /**
   * Hard cap (ms) from Alpine init — hung fonts / 3rd-party CSS must not
   * leave the overlay up forever. Defaults to 2.5s.
   */
  maxWait?: number;
  /** Remove the node from the DOM after hide (fullscreen overlays only). */
  remove?: boolean;
};

type PreloaderAlpine = {
  visible: boolean;
  startedAt: number;
  _done: boolean;
  _hideTimer: number;
  $el: HTMLElement;
  init(): void;
  scheduleHide(): void;
  hide(): void;
  show(): void;
};

const defaults: Required<PreloaderOptions> = {
  autoHide: true,
  minDuration: 400,
  maxWait: 2500,
  remove: true,
};

/**
 * Full-page boot overlay. Place `<neura::preloader />` near the top of `<body>`.
 * Auto-hides after DOM is interactive (+ optional min duration), with a hard
 * maxWait so a hung `window` load (fonts, VPN, …) cannot trap it. Or call
 * `NeuraKitPreloader.hide()` / `.show()` manually.
 */
function neuraPreloader(options: PreloaderOptions = {}): PreloaderAlpine {
  const config = { ...defaults, ...options };

  return {
    visible: true,
    startedAt: Date.now(),
    _done: false,
    _hideTimer: 0,
    $el: undefined as unknown as HTMLElement,

    init() {
      window.NeuraKitPreloader = {
        hide: () => this.hide(),
        show: () => this.show(),
        el: this.$el,
      };

      if (! config.autoHide) {
        return;
      }

      const finish = () => this.scheduleHide();

      // Prefer DOMContentLoaded over window `load`: stylesheets from
      // fonts.googleapis.com (often stalled behind a VPN) would otherwise
      // keep the overlay up indefinitely.
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', finish, { once: true });
      } else {
        finish();
      }

      window.setTimeout(finish, config.maxWait);
    },

    scheduleHide() {
      if (this._done || this._hideTimer) {
        return;
      }

      const wait = Math.max(0, config.minDuration - (Date.now() - this.startedAt));
      this._hideTimer = window.setTimeout(() => this.hide(), wait);
    },

    hide() {
      if (! this.visible) {
        return;
      }

      this._done = true;
      this.visible = false;

      if (! config.remove) {
        return;
      }

      window.setTimeout(() => {
        this.$el?.remove();
      }, 350);
    },

    show() {
      if (this._hideTimer) {
        window.clearTimeout(this._hideTimer);
        this._hideTimer = 0;
      }
      this._done = false;
      this.visible = true;
      this.startedAt = Date.now();
    },
  };
}

declare global {
  interface Window {
    neuraPreloader: typeof neuraPreloader;
    NeuraKitPreloader?: {
      hide: () => void;
      show: () => void;
      el: HTMLElement;
    };
  }
}

if (typeof window !== 'undefined') {
  window.neuraPreloader = neuraPreloader;
}

export { neuraPreloader };
export type { PreloaderOptions };

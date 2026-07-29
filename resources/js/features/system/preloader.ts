type PreloaderOptions = {
  autoHide?: boolean;
  minDuration?: number;
  /** Remove the node from the DOM after hide (fullscreen overlays only). */
  remove?: boolean;
};

type PreloaderAlpine = {
  visible: boolean;
  startedAt: number;
  _done: boolean;
  $el: HTMLElement;
  init(): void;
  scheduleHide(): void;
  hide(): void;
  show(): void;
};

const defaults: Required<PreloaderOptions> = {
  autoHide: true,
  minDuration: 400,
  remove: true,
};

/**
 * Full-page boot overlay. Place `<neura::preloader />` near the top of `<body>`.
 * Auto-hides after `window` load (+ optional min duration), or call
 * `NeuraKitPreloader.hide()` / `.show()` manually.
 */
function neuraPreloader(options: PreloaderOptions = {}): PreloaderAlpine {
  const config = { ...defaults, ...options };

  return {
    visible: true,
    startedAt: Date.now(),
    _done: false,
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

      if (document.readyState === 'complete') {
        finish();
      } else {
        window.addEventListener('load', finish, { once: true });
      }
    },

    scheduleHide() {
      if (this._done) {
        return;
      }

      const wait = Math.max(0, config.minDuration - (Date.now() - this.startedAt));
      window.setTimeout(() => this.hide(), wait);
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

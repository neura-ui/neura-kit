interface LottieAnimation {
  setSpeed(speed: number): void;
  destroy(): void;
}

interface LottieLoader {
  loadAnimation(config: {
    container: HTMLElement;
    renderer: 'svg';
    loop: boolean;
    autoplay: boolean;
    path: string;
  }): LottieAnimation;
}

interface AlpineComponent {
  $el: HTMLElement;
  $nextTick: (callback: () => void) => void;
  instance: LottieAnimation | null;
}

let lottie: LottieLoader | null = null;

async function loadLottie(): Promise<LottieLoader> {
  if (lottie) return lottie;
  const module = await import('lottie-web/build/player/lottie_light');
  lottie = module.default as LottieLoader;
  return lottie;
}

if (typeof document !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const Alpine = (window as any).Alpine;

    Alpine.data('lottieAnimation', function(this: AlpineComponent) {
      return {
    instance: null as LottieAnimation | null,

        async init(this: AlpineComponent): Promise<void> {
          const el = this.$el;
          const path = el.dataset.lottieAnimation;
      if (!path) return;

          const delay = parseInt(el.dataset.lottieDelay || '0', 10);
          const speed = parseFloat(el.dataset.lottieSpeed || '1');

      const load = async (): Promise<void> => {
        const lib = await loadLottie();
        this.instance = lib.loadAnimation({
              container: el,
          renderer: 'svg',
          loop: true,
          autoplay: true,
          path,
        });
        if (speed !== 1) this.instance.setSpeed(speed);
      };

          delay > 0 ? setTimeout(load, delay) : this.$nextTick(load);
    },

        destroy(this: AlpineComponent): void {
      this.instance?.destroy();
      this.instance = null;
    },
      };
    });
  });
}


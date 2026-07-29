import { defineMagic } from '../../shared/utils';
import { onAlpineInit } from '../../runtime/alpine';
import {
  configureThemeAnimation,
  getThemeAnimationConfig,
  withThemeTransition,
  type ThemeAnimationConfig,
  type ThemeOrigin,
} from './theme-animation';

type Theme = 'light' | 'dark' | 'system';

const getSystem = (): 'dark' | 'light' =>
  matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

const apply = (t: 'dark' | 'light'): void => {
  document.documentElement.style.colorScheme = t;
  document.documentElement.classList.toggle('dark', t === 'dark');
  dispatchEvent(
    new CustomEvent<{ theme: 'dark' | 'light' }>('theme-changed', {
      detail: { theme: t },
    }),
  );
};

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
  (() => {
    const stored = (localStorage.getItem('theme') ?? 'light') as Theme;
    apply(stored === 'system' ? getSystem() : stored);
  })();

  document.addEventListener('livewire:navigated', () => {
    const stored = (localStorage.getItem('theme') ?? 'light') as Theme;
    apply(stored === 'system' ? getSystem() : stored);
  });
}

if (typeof document !== 'undefined') {
  onAlpineInit(() => {
    defineMagic('theme', {
      current: null as 'dark' | 'light' | null,
      stored: null as Theme | null,

      init(): void {
        this.stored = (localStorage.getItem('theme') ?? 'light') as Theme;
        this.current = this.stored === 'system' ? getSystem() : this.stored;
        apply(this.current);

        matchMedia('(prefers-color-scheme: dark)').addEventListener(
          'change',
          (e: MediaQueryListEvent) => {
            if (this.stored === 'system') {
              const next = e.matches ? 'dark' : 'light';
              if (next === this.current) return;
              void withThemeTransition(
                () => {
                  this.current = next;
                  apply(next);
                },
                { willBeDark: next === 'dark' },
              );
            }
          },
        );
      },

      /** Kit-wide View Transition defaults (animation type, duration, …). */
      configure(options: ThemeAnimationConfig): void {
        configureThemeAnimation(options);
      },

      animation(): Readonly<ReturnType<typeof getThemeAnimationConfig>> {
        return getThemeAnimationConfig();
      },

      set(theme: Theme, origin?: ThemeOrigin): void {
        this.stored = theme;
        localStorage.setItem('theme', theme);
        const next = theme === 'system' ? getSystem() : theme;

        if (next === this.current) return;

        void withThemeTransition(
          () => {
            this.current = next;
            apply(next);
          },
          { origin, willBeDark: next === 'dark' },
        );
      },

      light(origin?: ThemeOrigin): void {
        this.set('light', origin);
      },
      dark(origin?: ThemeOrigin): void {
        this.set('dark', origin);
      },
      system(origin?: ThemeOrigin): void {
        this.set('system', origin);
      },
      toggle(origin?: ThemeOrigin): void {
        this.set(this.current === 'dark' ? 'light' : 'dark', origin);
      },
      setLight(origin?: ThemeOrigin): void {
        this.set('light', origin);
      },
      setDark(origin?: ThemeOrigin): void {
        this.set('dark', origin);
      },
      setSystem(origin?: ThemeOrigin): void {
        this.set('system', origin);
      },

      get isLight(): boolean {
        return this.stored === 'light';
      },
      get isDark(): boolean {
        return this.stored === 'dark';
      },
      get isSystem(): boolean {
        return this.stored === 'system';
      },
      get resolvedLight(): boolean {
        return this.current === 'light';
      },
      get resolvedDark(): boolean {
        return this.current === 'dark';
      },
      get isResolvedToLight(): boolean {
        return this.current === 'light';
      },
      get isResolvedToDark(): boolean {
        return this.current === 'dark';
      },
    });
  });
}

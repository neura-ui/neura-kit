import { defineMagic } from '../utils';

type Theme = 'light' | 'dark' | 'system';

const getSystem = (): 'dark' | 'light' =>
  matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

const apply = (t: 'dark' | 'light'): void => {
  document.documentElement.classList.toggle('dark', t === 'dark');
  dispatchEvent(
    new CustomEvent<{ theme: 'dark' | 'light' }>('theme-changed', {
      detail: { theme: t },
    })
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
  document.addEventListener('alpine:init', () => {
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
              this.current = e.matches ? 'dark' : 'light';
              apply(this.current);
            }
          }
        );
      },

      set(theme: Theme): void {
        this.stored = theme;
        localStorage.setItem('theme', theme);
        this.current = theme === 'system' ? getSystem() : theme;
        apply(this.current);
      },

      light(): void {
        this.set('light');
      },
      dark(): void {
        this.set('dark');
      },
      system(): void {
        this.set('system');
      },
      toggle(): void {
        this.set(this.current === 'dark' ? 'light' : 'dark');
      },
      setLight(): void {
        this.set('light');
      },
      setDark(): void {
        this.set('dark');
      },
      setSystem(): void {
        this.set('system');
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


import './types';

interface Translations {
  [key: string]: string;
}

interface TranslationsAPILocal {
  translations: Translations;
  currentLocale: string;
  fallbackLocale: string;
  _loading: Promise<Translations> | false;
  _loaded: boolean;
  getLocale(): string;
  getFallbackLocale(): string;
  load(locale?: string | null): Promise<Translations>;
  _loadTranslations(locale: string): Promise<Translations>;
  init(translations?: Translations, locale?: string | null): void;
  t(key: string, params?: Record<string, string>): string;
  setLocale(locale: string): Promise<Translations>;
  setupGlobalFunction(): void;
}

(function () {
  'use strict';

  if (typeof document === 'undefined' || typeof window === 'undefined') {
    return;
  }

  function injectLocaleMetaTags(): void {
    if (!document.head) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectLocaleMetaTags);
      }
      return;
    }

    function ensureMetaTag(name: string, content: string): HTMLMetaElement {
      let meta = document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement;
      if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', name);
        document.head.appendChild(meta);
      }
      if (!meta.getAttribute('content')) {
        meta.setAttribute('content', content);
      }
      return meta;
    }

    const existingLocaleMeta = document.querySelector('meta[name="app-locale"]') as HTMLMetaElement;
    const htmlLang = document.documentElement.getAttribute('lang');
    const browserLang = navigator.language.split('-')[0];

    const currentLocale =
      existingLocaleMeta?.getAttribute('content') || htmlLang || browserLang || 'en';

    const existingFallbackMeta = document.querySelector(
      'meta[name="app-fallback-locale"]'
    ) as HTMLMetaElement;
    const fallbackLocale = existingFallbackMeta?.getAttribute('content') || 'en';

    ensureMetaTag('app-locale', currentLocale);
    ensureMetaTag('app-fallback-locale', fallbackLocale);
  }

  injectLocaleMetaTags();

  window.NeuraKitTranslations = {
    translations: {},
    currentLocale: 'en',
    fallbackLocale: 'en',
    _loading: false,
    _loaded: false,

    getLocale(): string {
      const meta = document.querySelector('meta[name="app-locale"]') as HTMLMetaElement;
      return meta ? meta.getAttribute('content') || 'en' : 'en';
    },

    getFallbackLocale(): string {
      const meta = document.querySelector('meta[name="app-fallback-locale"]') as HTMLMetaElement;
      return meta ? meta.getAttribute('content') || 'en' : 'en';
    },

    async load(locale: string | null = null): Promise<Translations> {
      if (this._loading) {
        return this._loading;
      }

      locale = locale || this.getLocale();
      this.fallbackLocale = this.getFallbackLocale();
      this._loading = this._loadTranslations(locale);

      try {
        await this._loading;
        this._loaded = true;
        this.setupGlobalFunction();
        window.dispatchEvent(
          new CustomEvent<{ locale: string }>('neurakit-translations-loaded', {
            detail: { locale: this.currentLocale },
          })
        );
      } catch (error) {
        console.warn('Failed to load translations:', error);
        this._loaded = true;
        this.setupGlobalFunction();
      }

      return this._loading;
    },

    async _loadTranslations(locale: string): Promise<Translations> {
      try {
        const response = await fetch(`/neura-kit/lang/${locale}.json`, {
          method: 'GET',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (response.ok) {
          const translations = (await response.json()) as Translations;
          this.translations = translations;
          this.currentLocale = locale;
          if (document.documentElement) {
            document.documentElement.setAttribute('lang', locale);
          }
          return translations;
        } else if (locale !== this.fallbackLocale) {
          return this._loadTranslations(this.fallbackLocale);
        } else {
          this.translations = {};
          this.currentLocale = locale;
          return {};
        }
      } catch (error) {
        if (locale !== this.fallbackLocale) {
          return this._loadTranslations(this.fallbackLocale);
        }
        throw error;
      }
    },

    init(translations: Translations = {}, locale: string | null = null): void {
      if (translations && Object.keys(translations).length > 0) {
        this.translations = translations;
      }
      this.currentLocale = locale || this.getLocale();
      if (document.documentElement) {
        document.documentElement.setAttribute('lang', this.currentLocale);
      }
      this.setupGlobalFunction();
      this._loaded = true;
    },

    t(key: string, params: Record<string, string> = {}): string {
      let translation = this.translations[key] || key;

      if (params && Object.keys(params).length > 0) {
        Object.keys(params).forEach((param) => {
          translation = translation.replace(new RegExp(`\\{${param}\\}`, 'g'), params[param]);
        });
      }

      return translation;
    },

    async setLocale(locale: string): Promise<Translations> {
      this._loading = false;
      this._loaded = false;
      return this.load(locale);
    },

    setupGlobalFunction(): void {
      window.t = (key: string, params: Record<string, string> = {}) => {
        return this.t(key, params);
      };
    },
  };

  window.t = function (key: string, params: Record<string, string> = {}): string {
    if (window.NeuraKitTranslations && window.NeuraKitTranslations.translations) {
      return window.NeuraKitTranslations.t(key, params);
    }
    return key;
  };

  function initializeTranslations(): void {
    if (window.NeuraKitTranslations && !window.NeuraKitTranslations._loaded && !window.NeuraKitTranslations._loading) {
      window.NeuraKitTranslations.load();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTranslations);
  } else {
    initializeTranslations();
  }

  document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
      window.Alpine.data('translations', () => ({
        t(key: string, params: Record<string, string> = {}): string {
          return window.t?.(key, params) ?? key;
        },
      }));
    }
  });
})();


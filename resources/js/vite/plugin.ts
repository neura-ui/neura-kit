import type { Plugin } from 'vite';
import { generateThemeCSS } from '../core/theme';
import { loadNeuraConfig } from '../core/config';
import { transformCss } from './css-transform';
import { transformJs } from './js-transform';
import { configureOptimizeDeps } from './optimize';
import type { NeuraKitUserConfig, TransformResult } from '../core/types';

export function neuraKitPlugin(
  userConfig: NeuraKitUserConfig = {}
): Plugin {
  let themeCSS = '';

  return {
    name: 'neura-kit',
    enforce: 'pre',

    async configResolved() {
      const fileConfig = await loadNeuraConfig();
      themeCSS = generateThemeCSS({ ...fileConfig, ...userConfig });
    },

    config(config) {
      configureOptimizeDeps(config);
      return config;
    },

    transform(code: string, id: string): TransformResult | null {
      if (id.endsWith('resources/css/app.css')) {
        return transformCss(code, themeCSS);
      }

      if (id.endsWith('resources/js/app.js')) {
        return transformJs(code);
      }

      return null;
    },
  };
}

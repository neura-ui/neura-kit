import type {Plugin} from 'vite';
import {generateThemeCSS} from '../core/theme.ts';
import {loadNeuraConfig} from '../core/config.ts';
import {transformCss} from './css-transform.ts';
import {transformJs} from './js-transform.ts';
import {configureOptimizeDeps} from './optimize.ts';
import type {NeuraKitUserConfig, TransformResult} from '../core/types.ts';

export function neuraKitPlugin(
    userConfig: NeuraKitUserConfig = {}
): Plugin {
    let themeCSS = '';

    return {
        name: 'neura-kit',
        enforce: 'pre',

        async configResolved() {
            const fileConfig = await loadNeuraConfig();
            themeCSS = generateThemeCSS({...fileConfig, ...userConfig});
        },

        config(config) {
            configureOptimizeDeps(config);
            return config;
        },

        transform(code: string, id: string): TransformResult | null {
            const [pathOnly] = id.split('?', 1);
            const normalized = pathOnly.replace(/\\/g, '/');

            if (normalized.endsWith('/resources/css/app.css')) {
                return transformCss(code, themeCSS);
            }

            if (normalized.endsWith('/resources/js/app.js')) {
                return transformJs(code);
            }

            return null;
        },
    };
}

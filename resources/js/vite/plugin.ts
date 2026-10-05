import type {Plugin} from 'vite';
import {generateThemeCSS} from '../core/theme.ts';
import {generateSurfaceCSS} from '../core/surfaces.ts';
import {loadNeuraConfig} from '../core/config.ts';
import {transformCss} from './css-transform.ts';
import {transformJs} from './js-transform.ts';
import {configureOptimizeDeps} from './optimize.ts';
import type {NeuraKitConfig, NeuraKitUserConfig, TransformResult} from '../core/types.ts';

function mergeConfig(fileConfig: NeuraKitConfig, userConfig: NeuraKitConfig): NeuraKitConfig {
    const hasAppearance = fileConfig.appearance !== undefined || userConfig.appearance !== undefined;

    return {
        ...fileConfig,
        ...userConfig,
        colors: {...fileConfig.colors, ...userConfig.colors},
        radius: {...fileConfig.radius, ...userConfig.radius},
        font: {...fileConfig.font, ...userConfig.font},
        ...(hasAppearance
            ? {appearance: {...fileConfig.appearance, ...userConfig.appearance}}
            : {}),
    };
}

export function neuraKitPlugin(
    userConfig: NeuraKitUserConfig = {}
): Plugin {
    let themeCSS = '';
    let surfaceCSS = '';

    return {
        name: 'neura-kit',
        enforce: 'pre',

        async configResolved() {
            const merged = mergeConfig(await loadNeuraConfig(), userConfig);
            themeCSS = generateThemeCSS(merged);
            surfaceCSS = generateSurfaceCSS(merged);
        },

        config(config) {
            configureOptimizeDeps(config);
            return config;
        },

        transform(code: string, id: string): TransformResult | null {
            const [pathOnly] = id.split('?', 1);
            const normalized = pathOnly.replace(/\\/g, '/');

            if (normalized.endsWith('/resources/css/app.css')) {
                return transformCss(code, themeCSS, surfaceCSS);
            }

            if (normalized.endsWith('/resources/js/app.js')) {
                return transformJs(code);
            }

            return null;
        },
    };
}

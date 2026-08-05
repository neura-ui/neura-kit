/**
 * Feature manifest.
 *
 * Each entry maps the marks a feature leaves in the markup to the module that
 * implements it, so a page only downloads the parts of the kit it actually
 * renders. Everything not listed here — theme, toast, translations, icons,
 * clipboard — is small and globally reachable from user code, so it stays in
 * the entry chunk.
 *
 * `tokens` are matched against the text of `x-data` attributes, which is how
 * Alpine-driven components announce themselves. `selector` covers the features
 * driven by markup alone.
 */

export interface Feature {
    /** Stable id, used for dedupe and error messages. */
    readonly name: string;
    /** Factory names as they appear inside `x-data="…"`. */
    readonly tokens?: readonly string[];
    /** Markup that requires the feature even without an Alpine factory. */
    readonly selector?: string;
    readonly load: () => Promise<unknown>;
}

export const FEATURES: readonly Feature[] = [
    {
        name: 'modal',
        tokens: ['modalManager'],
        load: () => Promise.resolve(), // eager via app.ts
    },
    {
        name: 'sideover',
        tokens: ['sideoverManager'],
        load: () => Promise.resolve(), // eager via app.ts
    },
    {
        name: 'spotlight',
        tokens: ['neuraSpotlight'],
        load: () => Promise.resolve(), // eager via app.ts
    },
    {
        name: 'command',
        tokens: ['CommandSpotlight'],
        load: () => import('../features/overlays/command'),
    },
    {
        name: 'context-menu',
        tokens: ['contextMenu'],
        load: () => import('../features/overlays/context-menu'),
    },
    {
        name: 'color-picker',
        tokens: ['neuraColorPicker'],
        load: () => import('../features/forms/color-picker'),
    },
    {
        name: 'emoji-picker',
        tokens: ['neuraEmojiPicker'],
        load: () => import('../features/forms/emoji-picker'),
    },
    {
        name: 'phone-input',
        tokens: ['neuraPhoneInput'],
        load: () => import('../features/forms/phone-input'),
    },
    {
        name: 'dropzone',
        tokens: ['neuraDropzone'],
        load: () => import('../features/forms/dropzone'),
    },
    {
        name: 'file-manager',
        tokens: ['neuraFileManager'],
        load: () => import('../features/forms/file-manager'),
    },
    {
        name: 'tree',
        tokens: ['neuraTree'],
        load: () => import('../features/data/tree'),
    },
    {
        name: 'rule-builder',
        tokens: ['neuraRuleBuilder'],
        load: () => Promise.resolve(), // eager via app.ts
    },
    {
        name: 'chart',
        tokens: ['chartComponent'],
        load: () => import('../features/chart'),
    },
    {
        name: 'editor',
        tokens: ['nativeEditor'],
        load: () => import('../features/editor'),
    },
    {
        name: 'flow',
        tokens: ['neuraFlow', 'flowEditor'],
        load: () => import('../features/flow'),
    },
    {
        name: 'orb',
        tokens: ['neuraOrb'],
        load: () => Promise.resolve(), // eager via app.ts (preloader)
    },
    {
        name: 'layout',
        selector: '[data-slot="col"],[data-slot="grid"],[data-slot="stack"],[data-slot="box"]',
        load: () => import('../components'),
    },
];

/**
 * Collect every `x-data` expression under `root`, including the ones parked
 * inside <template> elements that Alpine will stamp out later — querySelectorAll
 * does not descend into template content, so those need walking by hand.
 */
function readXData(root: ParentNode): string {
    const expressions: string[] = [];

    const collect = (scope: ParentNode): void => {
        for (const el of scope.querySelectorAll('[x-data]')) {
            expressions.push(el.getAttribute('x-data') ?? '');
        }
        for (const template of scope.querySelectorAll('template')) {
            collect(template.content);
        }
    };

    collect(root);

    return expressions.join('\n');
}

export function matches(feature: Feature, root: ParentNode, xData: string): boolean {
    if (feature.tokens?.some((token) => xData.includes(token))) return true;

    return Boolean(feature.selector && root.querySelector(feature.selector));
}

export function detect(root: ParentNode): Feature[] {
    const xData = readXData(root);

    return FEATURES.filter((feature) => matches(feature, root, xData));
}

/** The elements a feature is responsible for, used to recover from a late load. */
export function elementsFor(feature: Feature): HTMLElement[] {
    const elements = new Set<HTMLElement>();

    if (feature.tokens) {
        for (const el of document.querySelectorAll<HTMLElement>('[x-data]')) {
            const expression = el.getAttribute('x-data') ?? '';
            if (feature.tokens.some((token) => expression.includes(token))) {
                elements.add(el);
            }
        }
    }

    if (feature.selector) {
        for (const el of document.querySelectorAll<HTMLElement>(feature.selector)) {
            elements.add(el);
        }
    }

    return [...elements];
}

/**
 * Feature manifest.
 *
 * Each entry maps the marks a feature leaves in the markup to the module that
 * implements it, so a page only downloads the parts of the kit it actually
 * renders. Everything not listed here — theme, toast, translations, icons,
 * clipboard — is small and globally reachable from user code, so it stays in
 * the entry chunk.
 *
 * The overlay managers, the orb and the rule builder are not listed either:
 * app.ts imports them eagerly, so their factories exist before Alpine starts.
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
    /**
     * Substrings the raw HTML must contain for the feature to be present, so a
     * Livewire response can be ruled out without parsing it. Defaults to `tokens`.
     */
    readonly hints?: readonly string[];
    readonly load: () => Promise<unknown>;
}

export const FEATURES: readonly Feature[] = [
    {
        name: 'action-button',
        tokens: ['neuraActionButton'],
        load: () => import('../features/actions/action-button'),
    },
    {
        name: 'split-button',
        tokens: ['neuraSplitButton'],
        load: () => import('../features/actions/split-button'),
    },
    {
        name: 'button-group',
        tokens: ['neuraButtonGroup'],
        load: () => import('../features/actions/button-group'),
    },
    {
        name: 'expanding-button-group',
        tokens: ['neuraExpandingGroup'],
        load: () => import('../features/actions/expanding-button-group'),
    },
    {
        name: 'confirm-morph',
        tokens: ['neuraConfirmMorph'],
        load: () => import('../features/actions/confirm-morph'),
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
        name: 'number-field',
        tokens: ['neuraNumberField'],
        load: () => import('../features/forms/number-field'),
    },
    {
        name: 'mention-input',
        tokens: ['neuraMentionInput'],
        load: () => import('../features/forms/mention-input'),
    },
    {
        name: 'signature-pad',
        tokens: ['neuraSignaturePad'],
        load: () => import('../features/forms/signature-pad'),
    },
    {
        name: 'time-dial',
        tokens: ['neuraTimeDial'],
        load: () => import('../features/forms/time-dial'),
    },
    {
        name: 'date-reel',
        tokens: ['neuraDateReel'],
        load: () => import('../features/forms/date-reel'),
    },
    {
        name: 'activity-rings',
        tokens: ['neuraActivityRings'],
        load: () => import('../features/data/activity-rings'),
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
        name: 'json-viewer',
        tokens: ['neuraJsonViewer'],
        load: () => import('../features/data/json-viewer'),
    },
    {
        name: 'thinking-state',
        tokens: ['neuraThinkingState'],
        load: () => import('../features/ai/thinking-state'),
    },
    {
        name: 'web-search',
        tokens: ['neuraWebSearch'],
        load: () => import('../features/ai/web-search'),
    },
    {
        name: 'todo-list',
        tokens: ['neuraTodoList'],
        load: () => import('../features/ai/todo-list'),
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
        name: 'layout',
        selector: '[data-slot="col"],[data-slot="grid"],[data-slot="stack"],[data-slot="box"]',
        hints: ['data-slot="col"', 'data-slot="grid"', 'data-slot="stack"', 'data-slot="box"'],
        load: () => import('../components'),
    },
];

/**
 * `root` plus the content of every <template> beneath it, at any depth.
 * querySelectorAll does not descend into template content, and Alpine stamps
 * those (x-if, x-for, x-teleport) later, so they are walked by hand.
 */
function scopes(root: ParentNode): ParentNode[] {
    const found: ParentNode[] = [root];

    for (let i = 0; i < found.length; i++) {
        for (const template of found[i].querySelectorAll('template')) {
            found.push(template.content);
        }
    }

    return found;
}

/** Matches a factory name as a whole identifier, so `contextMenu` skips `contextMenuOpen`. */
const factoryPatterns = new Map<string, RegExp>();

function factoryPattern(token: string): RegExp {
    let pattern = factoryPatterns.get(token);

    if (!pattern) {
        pattern = new RegExp(`(?:^|[^\\w$])${token}(?![\\w$])`);
        factoryPatterns.set(token, pattern);
    }

    return pattern;
}

/** Whether an `x-data` expression calls one of the feature's factories. */
export function usesFactory(feature: Feature, expression: string): boolean {
    return Boolean(feature.tokens?.some((token) => factoryPattern(token).test(expression)));
}

function present(feature: Feature, roots: readonly ParentNode[]): boolean {
    return roots.some((scope) => {
        if (feature.selector && scope.querySelector(feature.selector)) return true;
        if (!feature.tokens) return false;

        for (const el of scope.querySelectorAll('[x-data]')) {
            if (usesFactory(feature, el.getAttribute('x-data') ?? '')) return true;
        }

        return false;
    });
}

/** The features, among `candidates`, that the markup under `root` needs. */
export function detect(root: ParentNode, candidates: readonly Feature[] = FEATURES): Feature[] {
    const roots = scopes(root);

    return candidates.filter((feature) => present(feature, roots));
}

/**
 * The features, among `candidates`, that an HTML string needs.
 *
 * Most Livewire responses mention none of the features that are still
 * missing, so a substring pass rules them out before anything is parsed.
 * The rest is parsed into an inert <template>: scripts do not run and images
 * do not load, and only real `x-data` attributes count, not code samples.
 */
export function detectHtml(html: string, candidates: readonly Feature[] = FEATURES): Feature[] {
    const hinted = candidates.filter((feature) =>
        (feature.hints ?? feature.tokens ?? []).some((hint) => html.includes(hint)),
    );

    if (hinted.length === 0) return [];

    const template = document.createElement('template');
    template.innerHTML = html;

    return detect(template.content, hinted);
}

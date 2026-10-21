import { FEATURES, detect, detectHtml, usesFactory, type Feature } from './features';
import { withAlpine } from './alpine';

/** `_x_ignore` is the flag `x-ignore` sets: Alpine skips the element and its subtree. */
type AlpineElement = HTMLElement & { _x_ignore?: boolean; _x_dataStack?: unknown };

type LivewireGlobal = NonNullable<Window['Livewire']>;

/** The part of a Livewire response the loader reads: each component's new markup. */
interface LivewirePayload {
    components?: Array<{ effects?: { html?: unknown; islandFragments?: unknown } } | null>;
}

const loaded = new Set<string>();
const failed = new Set<string>();
const inFlight = new Map<string, Promise<void>>();

/**
 * Import a feature once. Failures are logged rather than rethrown: the boot
 * path and every Livewire response await these, and one missing chunk must not
 * be able to hold the page hostage. A failed feature is not retried, so the
 * guard below never waits on it twice.
 */
export function load(feature: Feature): Promise<void> {
    if (loaded.has(feature.name) || failed.has(feature.name)) return Promise.resolve();

    const pending = inFlight.get(feature.name);
    if (pending) return pending;

    const promise = feature
        .load()
        .then(() => {
            loaded.add(feature.name);
        })
        .catch((error: unknown) => {
            failed.add(feature.name);
            console.error(`[neura-kit] could not load feature "${feature.name}"`, error);
        })
        .finally(() => {
            inFlight.delete(feature.name);
            rebuild(feature);
        });

    inFlight.set(feature.name, promise);

    return promise;
}

export function loadAll(features: readonly Feature[]): Promise<void[]> {
    return Promise.all(features.map(load));
}

/** Features that have not arrived yet and are still worth waiting for. */
function missing(): Feature[] {
    return FEATURES.filter((feature) => !loaded.has(feature.name) && !failed.has(feature.name));
}

/** Factories an element's `x-data` calls that are not registered yet. */
function missingFor(el: Element): Feature[] {
    const expression = el.getAttribute('x-data');
    if (!expression) return [];

    return missing().filter((feature) => usesFactory(feature, expression));
}

/** Settle when `promise` does or after `ms`, whichever comes first. */
function within(promise: Promise<unknown>, ms: number): Promise<void> {
    let timer: number | undefined;

    return Promise.race([
        promise.then(() => undefined),
        new Promise<void>((resolve) => {
            timer = window.setTimeout(resolve, ms);
        }),
    ]).finally(() => window.clearTimeout(timer));
}

/* -------------------------------------------------------------------------- */
/*  Guard: no component starts without its factory                             */
/* -------------------------------------------------------------------------- */

const held = new WeakSet<HTMLElement>();
const stale = new Map<string, Set<AlpineElement>>();

function staleFor(feature: Feature): Set<AlpineElement> {
    let elements = stale.get(feature.name);

    if (!elements) {
        elements = new Set();
        stale.set(feature.name, elements);
    }

    return elements;
}

/** Whether Livewire acts on the element itself during Alpine's walk. */
function claimedByLivewire(el: Element): boolean {
    return el.getAttributeNames().some((name) => name.startsWith('wire:'));
}

/**
 * Alpine passes every element through its init interceptors before it runs a
 * single directive. When an `x-data` calls a factory whose chunk is still on
 * the wire, the element is held the way `x-ignore` holds it: no directive
 * runs, the subtree is skipped, and Alpine walks it once the factory exists.
 * Nothing is evaluated against a missing factory, so nothing throws.
 *
 * An element carrying `wire:` attributes cannot be held. Livewire's own
 * interceptor initialises those directives during the same walk, and some of
 * them (`wire:poll`, `wire:init`) register no cleanup, so walking the element
 * a second time would run them twice. Those start as before and are rebuilt
 * once their feature lands. Preloading (boot, Livewire responses, navigation)
 * exists so that this path stays rare.
 */
function intercept(el: Element): void {
    if (!(el instanceof HTMLElement) || held.has(el)) return;

    const features = missingFor(el);
    if (features.length === 0) return;

    if (claimedByLivewire(el)) {
        for (const feature of features) staleFor(feature).add(el);
        void loadAll(features);
        return;
    }

    const target = el as AlpineElement;
    held.add(target);
    target._x_ignore = true;

    void loadAll(features).then(() => {
        held.delete(target);
        delete target._x_ignore;

        if (target.isConnected) window.Alpine.initTree?.(target);
    });
}

/**
 * Rebuild the components Alpine started without this feature's factory.
 *
 * Tearing down a Livewire component root would destroy the component for
 * good, because Livewire does not register an element it has already seen.
 * Such a subtree is left alone and reported instead.
 */
function rebuild(feature: Feature): void {
    const elements = stale.get(feature.name);
    if (!elements) return;

    stale.delete(feature.name);

    if (!loaded.has(feature.name)) return;

    const alpine = window.Alpine;

    for (const el of elements) {
        // Still waiting on another feature: that one's arrival rebuilds it.
        if (!el.isConnected || missingFor(el).length > 0) continue;

        if (el.matches('[wire\\:id]') || el.querySelector('[wire\\:id]')) {
            console.warn(
                `[neura-kit] "${feature.name}" arrived after Alpine started a Livewire component that uses it.`,
                el,
            );
            continue;
        }

        alpine.destroyTree?.(el);
        alpine.initTree?.(el);
    }
}

/** Install the guard. Must run before Alpine's first walk to cover the page. */
export function guard(): void {
    withAlpine((alpine) => {
        const flagged = alpine as Window['Alpine'] & { __neuraGuard?: boolean };

        if (!alpine?.interceptInit || flagged.__neuraGuard) return;

        flagged.__neuraGuard = true;
        alpine.interceptInit(intercept);
    });
}

/* -------------------------------------------------------------------------- */
/*  Preloading                                                                 */
/* -------------------------------------------------------------------------- */

/**
 * How long boot may hold Livewire/Alpine for lazy chunks.
 *
 * Eager factories (orb, overlays, preloader) are already registered; a slow
 * or hung widgets chunk must not leave the preloader's canvas blank. Anything
 * that misses the budget is held by the guard until it lands.
 */
const BOOT_BUDGET_MS = 800;

/**
 * How long a Livewire response may wait for its chunks before it is applied.
 * The user is waiting on that response, so a hung chunk gives way to the guard.
 */
const RESPONSE_BUDGET_MS = 2000;

/**
 * Alpine normally starts after boot. If it was already running (HMR, a custom
 * start), the page's components started without their factories: queue them
 * for a rebuild like any other late arrival.
 */
function adoptStarted(features: readonly Feature[]): void {
    for (const el of document.querySelectorAll<AlpineElement>('[x-data]')) {
        if (!el._x_dataStack) continue;

        const expression = el.getAttribute('x-data') ?? '';

        for (const feature of features) {
            if (usesFactory(feature, expression)) staleFor(feature).add(el);
        }
    }
}

/** Load every feature the current document needs. */
export async function boot(): Promise<void> {
    if (typeof document === 'undefined') return;

    const features = detect(document, missing());
    if (features.length === 0) return;

    adoptStarted(features);

    // Late chunks are not awaited past the budget: the guard holds their
    // components, and load() rebuilds whatever it could not hold.
    await within(loadAll(features), BOOT_BUDGET_MS);
}

/** The markup a Livewire response is about to morph into the page. */
function htmlIn(payload: LivewirePayload | undefined): string {
    const parts: string[] = [];

    for (const component of payload?.components ?? []) {
        const { html, islandFragments } = component?.effects ?? {};

        if (typeof html === 'string') parts.push(html);

        // Livewire 4 islands re-render on their own, outside `html`.
        if (Array.isArray(islandFragments)) {
            for (const fragment of islandFragments) {
                if (typeof fragment === 'string') parts.push(fragment);
            }
        }
    }

    return parts.join('\n');
}

/**
 * Load the features a Livewire response brings before it reaches the page.
 *
 * Livewire awaits `payload.intercept` before it morphs a response (it loads
 * `@assets` there). A component that shows up for the first time in a
 * response, such as a lazy component, an `@if` branch or a card opened in
 * place, gets its factory registered before Alpine sees it.
 */
export function watchLivewire(livewire: LivewireGlobal): void {
    const flagged = livewire as LivewireGlobal & { __neuraPayload?: boolean };

    if (!livewire.hook || flagged.__neuraPayload) return;

    flagged.__neuraPayload = true;

    livewire.hook('payload.intercept', async (payload?: LivewirePayload) => {
        try {
            const candidates = missing();
            if (candidates.length === 0) return;

            const html = htmlIn(payload);
            if (!html) return;

            const features = detectHtml(html, candidates);
            if (features.length > 0) await within(loadAll(features), RESPONSE_BUDGET_MS);
        } catch (error) {
            // Livewire awaits this hook: a throw here would fail the request.
            console.error('[neura-kit] could not scan a Livewire response', error);
        }
    });
}

/**
 * Keep SPA navigations supplied with features.
 *
 * `livewire:navigate` fires as the request leaves, which buys the round-trip
 * to warm up whatever is still missing. Alpine walks the new page without
 * waiting for anything, so this is what keeps components carrying `wire:`
 * attributes, which the guard cannot hold, from starting without their
 * factory. `livewire:navigated` picks up the markup-only features the guard
 * never sees.
 */
export function watchNavigation(): void {
    if (typeof document === 'undefined') return;

    document.addEventListener('livewire:navigate', () => {
        void loadAll(missing());
    });

    document.addEventListener('livewire:navigated', () => {
        void loadAll(detect(document, missing()));
    });
}

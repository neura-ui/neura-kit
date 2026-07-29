import { FEATURES, detect, elementsFor, type Feature } from './features';
import { hasAlpineStarted } from './alpine';

const loaded = new Set<string>();
const inFlight = new Map<string, Promise<void>>();

/**
 * Import a feature once. Failures are logged rather than rethrown: the boot
 * path awaits these before Livewire.start(), and one missing chunk must not be
 * able to hold the whole page hostage.
 */
export function load(feature: Feature): Promise<void> {
    if (loaded.has(feature.name)) return Promise.resolve();

    const pending = inFlight.get(feature.name);
    if (pending) return pending;

    const promise = feature
        .load()
        .then(() => {
            loaded.add(feature.name);
        })
        .catch((error: unknown) => {
            console.error(`[neura-kit] could not load feature "${feature.name}"`, error);
        })
        .finally(() => {
            inFlight.delete(feature.name);
        });

    inFlight.set(feature.name, promise);

    return promise;
}

export function loadAll(features: readonly Feature[]): Promise<void[]> {
    return Promise.all(features.map(load));
}

function isLoaded(feature: Feature): boolean {
    return loaded.has(feature.name);
}

/**
 * Rebuild the Alpine components a feature owns.
 *
 * Only reached when a feature turns up after Alpine has already walked the
 * markup — an SPA navigation landing on a component the first page never used.
 * Those elements initialised against a factory that did not exist yet, so the
 * subtree is torn down and re-initialised now that it does.
 */
function reinitialise(feature: Feature): void {
    const alpine = window.Alpine;
    if (!alpine?.destroyTree || !alpine.initTree) return;

    for (const el of elementsFor(feature)) {
        alpine.destroyTree(el);
        alpine.initTree(el);
    }
}

/**
 * Keep SPA navigations supplied with features.
 *
 * `livewire:navigate` fires when a navigation starts, which buys us the
 * round-trip to warm up whatever is still missing. `livewire:navigated` fires
 * after the swap and settles the rest, repairing any component that beat its
 * own module onto the page.
 */
export function watchNavigation(): void {
    if (typeof document === 'undefined') return;

    document.addEventListener('livewire:navigate', () => {
        for (const feature of FEATURES) {
            if (!isLoaded(feature)) void load(feature);
        }
    });

    document.addEventListener('livewire:navigated', () => {
        for (const feature of detect(document)) {
            if (isLoaded(feature)) continue;

            void load(feature).then(() => reinitialise(feature));
        }
    });
}

/** Load every feature the current document needs. */
export async function boot(): Promise<void[]> {
    if (typeof document === 'undefined') return [];

    const features = detect(document);
    const results = await loadAll(features);

    // Defensive: if Alpine somehow started before we finished (HMR, custom
    // Livewire boot), rebuild the subtrees that just got their factories.
    if (hasAlpineStarted()) {
        for (const feature of features) {
            reinitialise(feature);
        }
    }

    return results;
}

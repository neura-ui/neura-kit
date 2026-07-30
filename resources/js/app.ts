/**
 * Runtime entry.
 *
 * The imports below are the parts of the kit any page can reach without
 * rendering a component — `window.NeuraKit.toast()`, `$clipboard`, `$theme`,
 * `t()` — plus the icon and preloader scanners. They are small, and they stay
 * eager.
 *
 * Overlay managers ship on every page via `@neuraKit`, so they stay eager too.
 * The orb is eager as well: the global `<neura::preloader />` renders it on
 * first paint, so a lazy chunk would leave a blank canvas until it arrived.
 *
 * Everything else is a component: it leaves a mark in the markup, so the
 * loader can find it and fetch only what the page renders.
 *
 * Timing note: Livewire's classic `<script>` runs during HTML parse (end of
 * `<body>`), before this deferred module. Setting `livewireScriptConfig` here
 * is therefore too late to disable auto-start. Instead we wrap `Livewire.start`
 * so Alpine only walks the DOM after feature factories are registered.
 */

import './features/system/translations';
import './features/system/theme';
import './features/system/pullcord';
import './features/system/toast';
import './features/system/neura-kit';
import './features/system/clipboard';
import './features/system/icons';
import './features/system/preloader';
import './features/media/orb';

import './features/overlays/modal';
import './features/overlays/sideover';
import './features/overlays/spotlight';

import { boot, watchNavigation } from './runtime/loader';

watchNavigation();

const featuresReady = boot();

wrapLivewireStart(featuresReady);

await featuresReady;

// Ensure start runs even if DOMContentLoaded already fired before we wrapped,
// or if something else disabled Livewire's auto-start.
window.Livewire?.start();

/**
 * Delay Alpine boot until feature chunks have registered their factories.
 *
 * Livewire is usually already on `window` when this module evaluates (its
 * blocking script sat further down the document). We still poll briefly for
 * the Vite / ESM setups that load it later.
 */
function wrapLivewireStart(ready: Promise<unknown>): void {
  const arm = (): boolean => {
    const lw = window.Livewire as
      | (NonNullable<Window['Livewire']> & {
          __neuraWrapped?: boolean;
          __neuraStarted?: boolean;
        })
      | undefined;

    if (!lw?.start || lw.__neuraWrapped) return Boolean(lw?.start);

    lw.__neuraWrapped = true;
    const originalStart = lw.start.bind(lw);

    lw.start = () => {
      void ready.then(() => {
        if (lw.__neuraStarted) return;
        lw.__neuraStarted = true;

        if (window.Alpine) {
          (window.Alpine as { __fromLivewire?: boolean }).__fromLivewire = true;
        }

        originalStart();
      });
    };

    return true;
  };

  if (arm()) return;

  const started = Date.now();
  const poll = (): void => {
    if (arm()) return;
    if (Date.now() - started > 15_000) {
      console.error('[neura-kit] Livewire failed to load before start');
      return;
    }
    requestAnimationFrame(poll);
  };

  poll();
}

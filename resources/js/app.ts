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
 * loader can find it and fetch only what the page renders. The same scan runs
 * on every Livewire response before it is morphed in, and a guard holds any
 * component Alpine reaches before its chunk has landed (see runtime/loader).
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
import './features/system/tooltip';
import './features/system/neura-kit';
import './features/system/clipboard';
import './features/system/icons';
import './features/system/preloader';
import './features/media/orb';

import './features/overlays/menu-morph';
import './features/overlays/modal';
import './features/overlays/sideover';
import './features/overlays/spotlight';

// Rule builder is used on docs / app pages that Alpine walks on first paint.
// Register eagerly so `x-data="neuraRuleBuilder(…)"` never races the lazy chunk.
import './features/data/rule-builder';

import { boot, guard, watchLivewire, watchNavigation } from './runtime/loader';

guard();
watchNavigation();

const featuresReady = boot();

whenLivewire((livewire) => {
  watchLivewire(livewire);
  deferStart(livewire, featuresReady);
});

await featuresReady;

// Ensure start runs even if DOMContentLoaded already fired before we wrapped,
// or if something else disabled Livewire's auto-start.
window.Livewire?.start();

type LivewireGlobal = NonNullable<Window['Livewire']>;

/**
 * Run `ready` once Livewire is on `window`.
 *
 * Livewire is usually already there when this module evaluates (its blocking
 * script sat further down the document). We still poll briefly for the Vite /
 * ESM setups that load it later.
 */
function whenLivewire(ready: (livewire: LivewireGlobal) => void): void {
  const started = Date.now();

  const poll = (): void => {
    const livewire = window.Livewire;

    if (livewire?.start) {
      ready(livewire);
      return;
    }

    if (Date.now() - started > 15_000) {
      console.error('[neura-kit] Livewire failed to load before start');
      return;
    }

    requestAnimationFrame(poll);
  };

  poll();
}

/** Delay Alpine boot until feature chunks have registered their factories. */
function deferStart(livewire: LivewireGlobal, ready: Promise<unknown>): void {
  const lw = livewire as LivewireGlobal & {
    __neuraWrapped?: boolean;
    __neuraStarted?: boolean;
  };

  if (lw.__neuraWrapped) return;

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
}

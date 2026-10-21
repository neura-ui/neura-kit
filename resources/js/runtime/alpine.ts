/**
 * Alpine registration timing.
 *
 * Features normally land before Livewire boots Alpine — the Vite entry is a
 * deferred module in <head>, Livewire calls `Livewire.start()` from a
 * DOMContentLoaded listener, and deferred modules finish evaluating before
 * that event fires. So the usual path is "queue on alpine:init".
 *
 * A feature pulled in after an SPA navigation arrives with Alpine already
 * running; those register straight away instead of waiting for an event that
 * has been and gone.
 */

let alpineStarted = false;

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        alpineStarted = true;
    });
}

export function onAlpineInit(register: () => void): void {
    if (typeof document === 'undefined') return;

    if (alpineStarted) {
        register();
        return;
    }

    document.addEventListener('alpine:init', register, { once: true });
}

export function hasAlpineStarted(): boolean {
    return alpineStarted;
}

/**
 * Hand Alpine over as soon as it exists, and before it walks the page.
 *
 * Livewire's script defines `window.Alpine` before this deferred module runs,
 * so the usual path is immediate. Setups that load Livewire later still get
 * the call on `alpine:init`, which Alpine fires ahead of its first walk.
 */
export function withAlpine(register: (alpine: Window['Alpine']) => void): void {
    if (typeof window === 'undefined') return;

    if (window.Alpine) {
        register(window.Alpine);
        return;
    }

    document.addEventListener('alpine:init', () => register(window.Alpine), { once: true });
}

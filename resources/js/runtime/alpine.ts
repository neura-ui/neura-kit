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

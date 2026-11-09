import { onAlpineInit } from '../../../runtime/alpine';
import { hiddenIn, modes, settle } from './model';
import type { Mode } from './model';

/**
 * Breadcrumbs.
 *
 * The server folds the middle past `max-items` and draws "…", its menu and the back link.
 * With `responsive`, this measures the trail whenever its room changes and keeps the widest
 * layout that fits: the full trail, the folded one, then the back link alone. Layouts are
 * switched and measured in one go, before the browser paints.
 */

interface BreadcrumbsConfig {
    responsive?: boolean;
    before?: number;
    after?: number;
}

onAlpineInit(() => {
    window.Alpine.data('neuraBreadcrumbs', (config: BreadcrumbsConfig = {}) => {
        const before = Math.max(0, Number(config.before ?? 1));
        const after = Math.max(1, Number(config.after ?? 2));
        let observer: ResizeObserver | null = null;
        let frame = 0;
        let unhook: (() => void) | null = null;

        return {
            hidden: [] as number[],
            more: false,
            mode: 'full' as Mode,

            init(this: any) {
                const root = this.$el as HTMLElement;

                const parts = () => {
                    const list = root.querySelector<HTMLElement>('[data-bc-list]');
                    const items = list ? Array.from(list.querySelectorAll<HTMLElement>(':scope > [data-bc-item]')) : [];
                    return { list, items, more: list?.querySelector<HTMLElement>(':scope > [data-bc-more]') ?? null };
                };

                const apply = (mode: Mode): void => {
                    const { items, more } = parts();
                    const base = items.flatMap((item, i) => (item.hasAttribute('data-bc-folded') ? [i] : []));
                    const hidden = hiddenIn(mode, items.length, before, after, base);
                    items.forEach((item, i) => {
                        item.hidden = hidden.includes(i);
                    });
                    if (more) more.hidden = hidden.length === 0;
                    root.dataset.mode = mode;
                    this.hidden = hidden;
                    this.mode = mode;
                };

                const layout = (): void => {
                    frame = 0;
                    const { list, items } = parts();
                    if (!list) return;
                    if (!config.responsive) {
                        apply('full');
                        return;
                    }
                    const base = items.flatMap((item, i) => (item.hasAttribute('data-bc-folded') ? [i] : []));
                    const candidates = modes(items.length, before, after, base, !!root.querySelector('[data-bc-back]'));
                    const chosen = settle(candidates, (mode) => {
                        apply(mode);
                        // The back link always fits; the trail fits when nothing spills past its box.
                        return mode === 'back' || list.scrollWidth <= list.clientWidth + 1;
                    });
                    if (root.dataset.mode !== chosen) apply(chosen);
                    if (chosen !== 'folded' && !this.hidden.length) this.more = false;
                };

                const schedule = (): void => {
                    if (!frame) frame = requestAnimationFrame(layout);
                };

                layout();

                if (config.responsive && typeof ResizeObserver !== 'undefined') {
                    observer = new ResizeObserver(schedule);
                    observer.observe(root);
                }

                // A Livewire render can bring other items; lay them out again once it lands.
                const livewire = (window as any).Livewire;
                if (livewire?.hook) {
                    unhook = livewire.hook('commit', ({ succeed }: { succeed: (fn: () => void) => void }) => {
                        succeed(() => queueMicrotask(schedule));
                    });
                }
            },

            destroy() {
                observer?.disconnect();
                if (frame) cancelAnimationFrame(frame);
                if (typeof unhook === 'function') unhook();
            },
        };
    });
});

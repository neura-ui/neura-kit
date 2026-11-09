/**
 * The editor's File / Edit / View… menus share one panel.
 *
 * Going from one menu to the next — a click, the pointer passing over another
 * title while one is open, or the arrow keys — does not close one panel and
 * open another: the same frame slides under the new title and takes the new
 * menu's size, while only its items cross-fade. Opening from closed and
 * closing keep the usual popup motion (`menu-morph`).
 *
 * Names start with `menu` so they never hide the editor's own methods, which
 * the items call (`toggle('bold')`, `undo()`…).
 */

import { onAlpineInit } from '../../../runtime/alpine';

/** How long the frame takes to slide and resize; in step with the CSS. */
const MOVE_MS = 280;

if (typeof window !== 'undefined') {
  onAlpineInit(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const Alpine = (window as any).Alpine as any;

    Alpine.data('nkMenubar', () => {
      let moving: number | undefined;
      // The menubar itself: inside a method, `$el` is whatever ran it.
      let bar: HTMLElement | null = null;

      return {
        init() {
          bar = this.$el as HTMLElement;
        },

        /** The open menu, by index; null when all are shut. */
        menuAt: null as number | null,
        /** The menu the frame shows: stays on the last one while it closes. */
        menuShown: 0,
        /** The open submenu in the open menu, by item index. */
        menuSub: null as number | null,

        menuTriggers(): HTMLElement[] {
          return Array.from(bar?.querySelectorAll<HTMLElement>('[data-menubar-trigger]') ?? []);
        },

        menuFrame(): HTMLElement {
          return bar!.querySelector<HTMLElement>('.nk-menubar-frame')!;
        },

        /** Lay the frame under `index`'s title, sized to its items; `glide` animates it there. */
        menuPlace(index: number, glide: boolean) {
          const frame = this.menuFrame();
          const trigger = this.menuTriggers()[index];
          const list = frame.querySelector<HTMLElement>(`[data-menubar-list="${index}"]`);
          if (!trigger || !list) return;

          // A shut frame is measured shown for an instant: nothing paints in between.
          const hidden = getComputedStyle(frame).display === 'none';
          if (hidden) frame.style.display = 'block';
          const borders = frame.offsetWidth - frame.clientWidth;
          const width = list.offsetWidth + borders;
          const height = list.offsetHeight + borders;
          const nav = bar!.getBoundingClientRect();
          const left = Math.max(0, Math.min(trigger.offsetLeft, window.innerWidth - 8 - nav.left - width));
          if (hidden) frame.style.display = 'none';

          frame.classList.toggle('is-placing', !glide);
          frame.style.width = `${width}px`;
          frame.style.height = `${height}px`;
          frame.style.translate = `${left}px 0`;
          if (!glide) {
            void frame.offsetWidth;
            frame.classList.remove('is-placing');
            return;
          }

          // While it moves, what slides out is clipped to it; after, submenus may overflow.
          frame.classList.add('is-moving');
          window.clearTimeout(moving);
          moving = window.setTimeout(() => frame.classList.remove('is-moving'), MOVE_MS + 40);
        },

        menuOpen(index: number) {
          const glide = this.menuAt !== null;
          if (this.menuAt === index) return;
          // The panel grows out of the title it opens from.
          this.menuTriggers().forEach((trigger, at) => trigger.toggleAttribute('data-menu-trigger', at === index));
          this.menuPlace(index, glide);
          this.menuSub = null;
          this.menuShown = index;
          this.menuAt = index;
        },

        menuClose() {
          this.menuAt = null;
          this.menuSub = null;
        },

        menuToggle(index: number) {
          if (this.menuAt === index) this.menuClose();
          else this.menuOpen(index);
        },

        /** Passing over another title while a menu is open opens that one instead. */
        menuHover(index: number) {
          if (this.menuAt === null || this.menuAt === index) return;
          const triggers = this.menuTriggers();
          const focused = triggers.includes(document.activeElement as HTMLElement);
          this.menuOpen(index);
          // Focus follows, so the keys carry on from the menu the pointer opened.
          if (focused) triggers[index]?.focus({ preventScroll: true });
        },

        /** Where a list sits relative to the one shown: it slides in from that side. */
        menuSide(index: number): 'before' | 'current' | 'after' {
          return index < this.menuShown ? 'before' : index > this.menuShown ? 'after' : 'current';
        },

        menuKey(event: KeyboardEvent) {
          if (this.menuAt === null) return;
          const count = this.menuTriggers().length;
          if (event.key === 'Escape') {
            event.preventDefault();
            const trigger = this.menuTriggers()[this.menuAt];
            this.menuClose();
            trigger?.focus();
          } else if ((event.key === 'ArrowRight' || event.key === 'ArrowLeft') && this.menuSub === null) {
            event.preventDefault();
            const next = (this.menuAt + (event.key === 'ArrowRight' ? 1 : -1) + count) % count;
            this.menuOpen(next);
            this.menuTriggers()[next]?.focus();
          }
        },
      };
    });
  });
}

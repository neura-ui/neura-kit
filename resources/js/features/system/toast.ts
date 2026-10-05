import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

type ToastType = 'success' | 'error' | 'warning' | 'info';

interface ToastDetail {
  content: string;
  type: ToastType;
  duration: number;
}

const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
const PEEK = 14;
const SCALE_STEP = 0.05;
const GAP = 10;
const VISIBLE = 3;

interface ToastItem {
  id: number;
  type: ToastType;
  content: string;
  duration: number;
  showProgress: boolean;
  left: number;
  mark: number;
}

interface ToastConfig {
  maxToasts?: number;
  fromBottom?: boolean;
  session?: { content?: string; type?: string; duration?: number; showProgress?: boolean } | null;
}

function tone(type: string | undefined): ToastType {
  return type === 'success' || type === 'error' || type === 'warning' || type === 'info' ? type : 'info';
}

if (typeof window !== 'undefined') {
  window.NeuraKitToast = {
    success(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'success', duration },
        })
      );
    },

    error(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'error', duration },
        })
      );
    },

    warning(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'warning', duration },
        })
      );
    },

    info(content: string, duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type: 'info', duration },
        })
      );
    },

    show(content: string, type: ToastType = 'info', duration = 4000): void {
      window.dispatchEvent(
        new CustomEvent<ToastDetail>('notify', {
          detail: { content, type, duration },
        })
      );
    },
  };
}

onAlpineInit(() => {
  window.Alpine.data('neuraToast', (config: ToastConfig = {}) => ({
    toasts: [] as ToastItem[],
    expanded: false,
    dragging: false,
    maxToasts: Math.max(1, Number(config.maxToasts) || 5),
    fromBottom: config.fromBottom !== false,
    _frame: 0,
    _ro: null as ResizeObserver | null,
    _seq: 0,
    _seen: new WeakMap<HTMLElement, number>(),
    _closing: new Set<string>(),
    _drag: null as { id: number; x: number; dx: number; t: number; card: HTMLElement } | null,
    _onVisibility: null as (() => void) | null,
    _onHotkey: null as ((event: KeyboardEvent) => void) | null,

    init() {
      this._ro = new ResizeObserver((entries) => {
        const changed = entries.some((entry) => {
          const el = entry.target as HTMLElement;
          const next = el.offsetHeight;
          return this._seen.get(el) !== next;
        });
        if (changed) this.place(false);
      });
      this._onVisibility = () => {
        const now = performance.now();
        for (const toast of this.toasts) toast.mark = now;
        this.$root.classList.toggle('is-held', this.held);
      };
      this._onHotkey = (event: KeyboardEvent) => this.onHotkey(event);
      document.addEventListener('visibilitychange', this._onVisibility);
      window.addEventListener('keydown', this._onHotkey);
      if (config.session?.content) this.addToast(config.session);
      this.loop();
    },

    destroy() {
      if (this._frame) cancelAnimationFrame(this._frame);
      this._ro?.disconnect();
      if (this._onVisibility) document.removeEventListener('visibilitychange', this._onVisibility);
      if (this._onHotkey) window.removeEventListener('keydown', this._onHotkey);
    },

    get held(): boolean {
      return this.expanded || this.dragging || document.hidden;
    },

    addToast(details: { content?: string; type?: string; duration?: number; showProgress?: boolean }) {
      if (!details?.content) return;
      const duration = Number(details.duration ?? 4000);
      this.toasts.unshift({
        id: ++this._seq,
        type: tone(details.type),
        content: details.content,
        duration,
        showProgress: details.showProgress !== false,
        left: duration,
        mark: 0,
      });
      if (this.toasts.length > this.maxToasts) this.toasts.pop();
    },

    removeToast(id: number) {
      this._closing.delete(String(id));
      this.toasts = this.toasts.filter((toast) => toast.id !== id);
      this.$nextTick(() => this.place(true));
    },

    dismiss(id: number) {
      const key = String(id);
      if (this._closing.has(key)) return;
      const card = this.card(id);
      if (!card) {
        this.removeToast(id);
        return;
      }
      this._closing.add(key);
      const ms = prefersReducedMotion() ? 120 : 200;
      const y = card.dataset.y || '0';
      const scale = card.dataset.scale || '1';
      card.animate(
        [
          { opacity: 1 },
          { opacity: 0, transform: `translate3d(0px, ${Number(y) + (this.fromBottom ? 10 : -10)}px, 0) scale(${scale})` },
        ],
        { duration: ms, easing: EASE, fill: 'forwards' },
      );
      window.setTimeout(() => this.removeToast(id), ms);
    },

    card(id: number): HTMLElement | null {
      return this.$root.querySelector<HTMLElement>(`[data-id="${id}"]`);
    },

    front(): HTMLElement | null {
      return this.toasts[0] ? this.card(this.toasts[0].id) : null;
    },

    mountCard(el: HTMLElement) {
      if (!prefersReducedMotion()) {
        const y = this.fromBottom ? el.offsetHeight : -el.offsetHeight;
        el.style.transform = `translate3d(0px, ${y}px, 0) scale(0.96)`;
        el.style.opacity = '0';
      }
      requestAnimationFrame(() => {
        this.place(true);
        this._ro?.observe(el);
      });
    },

    shownCount(): number {
      return Math.min(VISIBLE, this.maxToasts, this.toasts.length);
    },

    place(animate: boolean) {
      const pile = this.$refs.pile as HTMLElement | undefined;
      if (!pile) return;
      const cards = [...pile.querySelectorAll<HTMLElement>('[data-toast]')];
      const shown = Math.min(VISIBLE, this.maxToasts, cards.length);
      const heights = cards.map((card) => card.offsetHeight || 0);
      const expanded = this.expanded && !this.dragging;
      let cursor = 0;
      cards.forEach((card, index) => {
        const hidden = index >= shown;
        const y = hidden
          ? (this.fromBottom ? -1 : 1) * PEEK * Math.max(0, shown - 1)
          : expanded
            ? (this.fromBottom ? -cursor : cursor)
            : (this.fromBottom ? -1 : 1) * PEEK * index;
        const scale = hidden || expanded ? (hidden ? 0.92 : 1) : 1 - SCALE_STEP * index;
        if (!hidden && expanded) cursor += heights[index] + GAP;
        card.dataset.y = String(y);
        card.dataset.scale = String(scale);
        card.style.zIndex = String(hidden ? 0 : shown - index);
        card.style.pointerEvents = !expanded && index > 0 ? 'none' : 'auto';
        if (!expanded && index > 0) card.setAttribute('inert', '');
        else card.removeAttribute('inert');
        if (this._drag?.card === card) return;
        this.move(card, y, scale, hidden ? 0 : 1, animate);
      });
      cards.forEach((card, index) => this._seen.set(card, heights[index]));
      const front = heights[0] || 0;
      const peeks = Math.max(0, shown - 1) * PEEK;
      pile.style.height = cards.length
        ? `${expanded ? Math.max(front, cursor - GAP) : front + peeks}px`
        : '0px';
      this.$root.classList.toggle('is-held', this.held);
    },

    move(card: HTMLElement, y: number, scale: number, opacity: number, animate: boolean) {
      card.getAnimations().forEach((animation) => animation.cancel());
      const next = `translate3d(var(--tx, 0px), ${y}px, 0) scale(${scale})`;
      if (!animate || prefersReducedMotion()) {
        card.style.transform = next;
        card.style.opacity = String(opacity);
        return;
      }
      const from = getComputedStyle(card).transform;
      card.animate(
        [
          { transform: from === 'none' ? next : from, opacity: getComputedStyle(card).opacity },
          { transform: next, opacity },
        ],
        { duration: 420, easing: EASE, fill: 'forwards' },
      );
      card.style.transform = next;
      card.style.opacity = String(opacity);
    },

    expand(open: boolean) {
      if (!open && this.$refs.pile?.contains(document.activeElement)) return;
      if (this.expanded === open) return;
      this.expanded = open;
      this.place(true);
    },

    onFocusOut(event: FocusEvent) {
      const pile = this.$refs.pile as HTMLElement | undefined;
      if (pile?.contains(event.relatedTarget as Node | null)) return;
      this.expanded = false;
      this.place(true);
    },

    onHotkey(event: KeyboardEvent) {
      if (!event.altKey || event.key.toLowerCase() !== 't' || !this.toasts.length) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      this.expanded = true;
      this.place(true);
      this.front()?.focus();
    },

    onKey(event: KeyboardEvent) {
      if (event.key !== 'Escape' || !this.toasts.length) return;
      if (!this.$root.contains(document.activeElement)) return;
      event.preventDefault();
      const card = (document.activeElement as HTMLElement | null)?.closest<HTMLElement>('[data-toast]');
      const id = card ? Number(card.dataset.id) : this.toasts[0].id;
      this.dismiss(id);
    },

    down(event: PointerEvent, id: number) {
      if (event.button !== 0) return;
      const target = event.target as HTMLElement | null;
      if (target?.closest('button')) return;
      const card = event.currentTarget as HTMLElement;
      this._drag = { id, x: event.clientX, dx: 0, t: event.timeStamp, card };
      this.dragging = true;
      this.$root.classList.toggle('is-held', true);
      try { card.setPointerCapture(event.pointerId); } catch { /* synthetic events */ }
    },

    movePointer(event: PointerEvent) {
      if (!this._drag || event.currentTarget !== this._drag.card) return;
      const dx = event.clientX - this._drag.x;
      this._drag.dx = dx;
      const shown = dx < 0 ? dx * 0.28 : dx;
      this._drag.card.style.setProperty('--tx', `${shown}px`);
    },

    up(event: PointerEvent) {
      if (!this._drag || event.currentTarget !== this._drag.card) return;
      const drag = this._drag;
      this._drag = null;
      this.dragging = false;
      const dt = Math.max(16, event.timeStamp - drag.t);
      const velocity = drag.dx / dt;
      const y = drag.card.dataset.y || '0';
      const scale = drag.card.dataset.scale || '1';
      const from = getComputedStyle(drag.card).transform;
      if (drag.dx > 72 || velocity > 0.55) {
        const ms = prefersReducedMotion() ? 120 : 220;
        drag.card.animate(
          [
            { transform: from, opacity: 1 },
            { transform: `translate3d(${prefersReducedMotion() ? 0 : 110}%, ${y}px, 0) scale(${scale})`, opacity: 0 },
          ],
          { duration: ms, easing: EASE, fill: 'forwards' },
        );
        this._closing.add(String(drag.id));
        window.setTimeout(() => this.removeToast(drag.id), ms);
        return;
      }
      drag.card.style.setProperty('--tx', '0px');
      drag.card.animate(
        [
          { transform: from },
          { transform: `translate3d(0px, ${y}px, 0) scale(${scale})` },
        ],
        { duration: prefersReducedMotion() ? 0 : 280, easing: EASE, fill: 'forwards' },
      );
      drag.card.style.transform = `translate3d(var(--tx, 0px), ${y}px, 0) scale(${scale})`;
      this.$root.classList.toggle('is-held', this.held);
    },

    loop() {
      const now = performance.now();
      const paused = this.held;
      for (const toast of this.toasts) {
        if (!toast.duration || toast.left <= 0 || this._closing.has(String(toast.id))) continue;
        if (!toast.mark) toast.mark = now;
        if (paused) {
          toast.mark = now;
          continue;
        }
        toast.left -= now - toast.mark;
        toast.mark = now;
        const bar = this.card(toast.id)?.querySelector<HTMLElement>('.nk-toast-timer span');
        if (bar) bar.style.transform = `scaleX(${Math.max(0, toast.left / toast.duration)})`;
        if (toast.left <= 0) this.dismiss(toast.id);
      }
      this._frame = requestAnimationFrame(() => this.loop());
    },

    labelFor(type: ToastType): string {
      const key = type === 'info' ? 'information' : type;
      return typeof window.t === 'function' ? window.t(key) : type;
    },
  }));
});

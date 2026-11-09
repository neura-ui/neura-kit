import { onAlpineInit } from '../../runtime/alpine';

/**
 * Tooltip and hover card.
 *
 * Both open on a mouse hover after a delay and on keyboard focus (never on a click or a tap,
 * which belong to the trigger). The tooltip shares the kit's skip window: within a moment of
 * one closing, the next opens at once and only fades. Positions come from x-anchor, which
 * flips a panel that has no room; the side it lands on is read back so the arrow and the
 * motion follow.
 */

interface TooltipConfig {
    delay?: number;
    disabled?: boolean;
    /** Only when the trigger's text is cut short (an ellipsis). */
    overflow?: boolean;
}

interface HoverCardConfig {
    delay?: number;
    closeDelay?: number;
    disabled?: boolean;
}

/** The side a panel landed on, from where it sits next to its trigger. */
function landedSide(panel: HTMLElement, trigger: HTMLElement): string | null {
    const p = panel.getBoundingClientRect();
    const t = trigger.getBoundingClientRect();
    if (!p.width || !t.width) return null;
    if (p.bottom <= t.top + 1) return 'top';
    if (p.top >= t.bottom - 1) return 'bottom';
    if (p.right <= t.left + 1) return 'left';
    if (p.left >= t.right - 1) return 'right';
    return null;
}

/** Keep `data-side` true to where x-anchor put the panel. */
function followSide(panel: HTMLElement, trigger: () => HTMLElement | null): MutationObserver {
    const observer = new MutationObserver(() => {
        const anchor = trigger();
        if (!anchor || panel.style.display === 'none') return;
        const side = landedSide(panel, anchor);
        if (side && panel.dataset.side !== side) panel.dataset.side = side;
    });
    observer.observe(panel, { attributes: true, attributeFilter: ['style'] });
    return observer;
}

/** Whether some text in the trigger is cut short by its box. */
function isCut(trigger: HTMLElement): boolean {
    return [trigger, ...Array.from(trigger.querySelectorAll<HTMLElement>('*'))].some(
        (el) => el.clientWidth > 0 && el.scrollWidth > el.clientWidth + 1,
    );
}

function focusVisible(event: FocusEvent): boolean {
    const target = event.target as Element | null;
    try {
        return !!target?.matches(':focus-visible');
    } catch {
        return true;
    }
}

onAlpineInit(() => {
    window.Alpine.data('neuraTooltip', (config: TooltipConfig = {}) => {
        const delay = Math.max(0, Number(config.delay ?? 250));
        let timer = 0;
        let observer: MutationObserver | null = null;

        return {
            open: false,
            instant: false,

            init(this: any) {
                const tip = this.$refs.tip as HTMLElement | undefined;
                if (!tip) return;

                this.$nextTick(() => {
                    // The trigger is the first element of the slot; it is described by the tip.
                    const trigger = Array.from((this.$el as HTMLElement).children).find((el) => el !== tip) as HTMLElement | undefined;
                    if (trigger && tip.id) {
                        const ids = (trigger.getAttribute('aria-describedby') ?? '').split(/\s+/).filter(Boolean);
                        if (!ids.includes(tip.id)) trigger.setAttribute('aria-describedby', [...ids, tip.id].join(' '));
                    }
                });

                observer = followSide(tip, () => this.$el as HTMLElement);

                this.$watch('open', (value: boolean) => {
                    const warmth = window.NeuraKitTooltipWarm;
                    if (!warmth) return;
                    if (value) warmth.opened();
                    else warmth.closed();
                });
            },

            destroy() {
                window.clearTimeout(timer);
                observer?.disconnect();
            },

            /** The tooltip has something to add: not disabled, and with `overflow`, the text is cut. */
            wanted(this: any): boolean {
                if (config.disabled || !this.$refs.tip) return false;
                return !config.overflow || isCut(this.$el as HTMLElement);
            },

            enter(this: any, event?: PointerEvent) {
                if (event?.pointerType === 'touch' || !this.wanted()) return;
                window.clearTimeout(timer);
                const warm = !!window.NeuraKitTooltipWarm?.warm;
                timer = window.setTimeout(() => {
                    this.instant = !!window.NeuraKitTooltipWarm?.warm;
                    this.open = true;
                }, warm ? 0 : delay);
            },

            focus(this: any, event: FocusEvent) {
                if (!focusVisible(event) || !this.wanted()) return;
                window.clearTimeout(timer);
                this.instant = !!window.NeuraKitTooltipWarm?.warm;
                this.open = true;
            },

            leave(this: any) {
                window.clearTimeout(timer);
                this.open = false;
            },

            hide(this: any) {
                window.clearTimeout(timer);
                this.open = false;
            },
        };
    });

    window.Alpine.data('neuraHoverCard', (config: HoverCardConfig = {}) => {
        const delay = Math.max(0, Number(config.delay ?? 300));
        const closeDelay = Math.max(0, Number(config.closeDelay ?? 150));
        let openTimer = 0;
        let closeTimer = 0;
        let observer: MutationObserver | null = null;

        const clear = () => {
            window.clearTimeout(openTimer);
            window.clearTimeout(closeTimer);
        };

        return {
            open: false,

            init(this: any) {
                this.$nextTick(() => {
                    const card = this.$refs.card as HTMLElement | undefined;
                    if (card) observer = followSide(card, () => this.$refs.trigger as HTMLElement | null);
                });
            },

            destroy() {
                clear();
                observer?.disconnect();
            },

            within(this: any, node: Node | null): boolean {
                if (!node) return false;
                const card = this.$refs.card as HTMLElement | undefined;
                return (this.$el as HTMLElement).contains(node) || !!card?.contains(node);
            },

            enter(this: any, event?: PointerEvent) {
                if (config.disabled || event?.pointerType === 'touch') return;
                window.clearTimeout(closeTimer);
                if (this.open) return;
                window.clearTimeout(openTimer);
                openTimer = window.setTimeout(() => {
                    this.open = true;
                }, delay);
            },

            leave(this: any, event?: PointerEvent) {
                if (event?.pointerType === 'touch') return;
                window.clearTimeout(openTimer);
                // The pointer has a moment to cross the gap between the trigger and the card.
                closeTimer = window.setTimeout(() => {
                    const card = this.$refs.card as HTMLElement | undefined;
                    if (card?.contains(document.activeElement)) return;
                    this.open = false;
                }, closeDelay);
            },

            focus(this: any, event: FocusEvent) {
                if (config.disabled || !focusVisible(event)) return;
                clear();
                openTimer = window.setTimeout(() => {
                    this.open = true;
                }, delay);
            },

            keep() {
                clear();
            },

            blur(this: any, event: FocusEvent) {
                if (this.within(event.relatedTarget as Node | null)) return;
                clear();
                this.open = false;
            },

            hide(this: any, refocus = false) {
                clear();
                this.open = false;
                if (refocus) {
                    const trigger = this.$refs.trigger as HTMLElement | undefined;
                    trigger?.querySelector<HTMLElement>('a, button, [tabindex]')?.focus();
                }
            },
        };
    });
});

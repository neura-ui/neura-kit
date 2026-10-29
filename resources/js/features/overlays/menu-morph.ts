/**
 * Popup open motion, matched to Arc's Add filter menu.
 *
 * The panel grows out of its trigger (width and height as scale, so it
 * composes with the anchor transform) while the content fades through a
 * 2px blur. Closing folds back into the trigger.
 */

import { motionTokens, prefersReducedMotion } from '../../shared/motion-tokens';

const OPEN = motionTokens.duration.spring;
const CLOSE = 180;

function clamp(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}

/** A form control: its popup belongs to the whole field, not to an icon button inside it. */
const FIELD = 'input:not([type="hidden"]), textarea, select, [data-slot="control"]';

/**
 * The box a panel grows out of.
 *
 * A component can name it with `data-menu-trigger`, which wins over anything
 * in between (a hint under a field, for one). Otherwise it is the closest
 * earlier sibling: a field as a whole, else the button it holds, else the
 * sibling itself. Picking the first button inside a field made the color
 * picker unfold from the little swatch icon at its right end.
 */
function triggerOf(panel: HTMLElement): HTMLElement | null {
    for (let sibling = panel.previousElementSibling; sibling; sibling = sibling.previousElementSibling) {
        const marked = sibling.matches('[data-menu-trigger]')
            ? sibling
            : sibling.querySelector('[data-menu-trigger]');

        if (marked instanceof HTMLElement) {
            return marked;
        }
    }

    let sibling = panel.previousElementSibling;

    while (sibling) {
        if (sibling instanceof HTMLElement) {
            if (sibling.getBoundingClientRect().height > 0 && (sibling.matches(FIELD) || sibling.querySelector(FIELD))) {
                return sibling;
            }

            const button = sibling.matches('button, a, [role="button"]')
                ? sibling
                : sibling.querySelector('button, a, [role="button"]');

            if (button instanceof HTMLElement) {
                return button;
            }

            if (sibling.getBoundingClientRect().height > 0) {
                return sibling;
            }
        }

        sibling = sibling.previousElementSibling;
    }

    return null;
}

/**
 * The corner radius an element actually paints, in px.
 *
 * A pill computes to `calc(infinity * 1px)` (about 3.4e7px) and a percentage
 * resolves against the box, so both are brought back to what is drawn: never
 * more than half the shorter side. Layout sizes are used, not the bounding
 * box, so a scale already running on the element does not shrink the cap.
 */
function paintedRadius(el: HTMLElement): number {
    const value = getComputedStyle(el).borderTopLeftRadius;
    const amount = parseFloat(value);

    if (!Number.isFinite(amount) || amount <= 0) {
        return 0;
    }

    const side = Math.min(el.offsetWidth, el.offsetHeight);
    const px = value.trim().endsWith('%') ? (amount / 100) * side : amount;

    return Math.min(px, side / 2);
}

function scales(panel: HTMLElement): { x: number; y: number; from: string; to: string; origin: string } {
    const trigger = triggerOf(panel);
    const panelBox = panel.getBoundingClientRect();
    const to = `${paintedRadius(panel)}px`;

    if (!trigger || panelBox.width < 8 || panelBox.height < 8) {
        return { x: 0.92, y: 0.7, from: to, to, origin: '' };
    }

    const triggerBox = trigger.getBoundingClientRect();
    const x = clamp(triggerBox.width / panelBox.width, 0.42, 1);
    const y = clamp(triggerBox.height / panelBox.height, 0.22, 1);

    // The scale shrinks the panel's corners with it, by x across and y down.
    // Dividing each axis back out makes the first frame's corners the
    // trigger's own, whatever its shape.
    const corner = paintedRadius(trigger);
    const from = `${corner / x}px / ${corner / y}px`;
    const originX = Math.abs(1 - x) < 0.001 ? panelBox.width / 2 : (triggerBox.left - panelBox.left) / (1 - x);
    const originY = Math.abs(1 - y) < 0.001 ? panelBox.height / 2 : (triggerBox.top - panelBox.top) / (1 - y);

    return { x, y, from, to, origin: `${originX}px ${originY}px` };
}

function running(panel: HTMLElement): Animation | null {
    return (panel as HTMLElement & { _nkMorph?: Animation })._nkMorph ?? null;
}

function remember(panel: HTMLElement, animation: Animation | null) {
    (panel as HTMLElement & { _nkMorph?: Animation | null })._nkMorph = animation;
}

function play(panel: HTMLElement, opening: boolean) {
    if (prefersReducedMotion() || panel.classList.contains('nk-tooltip')) {
        return;
    }

    const token = ((panel as HTMLElement & { _nkMorphToken?: number })._nkMorphToken ?? 0) + 1;
    (panel as HTMLElement & { _nkMorphToken?: number })._nkMorphToken = token;

    const start = () => {
        if ((panel as HTMLElement & { _nkMorphToken?: number })._nkMorphToken !== token) {
            return;
        }

        if (opening && panel.classList.contains('nk-menu-leaving')) {
            return;
        }

        const current = getComputedStyle(panel).scale;
        const currentScale = current && current !== 'none' ? current : '1 1';

        running(panel)?.cancel();

        const { x, y, from, to, origin } = scales(panel);
        const folded = `${x} ${y}`;
        const originFrame = origin ? { transformOrigin: origin } : {};
        const animation = panel.animate(
            opening
                ? [
                    { scale: folded, borderRadius: from, ...originFrame },
                    { scale: '1 1', borderRadius: to, ...originFrame },
                ]
                : [
                    { scale: currentScale, borderRadius: to, ...originFrame },
                    { scale: folded, borderRadius: from, ...originFrame },
                ],
            {
                duration: opening ? OPEN : CLOSE,
                easing: opening ? motionTokens.ease.enter : motionTokens.ease.standard,
                fill: 'both',
            },
        );

        remember(panel, animation);

        animation.onfinish = () => {
            if (running(panel) !== animation) {
                return;
            }

            animation.cancel();
            panel.style.scale = '';
            panel.style.borderRadius = '';
            remember(panel, null);
        };
    };

    if (panel.getBoundingClientRect().width < 8) {
        requestAnimationFrame(() => requestAnimationFrame(start));
        return;
    }

    start();
}

function onClass(panel: HTMLElement) {
    if (panel.classList.contains('nk-tooltip')) {
        return;
    }

    const state = panel as HTMLElement & { _nkMorphPhase?: 'open' | 'close' | null };
    const leaving = panel.classList.contains('nk-menu-leaving');
    const entering = panel.classList.contains('nk-menu-from') && !panel.classList.contains('nk-menu-to');

    if (!leaving && !entering) {
        state._nkMorphPhase = null;
        return;
    }

    if (leaving) {
        if (state._nkMorphPhase === 'close') {
            return;
        }

        state._nkMorphPhase = 'close';
        play(panel, false);
        return;
    }

    if (state._nkMorphPhase === 'open') {
        return;
    }

    state._nkMorphPhase = 'open';
    play(panel, true);
}

const seen = new WeakSet<HTMLElement>();

function watch(panel: HTMLElement) {
    if (seen.has(panel) || panel.classList.contains('nk-tooltip')) {
        return;
    }

    seen.add(panel);
    onClass(panel);
}

function scan(root: ParentNode) {
    if (root instanceof HTMLElement && root.classList.contains('nk-menu-motion')) {
        watch(root);
    }

    root.querySelectorAll?.('.nk-menu-motion').forEach((node) => {
        if (node instanceof HTMLElement) {
            watch(node);
        }
    });
}

const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        if (mutation.type === 'attributes' && mutation.target instanceof HTMLElement) {
            if (mutation.target.classList.contains('nk-menu-motion')) {
                onClass(mutation.target);
            }
            continue;
        }

        mutation.addedNodes.forEach((node) => {
            if (node instanceof HTMLElement) {
                scan(node);
            }
        });
    }
});

const root = window as Window & { __nkMenuMorph?: boolean };

if (typeof document !== 'undefined' && !root.__nkMenuMorph) {
    root.__nkMenuMorph = true;
    scan(document);
    observer.observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['class'],
    });
}

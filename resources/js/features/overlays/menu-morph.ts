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

function triggerOf(panel: HTMLElement): HTMLElement | null {
    let sibling = panel.previousElementSibling;

    while (sibling) {
        if (sibling instanceof HTMLElement) {
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

function scales(panel: HTMLElement): { x: number; y: number; radius: string; origin: string } {
    const trigger = triggerOf(panel);
    const panelBox = panel.getBoundingClientRect();
    const endRadius = getComputedStyle(panel).borderTopLeftRadius || '8px';

    if (!trigger || panelBox.width < 8 || panelBox.height < 8) {
        return { x: 0.92, y: 0.7, radius: endRadius, origin: '' };
    }

    const triggerBox = trigger.getBoundingClientRect();
    const x = clamp(triggerBox.width / panelBox.width, 0.42, 1);
    const y = clamp(triggerBox.height / panelBox.height, 0.22, 1);
    const triggerRadius = parseFloat(getComputedStyle(trigger).borderTopLeftRadius) || triggerBox.height / 2;
    const radius = `${triggerRadius / Math.min(x, y)}px`;
    const originX = Math.abs(1 - x) < 0.001 ? panelBox.width / 2 : (triggerBox.left - panelBox.left) / (1 - x);
    const originY = Math.abs(1 - y) < 0.001 ? panelBox.height / 2 : (triggerBox.top - panelBox.top) / (1 - y);

    return { x, y, radius, origin: `${originX}px ${originY}px` };
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

        const { x, y, radius, origin } = scales(panel);
        const endRadius = getComputedStyle(panel).borderTopLeftRadius || '8px';
        const from = `${x} ${y}`;
        const originFrame = origin ? { transformOrigin: origin } : {};
        const animation = panel.animate(
            opening
                ? [
                    { scale: from, borderRadius: radius, ...originFrame },
                    { scale: '1 1', borderRadius: endRadius, ...originFrame },
                ]
                : [
                    { scale: currentScale, borderRadius: endRadius, ...originFrame },
                    { scale: from, borderRadius: radius, ...originFrame },
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

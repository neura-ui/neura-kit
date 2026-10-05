/**
 * Shared skip window for tooltips.
 *
 * The first tooltip waits, then rises from its trigger. For a short time
 * after one closes, the next only fades, so a toolbar can be scanned quickly.
 */

const SKIP_WINDOW = 300;

const warmth = {
    warm: false,
    count: 0,
    timer: 0,
    opened() {
        this.count += 1;
        window.clearTimeout(this.timer);
        this.warm = true;
    },
    closed() {
        this.count = Math.max(0, this.count - 1);
        if (this.count) return;
        window.clearTimeout(this.timer);
        this.timer = window.setTimeout(() => {
            this.warm = false;
        }, SKIP_WINDOW);
    },
};

window.NeuraKitTooltipWarm = warmth;

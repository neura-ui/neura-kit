/**
 * Breadcrumb folding.
 *
 * A trail folds its middle into "…": everything between the first `before` items and the
 * last `after`. A responsive trail goes through three layouts, the widest that fits wins:
 * the full trail (less what `max-items` already folds), the folded trail, and a lone link
 * back to the parent. No imports, so it runs in Node.
 */

export type Mode = 'full' | 'folded' | 'back';

/** Positions of the items that fold, in order. */
export function middle(count: number, before: number, after: number): number[] {
    const start = Math.max(0, before);
    const end = count - Math.max(1, after);
    const out: number[] = [];
    for (let i = start; i < end; i++) out.push(i);
    return out;
}

/** The items hidden in a layout: `base` are those folded from the start (max-items). */
export function hiddenIn(mode: Mode, count: number, before: number, after: number, base: number[] = []): number[] {
    if (mode !== 'folded') return base.slice().sort((a, b) => a - b);
    return [...new Set([...base, ...middle(count, before, after)])].sort((a, b) => a - b);
}

/**
 * The layouts to try, widest first. Folding is skipped when it would hide nothing new, and the
 * back link when there is none.
 */
export function modes(count: number, before: number, after: number, base: number[], hasBack: boolean): Mode[] {
    const out: Mode[] = ['full'];
    if (hiddenIn('folded', count, before, after, base).length > base.length) out.push('folded');
    if (hasBack) out.push('back');
    return out;
}

/** The first layout that fits, given a test that applies a layout and measures it. */
export function settle(candidates: Mode[], fits: (mode: Mode) => boolean): Mode {
    for (const mode of candidates) {
        if (fits(mode)) return mode;
    }
    return candidates[candidates.length - 1] ?? 'full';
}

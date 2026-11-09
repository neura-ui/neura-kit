/**
 * What changed between two versions of a text.
 *
 * Paragraphs are paired first (unchanged ones match, changed ones face each
 * other), then a pair is compared word by word, each word keeping the space
 * after it so the text can be put back together from the pieces. No imports,
 * so it runs in Node.
 */

export interface DiffOp {
  kind: 'same' | 'ins' | 'del';
  text: string;
}

/** Above this many word comparisons, a paragraph is shown as replaced whole. */
const MAX_CELLS = 400_000;

const words = (text: string): string[] => text.match(/\s+|\S+\s*/g) ?? [];

/** Longest common subsequence table over two lists, filled from the end. */
function lcs<T>(a: T[], b: T[], same: (x: T, y: T) => boolean): number[][] {
  const table = Array.from({ length: a.length + 1 }, () => new Array<number>(b.length + 1).fill(0));
  for (let i = a.length - 1; i >= 0; i -= 1) {
    for (let j = b.length - 1; j >= 0; j -= 1) {
      table[i][j] = same(a[i], b[j]) ? table[i + 1][j + 1] + 1 : Math.max(table[i + 1][j], table[i][j + 1]);
    }
  }
  return table;
}

export function diffWords(before: string, after: string): DiffOp[] {
  if (before === after) return before ? [{ kind: 'same', text: before }] : [];

  const a = words(before);
  const b = words(after);
  if (a.length * b.length > MAX_CELLS) {
    return [
      ...(before ? [{ kind: 'del' as const, text: before }] : []),
      ...(after ? [{ kind: 'ins' as const, text: after }] : []),
    ];
  }

  const same = (x: string, y: string) => x.trim() === y.trim();
  const table = lcs(a, b, same);
  const ops: DiffOp[] = [];
  const push = (kind: DiffOp['kind'], text: string) => {
    const last = ops[ops.length - 1];
    if (last && last.kind === kind) last.text += text;
    else ops.push({ kind, text });
  };

  let i = 0;
  let j = 0;
  while (i < a.length && j < b.length) {
    if (same(a[i], b[j])) {
      // The same word: the new version's spacing is the one shown.
      push('same', b[j]);
      i += 1;
      j += 1;
    } else if (table[i + 1][j] >= table[i][j + 1]) {
      push('del', a[i++]);
    } else {
      push('ins', b[j++]);
    }
  }
  while (i < a.length) push('del', a[i++]);
  while (j < b.length) push('ins', b[j++]);

  // Keep deletions before insertions where they touch, so "replace" reads in order.
  for (let k = 0; k < ops.length - 1; k += 1) {
    if (ops[k].kind === 'ins' && ops[k + 1].kind === 'del') [ops[k], ops[k + 1]] = [ops[k + 1], ops[k]];
  }
  return ops;
}

/**
 * Pair the paragraphs of two versions. Identical ones match; between two
 * matches, paragraphs face each other in order (changed), and what is left
 * over was added or taken away.
 */
export function pairBlocks(before: string[], after: string[]): Array<{ before: number | null; after: number | null }> {
  const table = lcs(before, after, (x, y) => x === y);
  const pairs: Array<{ before: number | null; after: number | null }> = [];
  let gapBefore: number[] = [];
  let gapAfter: number[] = [];

  const flush = () => {
    const shared = Math.min(gapBefore.length, gapAfter.length);
    for (let k = 0; k < shared; k += 1) pairs.push({ before: gapBefore[k], after: gapAfter[k] });
    for (let k = shared; k < gapBefore.length; k += 1) pairs.push({ before: gapBefore[k], after: null });
    for (let k = shared; k < gapAfter.length; k += 1) pairs.push({ before: null, after: gapAfter[k] });
    gapBefore = [];
    gapAfter = [];
  };

  let i = 0;
  let j = 0;
  while (i < before.length && j < after.length) {
    if (before[i] === after[j]) {
      flush();
      pairs.push({ before: i++, after: j++ });
    } else if (table[i + 1][j] >= table[i][j + 1]) {
      gapBefore.push(i++);
    } else {
      gapAfter.push(j++);
    }
  }
  while (i < before.length) gapBefore.push(i++);
  while (j < after.length) gapAfter.push(j++);
  flush();
  return pairs;
}

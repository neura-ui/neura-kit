/**
 * Find and replace over the live, paginated document.
 *
 * The text of each leaf block is read as one string, with where each of its
 * text nodes starts, so a match can span formatting ("Me<b>ta</b>") and be
 * turned back into a DOM range. Matches are painted with the CSS Custom
 * Highlight API, which marks ranges without touching the DOM: the document,
 * its history and the page flow never see a search.
 */

import { closestBlock } from './dom';
import { findInText } from './search';

export interface TextRun {
  block: HTMLElement;
  text: string;
  nodes: Array<{ node: Text; start: number }>;
}

/** The text of each leaf block in the page bodies, in reading order. */
export function textRuns(root: HTMLElement): TextRun[] {
  const runs: TextRun[] = [];
  const hosts = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content]'));

  for (const host of hosts.length ? hosts : [root]) {
    // A table of contents and footnote numbers are generated: nothing to find there.
    const walker = root.ownerDocument!.createTreeWalker(host, NodeFilter.SHOW_TEXT, {
      acceptNode: (node) =>
        node.parentElement?.closest('nav[data-toc], [data-footnote], [data-nk-repeat]') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT,
    });
    let run: TextRun | null = null;
    let node = walker.nextNode();
    while (node) {
      const text = node as Text;
      const block = closestBlock(text, root) ?? host;
      if (!run || run.block !== block) {
        run = { block, text: '', nodes: [] };
        runs.push(run);
      }
      run.nodes.push({ node: text, start: run.text.length });
      run.text += text.nodeValue ?? '';
      node = walker.nextNode();
    }
  }

  return runs;
}

/** The text run of one block. */
export function runOf(block: HTMLElement): TextRun {
  const run: TextRun = { block, text: '', nodes: [] };
  const walker = block.ownerDocument!.createTreeWalker(block, NodeFilter.SHOW_TEXT);
  let node = walker.nextNode();
  while (node) {
    const text = node as Text;
    run.nodes.push({ node: text, start: run.text.length });
    run.text += text.nodeValue ?? '';
    node = walker.nextNode();
  }
  return run;
}

/** A DOM range over characters `start` to `end` of a run. */
export function rangeIn(run: TextRun, start: number, end: number): Range | null {
  if (!run.nodes.length) return null;
  const locate = (offset: number, isEnd: boolean): [Text, number] => {
    for (const { node, start: from } of run.nodes) {
      const to = from + node.length;
      if (offset < to || (isEnd && offset === to)) return [node, Math.max(0, offset - from)];
    }
    const last = run.nodes[run.nodes.length - 1].node;
    return [last, last.length];
  };
  const range = run.block.ownerDocument!.createRange();
  const [startNode, startOffset] = locate(start, false);
  const [endNode, endOffset] = locate(end, true);
  range.setStart(startNode, startOffset);
  range.setEnd(endNode, endOffset);
  return range;
}

/** Every match of `pattern` in the document, as ranges in reading order. */
export function findRanges(root: HTMLElement, pattern: RegExp): Range[] {
  const ranges: Range[] = [];
  for (const run of textRuns(root)) {
    for (const [start, end] of findInText(run.text, pattern)) {
      const range = rangeIn(run, start, end);
      if (range) ranges.push(range);
    }
  }
  return ranges;
}

type HighlightRegistry = Map<string, unknown>;

function registry(): HighlightRegistry | null {
  const css = (globalThis as { CSS?: { highlights?: HighlightRegistry } }).CSS;
  const Highlight = (globalThis as { Highlight?: unknown }).Highlight;
  return css?.highlights && typeof Highlight === 'function' ? css.highlights : null;
}

/** Paint the matches, the current one apart. Browsers without the API simply show none. */
export function paintMatches(ranges: Range[], current: number): void {
  const highlights = registry();
  if (!highlights) return;
  const Highlight = (globalThis as unknown as { Highlight: new (...ranges: Range[]) => unknown }).Highlight;
  highlights.set('nk-find', new Highlight(...ranges.filter((_, index) => index !== current)));
  if (ranges[current]) highlights.set('nk-find-current', new Highlight(ranges[current]));
  else highlights.delete('nk-find-current');
}

export function clearMatches(): void {
  const highlights = registry();
  highlights?.delete('nk-find');
  highlights?.delete('nk-find-current');
}

/** The word around a collapsed caret, for commands that act on "this word". */
export function wordAt(range: Range): Range | null {
  const node = range.startContainer;
  if (node.nodeType !== Node.TEXT_NODE) return null;
  const text = node.nodeValue ?? '';
  const isWord = (char: string) => /[\p{L}\p{N}_'’-]/u.test(char);
  let start = range.startOffset;
  let end = range.startOffset;
  while (start > 0 && isWord(text.charAt(start - 1))) start -= 1;
  while (end < text.length && isWord(text.charAt(end))) end += 1;
  if (start === end) return null;
  const word = node.ownerDocument!.createRange();
  word.setStart(node, start);
  word.setEnd(node, end);
  return word;
}

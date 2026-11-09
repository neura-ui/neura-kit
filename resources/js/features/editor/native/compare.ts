/**
 * A saved version, drawn for reading — with what it changed, if asked.
 *
 * The version is cleaned like any value, then each paragraph is set against
 * its counterpart in the version before: added paragraphs and words are shown
 * in green, removed ones struck in red, put back where they stood.
 */

import { write as writeFootnotes } from './footnotes';
import { takeThreads } from './review';
import { sanitizeHtml } from './sanitize';
import { diffWords, pairBlocks } from './textdiff';

/** A version's blocks, as the reader sees them: footnotes as numbers and a list, no comment store. */
function blocksOf(html: string): HTMLElement {
  const fragment = sanitizeHtml(html);
  takeThreads(fragment);
  const container = document.createElement('div');
  container.appendChild(fragment);
  writeFootnotes(container);
  return container;
}

const textOf = (block: Element): string => (block.textContent ?? '').replace(/\s+/g, ' ').trim();

/** The text node and offset at a character position of a block (the start of the next node at a boundary). */
function pointAt(block: HTMLElement, position: number): { node: Text; offset: number } | null {
  const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
  let seen = 0;
  let last: Text | null = null;
  for (let node = walker.nextNode() as Text | null; node; node = walker.nextNode() as Text | null) {
    if (position < seen + node.length) return { node, offset: position - seen };
    seen += node.length;
    last = node;
  }
  return last ? { node: last, offset: last.length } : null;
}

function mark(kind: 'ins' | 'del', text?: string): HTMLElement {
  const element = document.createElement('span');
  element.className = kind === 'ins' ? 'nk-diff-ins' : 'nk-diff-del';
  if (text !== undefined) element.textContent = text;
  return element;
}

/** Wrap the characters `start`…`end` of a block, across however many text nodes they span. */
function wrapRange(block: HTMLElement, start: number, end: number): void {
  const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
  const pieces: Array<{ node: Text; from: number; to: number }> = [];
  let seen = 0;
  for (let node = walker.nextNode() as Text | null; node; node = walker.nextNode() as Text | null) {
    const from = Math.max(start, seen);
    const to = Math.min(end, seen + node.length);
    if (from < to) pieces.push({ node, from: from - seen, to: to - seen });
    seen += node.length;
    if (seen >= end) break;
  }
  for (const { node, from, to } of pieces) {
    let target = node;
    if (to < target.length) target.splitText(to);
    if (from > 0) target = target.splitText(from);
    const wrapper = mark('ins');
    target.parentNode?.insertBefore(wrapper, target);
    wrapper.appendChild(target);
  }
}

/**
 * Mark a changed paragraph word by word, against its old text. Positions are
 * worked out on the new text first, then applied from the end, so marking one
 * change never moves the next.
 */
function markWords(block: HTMLElement, before: string): void {
  const after = block.textContent ?? '';
  const ops = diffWords(before, after);
  const edits: Array<{ kind: 'ins' | 'del'; at: number; end: number; text: string }> = [];
  let position = 0;
  for (const op of ops) {
    if (op.kind === 'same') position += op.text.length;
    else if (op.kind === 'ins') {
      edits.push({ kind: 'ins', at: position, end: position + op.text.length, text: op.text });
      position += op.text.length;
    } else edits.push({ kind: 'del', at: position, end: position, text: op.text });
  }

  for (const edit of edits.reverse()) {
    if (edit.kind === 'ins') {
      wrapRange(block, edit.at, edit.end);
      continue;
    }
    const removed = mark('del', edit.text);
    const point = pointAt(block, edit.at);
    if (!point) {
      block.appendChild(removed);
      continue;
    }
    const at = point.offset > 0 && point.offset < point.node.length ? point.node.splitText(point.offset) : null;
    if (at) at.parentNode?.insertBefore(removed, at);
    else if (point.offset === 0) {
      // Where an added word starts, the removed one goes before its mark, not in it.
      let anchor: Node = point.node;
      while (anchor.parentElement?.classList.contains('nk-diff-ins') && anchor.parentElement.firstChild === anchor) anchor = anchor.parentElement;
      anchor.parentNode?.insertBefore(removed, anchor);
    } else point.node.parentNode?.insertBefore(removed, point.node.nextSibling);
  }
}

/** Draw `html` into `target`; with `previous`, mark what changed since it. */
export function renderVersion(target: HTMLElement, html: string, previous: string | null): void {
  const current = blocksOf(html);
  target.replaceChildren();

  if (previous === null) {
    while (current.firstChild) target.appendChild(current.firstChild);
    return;
  }

  const old = blocksOf(previous);
  const before = Array.from(old.children) as HTMLElement[];
  const after = Array.from(current.children) as HTMLElement[];

  for (const pair of pairBlocks(before.map(textOf), after.map(textOf))) {
    if (pair.after === null) {
      const removed = before[pair.before!];
      removed.classList.add('nk-diff-block-del');
      target.appendChild(removed);
      continue;
    }
    const block = after[pair.after];
    if (pair.before === null) block.classList.add('nk-diff-block-ins');
    else if (textOf(before[pair.before]) !== textOf(block)) markWords(block, before[pair.before].textContent ?? '');
    target.appendChild(block);
  }
}

/**
 * Review: comments on the text, and suggested changes.
 *
 * A comment is anchored by `<span data-comment="c-…">` around the text it is
 * about (several spans when it crosses formatting or blocks). Its thread —
 * who said what, when, whether it is resolved — travels with the document: a
 * hidden `<aside data-comments>` at the end of the HTML, `comments` in JSON.
 *
 * A suggestion is text typed or deleted in Suggesting mode: `<ins>` or `<del>`
 * with `data-suggest`, its author and time. Accepting keeps the insertion and
 * drops the deletion; rejecting does the opposite.
 */

import { blockNameOf, setBlockType } from './blocks';
import { closestBlock, isElement, normalizeInline, textNodesInRange, unwrap } from './dom';
import { applyMark, hasMark, markValue, removeMark, ZWSP } from './marks';
import type { BlockName, MarkName } from './schema';
import { ID_ATTR, PART_ATTR } from './selection';
import {
  addChange,
  blockAttribute,
  BLOCK_SUGGESTION_KINDS,
  parseBlockSuggestion,
  parseFormat,
  sameLook,
  sameValue,
  writeBlockSuggestion,
  type BlockLook,
  type BlockSuggestion,
  type BlockSuggestionKind,
  type FormatChange,
} from './suggestions';
import { validThreads, type Thread } from './threads';

export { validThreads, type ReviewComment, type Thread } from './threads';

export interface Suggestion {
  id: string;
  author: string;
  time: string;
  kind: 'insert' | 'delete' | 'replace' | 'format' | 'style' | 'split' | 'join';
  inserted: string;
  deleted: string;
  /** A formatting change: the marks set or taken off. */
  changes?: FormatChange[];
  /** A style change: the block's look now and before. */
  look?: BlockLook;
  before?: BlockLook;
}

let counter = 0;
const stamp = () => `${Date.now().toString(36)}${(counter++).toString(36)}`;
export const newCommentId = (): string => `c-${stamp()}`;
export const newSuggestionId = (): string => `s-${stamp()}`;

const SUGGEST = 'ins[data-suggest], del[data-suggest]';
/** Text whose formatting a suggestion changed. */
const FORMAT = 'span[data-suggest][data-format]';
/** Blocks carrying a suggestion of their own. */
const BLOCK_SUGGESTS = BLOCK_SUGGESTION_KINDS.map((kind) => `[${blockAttribute(kind)}]`).join(', ');

/* --------------------------------------------------------------- comments -- */

/** Wrap the text of a range in an anchor for comment `id`. */
export function addAnchor(root: HTMLElement, range: Range, id: string): HTMLElement[] {
  const doc = root.ownerDocument!;
  const anchors: HTMLElement[] = [];
  for (const node of textNodesInRange(range, root)) {
    if (!node.nodeValue?.length) continue;
    const anchor = doc.createElement('span');
    anchor.setAttribute('data-comment', id);
    node.parentNode?.insertBefore(anchor, node);
    anchor.appendChild(node);
    anchors.push(anchor);
  }
  return anchors;
}

export function anchorsOf(root: ParentNode, id: string): HTMLElement[] {
  return Array.from(root.querySelectorAll<HTMLElement>(`[data-nk-content] [data-comment="${CSS.escape(id)}"]`));
}

export function removeAnchors(root: HTMLElement, id: string): void {
  for (const anchor of anchorsOf(root, id)) {
    const parent = anchor.parentElement;
    unwrap(anchor);
    parent?.normalize();
  }
}

export function quoteOf(root: HTMLElement, id: string): string {
  return anchorsOf(root, id)
    .map((anchor) => anchor.textContent ?? '')
    .join(' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 300);
}

/** The comment and the suggestion the caret is in, if any. */
export function reviewAt(node: Node | null): { comment: string | null; suggestion: string | null } {
  const element = node && (isElement(node) ? node : node.parentElement);
  const block = element?.closest<HTMLElement>(BLOCK_SUGGESTS) ?? null;
  return {
    comment: element?.closest('[data-comment]')?.getAttribute('data-comment') ?? null,
    suggestion: element?.closest(`${SUGGEST}, ${FORMAT}`)?.getAttribute('data-suggest') ?? (block ? blockSuggestions(block)[0]?.suggestion.id ?? null : null),
  };
}

/** Anchors of resolved threads stop being highlighted; anchors with no thread at all go. */
export function decorateAnchors(root: HTMLElement, threads: Thread[], keep: string | null, active: string | null): void {
  const byId = new Map(threads.map((thread) => [thread.id, thread]));
  root.querySelectorAll<HTMLElement>('[data-nk-content] [data-comment]').forEach((anchor) => {
    const id = anchor.getAttribute('data-comment') ?? '';
    const thread = byId.get(id);
    if (!thread && id !== keep) {
      const parent = anchor.parentElement;
      unwrap(anchor);
      parent?.normalize();
      return;
    }
    anchor.toggleAttribute('data-nk-resolved', Boolean(thread?.resolved));
    anchor.toggleAttribute('data-nk-active', id === active);
  });
}

/** Threads in the order their anchors appear; threads whose text is gone come last. */
export function orderOf(root: HTMLElement): Map<string, number> {
  const order = new Map<string, number>();
  let index = 0;
  root.querySelectorAll<HTMLElement>(`[data-nk-content] :is([data-comment], ${SUGGEST}, ${FORMAT}, ${BLOCK_SUGGESTS})`).forEach((element) => {
    if (element.closest('[data-nk-repeat]')) return;
    const ids = element.matches(BLOCK_SUGGESTS)
      ? blockSuggestions(element).map((entry) => entry.suggestion.id)
      : [element.getAttribute('data-comment') ?? element.getAttribute('data-suggest') ?? ''];
    for (const id of ids) if (!order.has(id)) order.set(id, index++);
  });
  return order;
}

/** Take the threads out of a loaded document: the hidden aside goes. */
export function takeThreads(container: ParentNode): Thread[] {
  let threads: Thread[] = [];
  container.querySelectorAll<HTMLElement>('aside[data-comments]').forEach((aside) => {
    try {
      threads = threads.concat(validThreads(JSON.parse(aside.getAttribute('data-comments') ?? '[]')));
    } catch {
      // A damaged store loses its threads, never the document.
    }
    aside.remove();
  });
  return validThreads(threads);
}

/** The threads as the hidden aside saved after the document's HTML. */
export function threadsHtml(doc: Document, threads: Thread[]): string {
  if (!threads.length) return '';
  const aside = doc.createElement('aside');
  aside.setAttribute('data-comments', JSON.stringify(threads));
  aside.hidden = true;
  return aside.outerHTML;
}

/* ------------------------------------------------------------ suggestions -- */

function suggestion(doc: Document, tag: 'ins' | 'del', id: string, author: string, time: string): HTMLElement {
  const element = doc.createElement(tag);
  element.setAttribute('data-suggest', id);
  element.setAttribute('data-author', author);
  element.setAttribute('data-time', time);
  return element;
}

const ownBy = (element: Element | null, tag: 'ins' | 'del', author: string): element is HTMLElement =>
  Boolean(element) && element!.tagName.toLowerCase() === tag && element!.hasAttribute('data-suggest') && element!.getAttribute('data-author') === author;

/** The suggestion element of `tag` holding a node, if any. */
function holder(node: Node, tag: 'ins' | 'del'): HTMLElement | null {
  const element = isElement(node) ? node : node.parentElement;
  return (element?.closest(`${tag}[data-suggest]`) as HTMLElement | null) ?? null;
}

/** The element just before a collapsed caret, skipping empty text. */
function nodeBefore(range: Range): Node | null {
  const { startContainer: container, startOffset: offset } = range;
  if (container.nodeType === Node.TEXT_NODE) {
    if (offset > 0) return null;
    let node: Node | null = container.previousSibling;
    while (node && node.nodeType === Node.TEXT_NODE && !node.nodeValue) node = node.previousSibling;
    return node;
  }
  let node: Node | null = container.childNodes[offset - 1] ?? null;
  while (node && node.nodeType === Node.TEXT_NODE && !node.nodeValue) node = node.previousSibling;
  return node;
}

function placeCaret(doc: Document, node: Node, offset: number): Range {
  const caret = doc.createRange();
  caret.setStart(node, offset);
  caret.collapse(true);
  const selection = doc.defaultView?.getSelection();
  selection?.removeAllRanges();
  selection?.addRange(caret);
  return caret;
}

/**
 * Suggest deleting what a range covers: text becomes a `<del>`, text that was
 * itself suggested by the same author goes for good, text already suggested
 * for deletion stays as it is. Returns where the caret goes.
 */
export function suggestDeletion(root: HTMLElement, range: Range, author: string, direction: 'backward' | 'forward'): Range | null {
  const doc = root.ownerDocument!;
  const nodes = textNodesInRange(range, root);
  if (!nodes.length) return null;

  const time = new Date().toISOString();
  const id = newSuggestionId();
  let first: Node | null = null;
  let last: Node | null = null;

  for (const node of nodes) {
    const inserted = holder(node, 'ins');
    if (inserted && inserted.getAttribute('data-author') === author) {
      // Taking back one's own suggestion is just deleting it.
      const marker = doc.createTextNode('');
      node.parentNode?.insertBefore(marker, node);
      node.remove();
      first ??= marker;
      last = marker;
      if (!(inserted.textContent ?? '').length) {
        const spot = doc.createTextNode('');
        inserted.replaceWith(spot);
        if (first === marker) first = spot;
        last = spot;
      }
      continue;
    }
    if (holder(node, 'del')) {
      first ??= node;
      last = node;
      continue;
    }
    const wrapper = suggestion(doc, 'del', id, author, time);
    node.parentNode?.insertBefore(wrapper, node);
    wrapper.appendChild(node);
    first ??= wrapper;
    last = wrapper;
  }

  const block = closestBlock(first ?? range.startContainer, root);
  if (block) tidy(block, author);

  const target = direction === 'backward' ? first : last;
  if (!target || !target.parentNode) return null;
  const parent = target.parentNode;
  const index = Array.from(parent.childNodes).indexOf(target as ChildNode);
  return placeCaret(doc, parent, direction === 'backward' ? index : index + 1);
}

/** Whether anything visible follows a point in its block. */
function textAfter(root: HTMLElement, node: Node, offset: number): boolean {
  const block = closestBlock(node, root);
  if (!block) return false;
  const rest = node.ownerDocument!.createRange();
  rest.setStart(node, offset);
  rest.setEnd(block, block.childNodes.length);
  return rest.toString().length > 0;
}

/**
 * Put text into a text node the way a browser types it: a space with nothing
 * visible after it, or after another space, would collapse, so it is written
 * as a non-breaking one — and turns back into a plain space once text follows.
 */
function typeInto(root: HTMLElement, node: Text, offset: number, text: string): number {
  if (offset > 0 && node.data.charAt(offset - 1) === '\u00a0' && !text.startsWith(' ')) {
    node.replaceData(offset - 1, 1, ' ');
  }
  let typed = text;
  const before = offset > 0 ? node.data.charAt(offset - 1) : '';
  if (typed.startsWith(' ') && (offset === 0 || before === ' ' || before === '\u00a0')) typed = `\u00a0${typed.slice(1)}`;
  if (typed.endsWith(' ') && !textAfter(root, node, offset)) typed = `${typed.slice(0, -1)}\u00a0`;
  node.insertData(offset, typed);
  return offset + typed.length;
}

/**
 * Suggest typing `text` at the caret: it goes into the author's suggested
 * insertion the caret is in or just after, or a new one.
 */
export function suggestInsertion(root: HTMLElement, range: Range, text: string, author: string): void {
  const doc = root.ownerDocument!;
  if (!range.collapsed) {
    const caret = suggestDeletion(root, range, author, 'forward');
    if (!caret) return;
    range = caret;
  }

  // Never type inside text suggested for deletion: step out of it.
  const deleted = holder(range.startContainer, 'del');
  if (deleted) {
    const parent = deleted.parentNode!;
    const index = Array.from(parent.childNodes).indexOf(deleted);
    const atStart = range.startOffset === 0 && (range.startContainer === deleted.firstChild || range.startContainer === deleted);
    range = placeCaret(doc, parent, atStart ? index : index + 1);
  }

  let target = holder(range.startContainer, 'ins');
  if (target && target.getAttribute('data-author') !== author) target = null;

  if (target) {
    if (range.startContainer.nodeType === Node.TEXT_NODE) {
      const node = range.startContainer as Text;
      placeCaret(doc, node, typeInto(root, node, range.startOffset, text));
    } else {
      const node = doc.createTextNode('');
      range.insertNode(node);
      placeCaret(doc, node, typeInto(root, node, 0, text));
    }
    return;
  }

  // Right after one's own insertion: carry on in it.
  const before = nodeBefore(range);
  if (isElement(before) && ownBy(before, 'ins', author)) {
    let tail = before.lastChild;
    if (!tail || tail.nodeType !== Node.TEXT_NODE) tail = before.appendChild(doc.createTextNode(''));
    placeCaret(doc, tail, typeInto(root, tail as Text, (tail as Text).length, text));
    return;
  }

  const inserted = suggestion(doc, 'ins', newSuggestionId(), author, new Date().toISOString());
  const node = doc.createTextNode('');
  inserted.appendChild(node);
  range.insertNode(inserted);
  const end = typeInto(root, node, 0, text);
  const block = closestBlock(inserted, root);
  if (block) tidy(block, author);
  placeCaret(doc, node, end);
}

/**
 * Join an author's neighbouring suggestions: two deletions (or insertions) side
 * by side become one, and a deletion next to an insertion become one
 * replacement, sharing an id.
 */
export function tidy(block: HTMLElement, author: string): void {
  const elements = Array.from(block.querySelectorAll<HTMLElement>(SUGGEST));
  for (const element of elements) {
    if (!element.isConnected || element.getAttribute('data-author') !== author) continue;
    let next = element.nextSibling;
    while (next && next.nodeType === Node.TEXT_NODE && !next.nodeValue) next = next.nextSibling;
    if (!isElement(next) || !next.matches(SUGGEST) || next.getAttribute('data-author') !== author) continue;

    if (next.tagName === element.tagName) {
      while (next.firstChild) element.appendChild(next.firstChild);
      next.remove();
    } else {
      next.setAttribute('data-suggest', element.getAttribute('data-suggest')!);
      next.setAttribute('data-time', element.getAttribute('data-time')!);
    }
  }
}

/** Every suggestion, in document order, with what it adds, takes away or changes. */
export function suggestionsIn(root: HTMLElement): Suggestion[] {
  const found = new Map<string, Suggestion>();
  const entry = (id: string, author: string, time: string, kind: Suggestion['kind']): Suggestion => {
    let existing = found.get(id);
    if (!existing) {
      existing = { id, author, time, kind, inserted: '', deleted: '' };
      found.set(id, existing);
    }
    return existing;
  };

  root.querySelectorAll<HTMLElement>(`[data-nk-content] :is(${SUGGEST}, ${FORMAT}, ${BLOCK_SUGGESTS})`).forEach((element) => {
    if (element.closest('[data-nk-repeat]')) return;

    if (element.matches(BLOCK_SUGGESTS)) {
      // A block a page cut speaks once, from its first part.
      if (Number(element.getAttribute(PART_ATTR) ?? 0) > 0) return;
      for (const { kind, suggestion } of blockSuggestions(element)) {
        const item = entry(suggestion.id, suggestion.author, suggestion.time, kind);
        item.inserted = (element.textContent ?? '').trim().slice(0, 120);
        if (kind === 'style') {
          item.before = suggestion.before;
          item.look = lookOf(element);
        }
      }
      return;
    }

    const id = element.getAttribute('data-suggest') ?? '';
    const author = element.getAttribute('data-author') ?? '';
    const time = element.getAttribute('data-time') ?? '';
    if (element.matches(FORMAT)) {
      const item = entry(id, author, time, 'format');
      item.inserted += element.textContent?.replace(new RegExp(ZWSP, 'g'), '') ?? '';
      item.changes ??= parseFormat(element.getAttribute('data-format'));
      return;
    }
    const item = entry(id, author, time, 'insert');
    if (element.tagName.toLowerCase() === 'ins') item.inserted += element.textContent ?? '';
    else item.deleted += element.textContent ?? '';
  });

  for (const item of found.values()) {
    if (item.kind === 'insert' || item.kind === 'delete' || item.kind === 'replace') {
      item.kind = item.inserted && item.deleted ? 'replace' : item.deleted ? 'delete' : 'insert';
    }
  }
  return [...found.values()];
}

/** Keep (accept) or undo (reject) a suggestion. */
export function settle(root: HTMLElement, id: string, accept: boolean): void {
  const elements = Array.from(root.querySelectorAll<HTMLElement>(`[data-nk-content] [data-suggest="${CSS.escape(id)}"]`));
  const blocks = new Set<HTMLElement>();
  for (const element of elements) {
    const block = closestBlock(element, root);
    if (block) blocks.add(block);
    if (element.matches(FORMAT)) {
      if (!accept) undoFormat(root, element);
      unwrap(element);
      continue;
    }
    const keep = (element.tagName.toLowerCase() === 'ins') === accept;
    if (keep) unwrap(element);
    else element.remove();
  }
  for (const block of blocks) {
    if (!block.isConnected) continue;
    normalizeInline(block);
    if (!(block.textContent ?? '').length && !block.querySelector('br, img')) block.appendChild(root.ownerDocument!.createElement('br'));
  }

  settleBlocks(root, id, accept);
}

/* ------------------------------------------------------ formatting -- */

/**
 * Suggest a formatting change over a range. `apply` makes it — the editor's
 * own mark command — and each piece of text it changed is wrapped with what it
 * had before, mark by mark, so rejecting puts it back. Changing the same text
 * again adds to its suggestion; changing it back takes the suggestion away.
 */
export function suggestFormat(
  root: HTMLElement,
  range: Range,
  mark: MarkName,
  styled: boolean,
  apply: () => void,
  author: string
): void {
  const doc = root.ownerDocument!;
  const nodes = textNodesInRange(range, root).filter((node) => (node.nodeValue ?? '').replace(new RegExp(ZWSP, 'g'), '').length > 0);
  const read = (node: Text): string | boolean | null => {
    const probe = doc.createRange();
    probe.selectNodeContents(node);
    return styled ? markValue(root, probe, mark) : hasMark(root, probe, mark);
  };
  const before = nodes.map(read);
  apply();

  const id = newSuggestionId();
  const time = new Date().toISOString();
  nodes.forEach((node, index) => {
    if (!node.isConnected) return;
    const after = read(node);
    if (sameValue(after, before[index])) return;

    // The text already carries this author's suggestion, around it alone: add to it.
    const own = node.parentElement?.closest<HTMLElement>(FORMAT) ?? null;
    if (own && own.getAttribute('data-author') === author && own.textContent === node.nodeValue) {
      const changes = addChange(parseFormat(own.getAttribute('data-format')), { mark, after, before: before[index] });
      if (changes.length) own.setAttribute('data-format', JSON.stringify(changes));
      else unwrap(own);
      return;
    }

    const wrapper = doc.createElement('span');
    wrapper.setAttribute('data-suggest', id);
    wrapper.setAttribute('data-author', author);
    wrapper.setAttribute('data-time', time);
    wrapper.setAttribute('data-format', JSON.stringify([{ mark, after, before: before[index] }]));
    node.parentNode?.insertBefore(wrapper, node);
    wrapper.appendChild(node);
  });

  if (nodes[0]?.isConnected) {
    range.setStart(nodes[0], 0);
    const last = nodes[nodes.length - 1];
    if (last.isConnected) range.setEnd(last, last.length);
  }
}

/** Put a text's formatting back the way it was before its suggestion. */
function undoFormat(root: HTMLElement, element: HTMLElement): void {
  const doc = root.ownerDocument!;
  for (const change of parseFormat(element.getAttribute('data-format')).reverse()) {
    const range = doc.createRange();
    range.selectNodeContents(element);
    const name = change.mark as MarkName;
    if (change.before === true) applyMark(root, range, name);
    else if (typeof change.before === 'string' && change.before) applyMark(root, range, name, change.before);
    else removeMark(root, range, name);
  }
}

/* ---------------------------------------------------------- blocks -- */

/** How a block looks: its paragraph style, alignment, line spacing and indent. */
export function lookOf(block: HTMLElement): BlockLook {
  return {
    name: blockNameOf(block) ?? 'paragraph',
    align: block.getAttribute('data-nk-align') ?? '',
    spacing: block.getAttribute('data-nk-spacing') ?? '',
    indent: block.getAttribute('data-nk-indent') ?? '',
  };
}

/** The suggestions a block carries, by kind. */
export function blockSuggestions(block: Element): Array<{ kind: BlockSuggestionKind; suggestion: BlockSuggestion }> {
  const found: Array<{ kind: BlockSuggestionKind; suggestion: BlockSuggestion }> = [];
  for (const kind of BLOCK_SUGGESTION_KINDS) {
    const suggestion = parseBlockSuggestion(block.getAttribute(blockAttribute(kind)), kind);
    if (suggestion) found.push({ kind, suggestion });
  }
  return found;
}

function markBlock(block: HTMLElement, kind: BlockSuggestionKind, suggestion: BlockSuggestion | null): void {
  // Every part of a block a page cut carries it, so it survives them joining back.
  const id = block.getAttribute(ID_ATTR);
  const parts = id && block.parentElement ? Array.from(block.ownerDocument!.querySelectorAll<HTMLElement>(`[${ID_ATTR}="${CSS.escape(id)}"]`)) : [block];
  for (const part of parts.length ? parts : [block]) {
    if (suggestion) part.setAttribute(blockAttribute(kind), writeBlockSuggestion(suggestion));
    else part.removeAttribute(blockAttribute(kind));
  }
}

/**
 * Suggest a change to blocks' look (paragraph style, alignment, spacing,
 * indent): `apply` makes it, each block it changed keeps how it looked before
 * — the first time only, so a second change still goes back to the start —
 * and a block changed back loses its suggestion.
 */
export function suggestStyle(root: HTMLElement, blocks: HTMLElement[], apply: () => void, author: string): void {
  const before = blocks.map((block) => ({ id: block.getAttribute(ID_ATTR), look: lookOf(block), pending: parseBlockSuggestion(block.getAttribute(blockAttribute('style')), 'style') }));
  apply();

  const suggestionId = newSuggestionId();
  const time = new Date().toISOString();
  for (const { id, look, pending } of before) {
    const block = id ? root.querySelector<HTMLElement>(`[data-nk-content] [${ID_ATTR}="${CSS.escape(id)}"]`) : null;
    if (!block) continue;
    const now = lookOf(block);
    if (sameLook(now, look)) continue;

    const first = pending && pending.author === author ? pending.before! : look;
    if (sameLook(now, first)) markBlock(block, 'style', null);
    else markBlock(block, 'style', pending && pending.author === author ? pending : { id: suggestionId, author, time, before: first });
  }
}

/** Suggest that `block` starts a new paragraph (Enter): it stays apart until accepted. */
export function suggestSplit(block: HTMLElement, author: string): void {
  markBlock(block, 'split', { id: newSuggestionId(), author, time: new Date().toISOString() });
}

/**
 * Suggest joining `block` to the one before it (Backspace at its start, or
 * Delete at the end of the one before). One's own suggested paragraph is just
 * taken back: the two are joined at once. Returns true when they were joined.
 */
export function suggestJoin(root: HTMLElement, block: HTMLElement, author: string): boolean {
  const split = parseBlockSuggestion(block.getAttribute(blockAttribute('split')), 'split');
  if (split && split.author === author) {
    markBlock(block, 'split', null);
    joinToPrevious(root, block);
    return true;
  }
  if (!block.hasAttribute(blockAttribute('join'))) {
    markBlock(block, 'join', { id: newSuggestionId(), author, time: new Date().toISOString() });
  }
  return false;
}

/** The block before `block` in reading order, across pages; null at the start. */
export function previousBlock(root: HTMLElement, block: HTMLElement): HTMLElement | null {
  const previous = block.previousElementSibling as HTMLElement | null;
  if (previous) return previous;
  if (!block.parentElement?.matches('[data-nk-content]')) return null;
  const bodies = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-page] > [data-nk-content]'));
  const at = bodies.indexOf(block.parentElement);
  for (let index = at - 1; index >= 0; index--) {
    const last = bodies[index].lastElementChild as HTMLElement | null;
    if (last) return last;
  }
  return null;
}

/** Move a block's content to the end of the one before it, if that one holds text. */
function joinToPrevious(root: HTMLElement, block: HTMLElement): HTMLElement | null {
  const previous = previousBlock(root, block);
  if (!previous || previous.matches('table, figure, hr, nav, ul, ol, [data-nk-break]')) return null;
  // Whatever stands only to keep an empty line open goes.
  const lastChild = previous.lastChild;
  if (lastChild && isElement(lastChild) && lastChild.tagName === 'BR' && !lastChild.nextSibling) lastChild.remove();
  const firstChild = block.firstChild;
  if (firstChild && isElement(firstChild) && firstChild.tagName === 'BR' && !firstChild.nextSibling) firstChild.remove();
  while (block.firstChild) previous.appendChild(block.firstChild);
  block.remove();
  normalizeInline(previous);
  if (!(previous.textContent ?? '').length && !previous.querySelector('br, img')) previous.appendChild(root.ownerDocument!.createElement('br'));
  return previous;
}

/** Accept or reject the block suggestions with this id. */
function settleBlocks(root: HTMLElement, id: string, accept: boolean): void {
  const doc = root.ownerDocument!;
  for (const kind of BLOCK_SUGGESTION_KINDS) {
    const attribute = blockAttribute(kind);
    const marked = Array.from(root.querySelectorAll<HTMLElement>(`[data-nk-content] [${attribute}]`)).filter(
      (block) => parseBlockSuggestion(block.getAttribute(attribute), kind)?.id === id
    );
    for (const block of marked) {
      if (!block.isConnected) continue;
      const suggestion = parseBlockSuggestion(block.getAttribute(attribute), kind);
      markBlock(block, kind, null);
      if (kind === 'split' && !accept) joinToPrevious(root, block);
      if (kind === 'join' && accept) joinToPrevious(root, block);
      if (kind === 'style' && !accept && suggestion?.before) {
        const look = suggestion.before;
        let target = block;
        if ((blockNameOf(block) ?? 'paragraph') !== look.name) {
          const range = doc.createRange();
          range.selectNodeContents(block);
          range.collapse(true);
          const blockId = block.getAttribute(ID_ATTR);
          setBlockType(root, range, look.name as BlockName);
          target = (blockId && root.querySelector<HTMLElement>(`[data-nk-content] [${ID_ATTR}="${CSS.escape(blockId)}"]`)) || block;
        }
        for (const [attr, value] of [['data-nk-align', look.align], ['data-nk-spacing', look.spacing], ['data-nk-indent', look.indent]] as const) {
          if (value) target.setAttribute(attr, value);
          else target.removeAttribute(attr);
        }
      }
    }
  }
}

/**
 * Text an input method composed (accents, Asian scripts) is typed by the
 * browser, past any interception: once it lands, it becomes a suggestion too.
 */
export function markComposed(root: HTMLElement, text: string, author: string): void {
  const selection = root.ownerDocument!.defaultView?.getSelection();
  if (!text || !selection?.rangeCount) return;
  const caret = selection.getRangeAt(0);
  const node = caret.startContainer;
  if (!caret.collapsed || node.nodeType !== Node.TEXT_NODE || caret.startOffset < text.length) return;

  const own = holder(node, 'ins');
  if (own && own.getAttribute('data-author') === author) return;

  const doc = root.ownerDocument!;
  const range = doc.createRange();
  range.setStart(node, caret.startOffset - text.length);
  range.setEnd(node, caret.startOffset);
  if (range.toString() !== text) return;

  const inserted = suggestion(doc, 'ins', newSuggestionId(), author, new Date().toISOString());
  range.surroundContents(inserted);
  const block = closestBlock(inserted, root);
  if (block) tidy(block, author);
  placeCaret(doc, inserted, inserted.childNodes.length);
}

/** The comment's anchors and the suggestion's text stand out while their card is open. */
export function markActive(root: HTMLElement, id: string | null): void {
  root.querySelectorAll<HTMLElement>('[data-nk-content] [data-nk-active]').forEach((element) => {
    const own = element.getAttribute('data-comment') ?? element.getAttribute('data-suggest');
    const ids = own ? [own] : blockSuggestions(element).map((entry) => entry.suggestion.id);
    if (!id || !ids.includes(id)) element.removeAttribute('data-nk-active');
  });
  if (!id) return;
  root
    .querySelectorAll<HTMLElement>(`[data-nk-content] :is([data-comment="${CSS.escape(id)}"], [data-suggest="${CSS.escape(id)}"])`)
    .forEach((element) => element.setAttribute('data-nk-active', ''));
  root.querySelectorAll<HTMLElement>(`[data-nk-content] :is(${BLOCK_SUGGESTS})`).forEach((block) => {
    if (blockSuggestions(block).some((entry) => entry.suggestion.id === id)) block.setAttribute('data-nk-active', '');
  });
}

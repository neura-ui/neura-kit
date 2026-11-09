/**
 * Block-level commands: paragraph styles, lists, indentation, alignment and
 * the insertable objects (rules, images, tables, page breaks).
 *
 * A "block" here is a leaf that holds text — `<p>`, `<h1>`, `<li>`, `<td>`.
 * Lists and tables are containers around those leaves, never blocks themselves.
 */

import {
  blocksInRange,
  closestBlock,
  el,
  emptyParagraph,
  isElement,
  isEmptyBlock,
  tag,
  unwrap,
} from './dom';
import { BLOCKS, type Alignment, type BlockName } from './schema';
import { blockId, blockParts, PART_ATTR, setCaret } from './selection';

/**
 * Blocks that hold running text and take a paragraph style or a list. Table
 * cells and images are blocks too, but turning one into a heading or a list
 * item would take it out of its table or its figure.
 */
const TEXT_BLOCKS = new Set(['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'li']);
const isTextBlock = (block: HTMLElement): boolean => TEXT_BLOCKS.has(tag(block)) && !block.closest('figure');

/** Attributes that survive a block being retyped. */
const CARRIED_ATTRS = ['data-nk-id', 'data-nk-align', 'data-nk-indent', 'data-nk-spacing', 'style', 'data-suggest-split', 'data-suggest-join', 'data-suggest-style'];

/** Which schema block an element currently represents. */
export function blockNameOf(element: HTMLElement): BlockName | null {
  const name = tag(element);
  const variant = element.getAttribute('data-nk-style');

  for (const [key, spec] of Object.entries(BLOCKS) as [BlockName, (typeof BLOCKS)[BlockName]][]) {
    if (spec.tag !== name) continue;
    if ((spec.variant ?? null) === (variant ?? null)) return key;
  }

  return name === 'li' ? 'paragraph' : null;
}

/** The block style at the caret, or null when the selection spans several. */
export function currentBlockName(root: HTMLElement, range: Range): BlockName | null {
  const blocks = blocksInRange(range, root);
  if (blocks.length === 0) return null;

  const first = blockNameOf(blocks[0]);
  return blocks.every((block) => blockNameOf(block) === first) ? first : null;
}

/** Replace an element's tag while keeping its children and carried attributes. */
function retag(element: HTMLElement, name: BlockName): HTMLElement {
  const spec = BLOCKS[name];
  const doc = element.ownerDocument!;
  const replacement = doc.createElement(spec.tag);

  for (const attr of CARRIED_ATTRS) {
    const value = element.getAttribute(attr);
    if (value !== null) replacement.setAttribute(attr, value);
  }

  if (spec.variant) replacement.setAttribute('data-nk-style', spec.variant);

  while (element.firstChild) replacement.appendChild(element.firstChild);
  element.parentNode?.replaceChild(replacement, element);

  return replacement;
}

/** Apply a paragraph style to every block the selection touches. */
export function setBlockType(root: HTMLElement, range: Range, name: BlockName): void {
  for (const block of blocksInRange(range, root)) {
    // List items keep their `<li>` shell — Docs styles the text, not the bullet.
    if (tag(block) === 'li' || !isTextBlock(block)) continue;
    if (blockNameOf(block) === name) continue;
    retag(block, name);
  }
}

/** The list container (`ul`/`ol`) a block sits in, if any. */
function listOf(block: HTMLElement, root: HTMLElement): HTMLElement | null {
  let current: Node | null = block.parentNode;
  while (current && current !== root) {
    if (isElement(current) && (tag(current) === 'ul' || tag(current) === 'ol')) return current;
    current = current.parentNode;
  }
  return null;
}

const CHECKLIST_ATTR = 'data-checklist';
const CHECKED_ATTR = 'data-checked';

/** Whether a list is a checklist: a bulleted list whose items are ticked or not. */
export function isChecklist(list: Element | null): boolean {
  return !!list && tag(list) === 'ul' && list.hasAttribute(CHECKLIST_ATTR);
}

/** Make a list a plain list again, dropping the ticks. */
function unmarkChecklist(list: HTMLElement): void {
  list.removeAttribute(CHECKLIST_ATTR);
  for (const item of Array.from(list.children)) item.removeAttribute(CHECKED_ATTR);
}

/** Whether the whole selection already sits in a list of `listTag` (a checklist is not a bulleted list). */
export function inList(root: HTMLElement, range: Range, listTag: 'ul' | 'ol'): boolean {
  const blocks = blocksInRange(range, root);
  if (blocks.length === 0) return false;
  return blocks.every((block) => {
    const list = listOf(block, root);
    return !!list && tag(list) === listTag && !isChecklist(list);
  });
}

/**
 * How a numbered list counts, level by level: `decimal` (1. a. i., the
 * default), `paren` (1) a) i)), `outline` (1. 1.1. 1.1.1.), or letters and
 * roman numerals first.
 */
export const NUMBERINGS = ['decimal', 'paren', 'outline', 'upper-alpha', 'lower-alpha', 'upper-roman', 'lower-roman'] as const;
export type Numbering = (typeof NUMBERINGS)[number];

/** The outermost numbered list around a block, where its numbering is set. */
function outerList(block: HTMLElement, root: HTMLElement): HTMLElement | null {
  let list = listOf(block, root);
  let outer: HTMLElement | null = null;
  while (list && tag(list) === 'ol') {
    outer = list;
    const item = list.parentElement;
    list = item && tag(item) === 'li' ? listOf(item, root) : null;
  }
  return outer;
}

/** The numbering of the list around the caret, or null outside a numbered list. */
export function numberingAt(root: HTMLElement, range: Range): Numbering | null {
  const block = blocksInRange(range, root)[0];
  const list = block ? outerList(block, root) : null;
  if (!list) return null;
  const numbering = list.getAttribute('data-numbering') as Numbering | null;
  return numbering && NUMBERINGS.includes(numbering) ? numbering : 'decimal';
}

/**
 * Number the selection's lists one way — every part of a list a page cut. Not
 * in a numbered list yet, the selection becomes one.
 */
export function setNumbering(root: HTMLElement, range: Range, numbering: Numbering): void {
  if (!inList(root, range, 'ol')) toggleList(root, range, 'ol');
  const lists = new Set<HTMLElement>();
  for (const block of blocksInRange(range, root)) {
    const list = outerList(block, root);
    if (!list) continue;
    const id = list.getAttribute('data-nk-id');
    for (const part of id ? blockParts(root, id) : [list]) lists.add(part);
  }
  for (const list of lists) {
    if (numbering === 'decimal') list.removeAttribute('data-numbering');
    else list.setAttribute('data-numbering', numbering);
  }
}

/** Whether the whole selection sits in checklists. */
export function inChecklist(root: HTMLElement, range: Range): boolean {
  const blocks = blocksInRange(range, root);
  return blocks.length > 0 && blocks.every((block) => isChecklist(listOf(block, root)));
}

/**
 * Turn the selected blocks into a checklist, or back into paragraphs when they
 * already are one. A bulleted or numbered list becomes a checklist in place.
 */
export function toggleChecklist(root: HTMLElement, range: Range, checked = false): void {
  const blocks = blocksInRange(range, root);
  if (blocks.length === 0) return;

  if (inChecklist(root, range)) {
    for (const block of blocks) listOf(block, root)?.removeAttribute(CHECKLIST_ATTR);
    for (const block of blocks) block.removeAttribute(CHECKED_ATTR);
    liftFromList(root, blocks);
    return;
  }

  const inBullets = blocks.every((block) => {
    const list = listOf(block, root);
    return !!list && tag(list) === 'ul';
  });
  // Building the list replaces the blocks, which can leave the range behind; the new
  // items keep the blocks' ids, so they are found by those.
  const ids = blocks.map((block) => blockId(block));
  if (!inBullets) toggleList(root, range, 'ul');

  const items = ids
    .map((id) => root.querySelector<HTMLElement>(`[data-nk-id="${CSS.escape(id)}"]`))
    .filter((item): item is HTMLElement => !!item);

  for (const block of items) {
    const list = listOf(block, root);
    if (!list || tag(list) !== 'ul') continue;
    list.setAttribute(CHECKLIST_ATTR, '');
    for (const item of Array.from(list.children)) {
      if (tag(item) === 'li' && !item.hasAttribute(CHECKED_ATTR)) item.setAttribute(CHECKED_ATTR, 'false');
    }
    if (checked && tag(block) === 'li') block.setAttribute(CHECKED_ATTR, 'true');
  }
}

/** Tick or untick a checklist item, every page part of it included. */
export function toggleChecked(root: HTMLElement, item: HTMLElement): void {
  const next = item.getAttribute(CHECKED_ATTR) === 'true' ? 'false' : 'true';
  const id = item.getAttribute('data-nk-id');
  const parts = id ? blockParts(root, id) : [item];
  for (const part of parts.length ? parts : [item]) part.setAttribute(CHECKED_ATTR, next);
}

/**
 * Turn the selected blocks into a list, or back into paragraphs when they are
 * already that kind of list. Consecutive blocks join one list.
 */
export function toggleList(root: HTMLElement, range: Range, listTag: 'ul' | 'ol'): void {
  const blocks = blocksInRange(range, root).filter(isTextBlock);
  if (blocks.length === 0) return;

  if (inList(root, range, listTag)) {
    liftFromList(root, blocks);
    return;
  }

  const doc = root.ownerDocument!;
  let list: HTMLElement | null = null;
  let previous: HTMLElement | null = null;

  for (const block of blocks) {
    const existing = listOf(block, root);

    // A checklist asked to be a bulleted list loses its ticks.
    if (existing && listTag === 'ul' && isChecklist(existing)) {
      unmarkChecklist(existing);
      list = existing;
      previous = block;
      continue;
    }

    // Already a list, wrong kind — retag the container in place.
    if (existing && tag(existing) !== listTag) {
      unmarkChecklist(existing);
      const replacement = doc.createElement(listTag);
      replacement.setAttribute('data-nk-id', existing.getAttribute('data-nk-id') ?? blockId(existing));
      while (existing.firstChild) replacement.appendChild(existing.firstChild);
      existing.parentNode?.replaceChild(replacement, existing);
      list = replacement;
      previous = block;
      continue;
    }
    if (existing) continue;

    const item = doc.createElement('li');
    const id = block.getAttribute('data-nk-id');
    if (id) item.setAttribute('data-nk-id', id);
    const align = block.getAttribute('data-nk-align');
    if (align) item.setAttribute('data-nk-align', align);
    while (block.firstChild) item.appendChild(block.firstChild);

    // Extend the list built for the previous block when they were adjacent.
    if (list && previous && previous.nextSibling === block) {
      list.appendChild(item);
      block.parentNode?.removeChild(block);
    } else {
      list = doc.createElement(listTag);
      blockId(list);
      list.appendChild(item);
      block.parentNode?.replaceChild(list, block);
    }

    previous = list;
  }
}

/** Unwrap list items back into paragraphs. */
function liftFromList(root: HTMLElement, blocks: HTMLElement[]): void {
  const doc = root.ownerDocument!;
  const lists = new Set<HTMLElement>();

  for (const block of blocks) {
    if (tag(block) !== 'li') continue;
    const list = listOf(block, root);
    if (list) lists.add(list);

    const paragraph = doc.createElement('p');
    const id = block.getAttribute('data-nk-id');
    if (id) paragraph.setAttribute('data-nk-id', id);
    while (block.firstChild) paragraph.appendChild(block.firstChild);

    list?.parentNode?.insertBefore(paragraph, list);
    block.parentNode?.removeChild(block);
  }

  for (const list of lists) {
    if (!list.querySelector('li')) list.parentNode?.removeChild(list);
  }
}

const MAX_INDENT = 8;

/** Read the indent level a block carries. */
export function indentOf(block: HTMLElement): number {
  return Number(block.getAttribute('data-nk-indent') ?? 0);
}

/**
 * Indent the selection one step.
 *
 * List items nest into a child list so the bullet glyph changes with depth;
 * everything else moves on a numeric level the stylesheet turns into padding.
 */
export function indent(root: HTMLElement, range: Range): void {
  for (const block of blocksInRange(range, root)) {
    if (tag(block) === 'li') {
      nestListItem(block, root);
      continue;
    }
    const level = Math.min(MAX_INDENT, indentOf(block) + 1);
    block.setAttribute('data-nk-indent', String(level));
  }
}

/** Outdent the selection one step. */
export function outdent(root: HTMLElement, range: Range): void {
  for (const block of blocksInRange(range, root)) {
    if (tag(block) === 'li') {
      const parentList = listOf(block, root);
      const grandparent = parentList?.parentNode;
      if (parentList && grandparent && tag(grandparent) === 'li') {
        // Pull the item up one nesting level.
        const outerList = listOf(grandparent as HTMLElement, root);
        outerList?.insertBefore(block, (grandparent as HTMLElement).nextSibling);
        if (!parentList.querySelector('li')) parentList.parentNode?.removeChild(parentList);
        continue;
      }
      liftFromList(root, [block]);
      continue;
    }

    const level = Math.max(0, indentOf(block) - 1);
    if (level === 0) block.removeAttribute('data-nk-indent');
    else block.setAttribute('data-nk-indent', String(level));
  }
}

/** Move a list item into a nested list under its previous sibling. */
function nestListItem(item: HTMLElement, root: HTMLElement): void {
  const previous = item.previousElementSibling as HTMLElement | null;
  if (!previous || tag(previous) !== 'li') return;

  const parentList = listOf(item, root);
  if (!parentList) return;

  const lastChild = previous.lastElementChild;
  const target =
    lastChild && (tag(lastChild) === 'ul' || tag(lastChild) === 'ol')
      ? (lastChild as HTMLElement)
      : (() => {
          const nested = root.ownerDocument!.createElement(tag(parentList));
          previous.appendChild(nested);
          return nested;
        })();

  target.appendChild(item);
}

/** Set paragraph alignment on every block in the selection. */
export function setAlignment(root: HTMLElement, range: Range, align: Alignment): void {
  for (const block of blocksInRange(range, root)) {
    if (align === 'left') block.removeAttribute('data-nk-align');
    else block.setAttribute('data-nk-align', align);
  }
}

/** The alignment shared by the selection, defaulting to left. */
export function currentAlignment(root: HTMLElement, range: Range): Alignment {
  const blocks = blocksInRange(range, root);
  if (blocks.length === 0) return 'left';
  const first = (blocks[0].getAttribute('data-nk-align') as Alignment) ?? 'left';
  return blocks.every(
    (block) => ((block.getAttribute('data-nk-align') as Alignment) ?? 'left') === first
  )
    ? first
    : 'left';
}

/** Set line spacing (a unitless multiplier) on the selected blocks. */
export function setLineSpacing(root: HTMLElement, range: Range, spacing: number): void {
  for (const block of blocksInRange(range, root)) {
    if (spacing === 1.15) block.removeAttribute('data-nk-spacing');
    else block.setAttribute('data-nk-spacing', String(spacing));
  }
}

export function currentLineSpacing(root: HTMLElement, range: Range): number {
  const block = closestBlock(range.startContainer, root);
  return Number(block?.getAttribute('data-nk-spacing') ?? 1.15);
}

/** Insert a node after the block holding the caret, then return it. */
export function insertAfterCaret(root: HTMLElement, range: Range, node: HTMLElement): HTMLElement {
  const block = closestBlock(range.startContainer, root);
  const host = block?.closest('[data-nk-content]') ?? root;
  const anchor = block && block.parentNode === host ? block : host.lastElementChild;

  if (anchor) host.insertBefore(node, anchor.nextSibling);
  else host.appendChild(node);

  blockId(node);
  return node;
}

/**
 * Put an object (an image, a table, a rule, a page break) where the caret is,
 * as a word processor does: in an empty paragraph it goes before it; at the
 * start of a paragraph, before it; at its end, after it, with a new paragraph;
 * in the middle, between its two halves. Returns the block where writing goes
 * on, after the object.
 *
 * In a list or a table the object goes after it, and a paragraph cut across
 * two pages is not cut again: it goes after that paragraph.
 */
export function placeObject(root: HTMLElement, range: Range, node: HTMLElement): HTMLElement {
  const doc = root.ownerDocument!;
  const block = closestBlock(range.startContainer, root);
  const host = block?.closest<HTMLElement>('[data-nk-content]') ?? null;
  blockId(node);

  if (!block || !host || block.parentElement !== host || !isTextBlock(block) || block.hasAttribute(PART_ATTR)) {
    insertAfterCaret(root, range, node);
    const paragraph = emptyParagraph(doc);
    node.after(paragraph);
    blockId(paragraph);
    return paragraph;
  }

  if (isEmptyBlock(block)) {
    block.before(node);
    return block;
  }

  const before = doc.createRange();
  before.selectNodeContents(block);
  before.setEnd(range.startContainer, range.startOffset);
  const at = before.toString().length;

  if (at === 0) {
    block.before(node);
    return block;
  }

  if (at >= (block.textContent ?? '').length) {
    block.after(node);
    const paragraph = emptyParagraph(doc);
    node.after(paragraph);
    blockId(paragraph);
    return paragraph;
  }

  // In the middle: the text after the caret becomes a block of its own, after the object.
  const tail = doc.createRange();
  tail.setStart(range.startContainer, range.startOffset);
  tail.setEnd(block, block.childNodes.length);
  const next = block.cloneNode(false) as HTMLElement;
  next.removeAttribute('data-nk-id');
  next.appendChild(tail.extractContents());
  blockId(next);
  block.after(node);
  node.after(next);
  return next;
}

export function insertHorizontalRule(root: HTMLElement, range: Range): void {
  const doc = root.ownerDocument!;
  setCaret(root, placeObject(root, range, doc.createElement('hr')), 0);
}

/** Insert an image as a figure so it can carry a caption; writing goes on after it. Returns the figure. */
export function insertImage(root: HTMLElement, range: Range, src: string, alt = ''): HTMLElement {
  const doc = root.ownerDocument!;
  const figure = el(doc, 'figure');
  const image = el(doc, 'img', { src, alt });
  figure.appendChild(image);
  setCaret(root, placeObject(root, range, figure), 0);
  return figure;
}

/** Insert a rows × cols table with an empty paragraph in every cell. */
export function insertTable(root: HTMLElement, range: Range, rows: number, cols: number): void {
  const doc = root.ownerDocument!;
  const table = el(doc, 'table');
  const body = el(doc, 'tbody');

  for (let r = 0; r < rows; r += 1) {
    const row = el(doc, 'tr');
    for (let c = 0; c < cols; c += 1) {
      const cell = el(doc, r === 0 ? 'th' : 'td');
      cell.appendChild(doc.createElement('br'));
      blockId(cell);
      row.appendChild(cell);
    }
    body.appendChild(row);
  }

  table.appendChild(body);
  placeObject(root, range, table);

  // Writing goes on in the first cell.
  const first = table.querySelector<HTMLElement>('th, td');
  if (first) setCaret(root, first, 0);
}

/* ------------------------------------------------------------------ tables -- */

// Row, column and cell commands work on a span-aware model; see tables.ts.
export {
  deleteTable,
  deleteTableColumn,
  deleteTableRow,
  insertTableColumn,
  insertTableRow,
  moveToAdjacentCell,
  tableContext,
  toggleTableHeaderRow,
  type TableContext,
} from './tables';

/**
 * Force everything after the caret onto a fresh sheet.
 *
 * The marker is an empty block the flow engine treats as taller than any
 * remaining space, so the normal overflow pass does the actual page creation.
 */
export type BreakKind = 'page' | 'odd' | 'even' | 'column';

/** A break marker: the next page, or the next odd (right) or even (left) page. */
export function breakMarker(doc: Document, kind: BreakKind): HTMLElement {
  const marker = el(doc, 'div', { 'data-nk-break': kind });
  marker.setAttribute('contenteditable', 'false');
  return marker;
}

export function insertPageBreak(root: HTMLElement, range: Range, kind: BreakKind = 'page'): void {
  setCaret(root, placeObject(root, range, breakMarker(root.ownerDocument!, kind)), 0);
}

/** Drop a block's own formatting, keeping its text. */
export function clearBlockFormatting(root: HTMLElement, range: Range): void {
  for (const block of blocksInRange(range, root)) {
    if (!isTextBlock(block)) continue;
    block.removeAttribute('data-nk-align');
    block.removeAttribute('data-nk-indent');
    block.removeAttribute('data-nk-spacing');
    block.removeAttribute('style');
    if (tag(block) !== 'li' && blockNameOf(block) !== 'paragraph') retag(block, 'paragraph');
  }
}

export { unwrap, listOf };

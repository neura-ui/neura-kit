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
  tag,
  unwrap,
} from './dom';
import { BLOCKS, type Alignment, type BlockName } from './schema';
import { blockId, setCaret } from './selection';

/** Attributes that survive a block being retyped. */
const CARRIED_ATTRS = ['data-nk-id', 'data-nk-align', 'data-nk-indent', 'data-nk-spacing', 'style'];

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
    if (tag(block) === 'li') continue;
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

/** Whether the whole selection already sits in a list of `listTag`. */
export function inList(root: HTMLElement, range: Range, listTag: 'ul' | 'ol'): boolean {
  const blocks = blocksInRange(range, root);
  if (blocks.length === 0) return false;
  return blocks.every((block) => {
    const list = listOf(block, root);
    return !!list && tag(list) === listTag;
  });
}

/**
 * Turn the selected blocks into a list, or back into paragraphs when they are
 * already that kind of list. Consecutive blocks join one list.
 */
export function toggleList(root: HTMLElement, range: Range, listTag: 'ul' | 'ol'): void {
  const blocks = blocksInRange(range, root);
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

    // Already a list, wrong kind — retag the container in place.
    if (existing && tag(existing) !== listTag) {
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
function insertAfterCaret(root: HTMLElement, range: Range, node: HTMLElement): HTMLElement {
  const block = closestBlock(range.startContainer, root);
  const host = block?.closest('[data-nk-content]') ?? root;
  const anchor = block && block.parentNode === host ? block : host.lastElementChild;

  if (anchor) host.insertBefore(node, anchor.nextSibling);
  else host.appendChild(node);

  blockId(node);
  return node;
}

export function insertHorizontalRule(root: HTMLElement, range: Range): void {
  const doc = root.ownerDocument!;
  const rule = insertAfterCaret(root, range, doc.createElement('hr'));
  const paragraph = emptyParagraph(doc);
  rule.parentNode?.insertBefore(paragraph, rule.nextSibling);
  blockId(paragraph);
}

/** Insert an image as a figure so it can carry a caption. */
export function insertImage(root: HTMLElement, range: Range, src: string, alt = ''): void {
  const doc = root.ownerDocument!;
  const figure = el(doc, 'figure');
  const image = el(doc, 'img', { src, alt });
  figure.appendChild(image);
  insertAfterCaret(root, range, figure);

  const paragraph = emptyParagraph(doc);
  figure.parentNode?.insertBefore(paragraph, figure.nextSibling);
  blockId(paragraph);
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
  insertAfterCaret(root, range, table);

  const paragraph = emptyParagraph(doc);
  table.parentNode?.insertBefore(paragraph, table.nextSibling);
  blockId(paragraph);
}

/* ------------------------------------------------------------------ tables -- */

export interface TableContext {
  table: HTMLElement;
  row: HTMLElement;
  cell: HTMLElement;
  /** Index of the caret's row among all rows in the table. */
  rowIndex: number;
  /** Index of the caret's cell within its row. */
  cellIndex: number;
  rows: number;
  columns: number;
}

/** Every `tr` in a table, in visual order across thead/tbody/tfoot. */
function tableRows(table: HTMLElement): HTMLElement[] {
  return Array.from(table.querySelectorAll<HTMLElement>(':scope > tr, :scope > * > tr'));
}

/** Cells of a row, header or body. */
function rowCells(row: HTMLElement): HTMLElement[] {
  return Array.from(row.children).filter((child) =>
    ['td', 'th'].includes(tag(child))
  ) as HTMLElement[];
}

/**
 * Where the caret sits inside a table, or null when it is not in one.
 *
 * This is what every table command works from, and what the toolbar uses to
 * decide whether the row/column actions apply.
 */
export function tableContext(root: HTMLElement, range: Range): TableContext | null {
  const start = range.startContainer;
  const element = start.nodeType === Node.ELEMENT_NODE ? (start as HTMLElement) : start.parentElement;
  const cell = element?.closest<HTMLElement>('td, th');
  if (!cell || !root.contains(cell)) return null;

  const row = cell.closest<HTMLElement>('tr');
  const table = cell.closest<HTMLElement>('table');
  if (!row || !table) return null;

  const rows = tableRows(table);
  const cells = rowCells(row);

  return {
    table,
    row,
    cell,
    rowIndex: rows.indexOf(row),
    cellIndex: cells.indexOf(cell),
    rows: rows.length,
    columns: Math.max(...rows.map((r) => rowCells(r).length), 0),
  };
}

/** An empty cell, ready for the caret. */
function makeCell(doc: Document, headerLike: boolean): HTMLElement {
  const cell = doc.createElement(headerLike ? 'th' : 'td');
  cell.appendChild(doc.createElement('br'));
  blockId(cell);
  return cell;
}

/** Insert a row above or below the one holding the caret. */
export function insertTableRow(root: HTMLElement, range: Range, where: 'above' | 'below'): void {
  const context = tableContext(root, range);
  if (!context) return;

  const doc = root.ownerDocument!;
  const width = rowCells(context.row).length || context.columns;

  const row = doc.createElement('tr');
  for (let i = 0; i < width; i += 1) {
    // A row added above the header keeps the header styling; everything else
    // becomes a body row.
    row.appendChild(makeCell(doc, where === 'above' && context.rowIndex === 0 && tag(context.cell) === 'th'));
  }

  context.row.parentNode?.insertBefore(row, where === 'above' ? context.row : context.row.nextSibling);

  const first = rowCells(row)[0];
  if (first) setCaret(root, first, 0);
}

/** Insert a column left or right of the one holding the caret. */
export function insertTableColumn(root: HTMLElement, range: Range, where: 'left' | 'right'): void {
  const context = tableContext(root, range);
  if (!context) return;

  const doc = root.ownerDocument!;
  const target = context.cellIndex + (where === 'right' ? 1 : 0);

  for (const row of tableRows(context.table)) {
    const cells = rowCells(row);
    // A short row (ragged table from a paste) just gets the cell appended.
    const reference = cells[target] ?? null;
    const cell = makeCell(doc, tag(cells[0] ?? row) === 'th');
    row.insertBefore(cell, reference);
  }

  const cells = rowCells(context.row);
  const landing = cells[target] ?? cells[cells.length - 1];
  if (landing) setCaret(root, landing, 0);
}

/** Delete the row holding the caret; removes the table when it was the last. */
export function deleteTableRow(root: HTMLElement, range: Range): void {
  const context = tableContext(root, range);
  if (!context) return;

  if (context.rows <= 1) return deleteTable(root, range);

  const rows = tableRows(context.table);
  const next = rows[context.rowIndex + 1] ?? rows[context.rowIndex - 1];
  const section = context.row.parentElement;
  context.row.remove();

  // A thead left with no rows would still take part in layout.
  if (section && ['thead', 'tbody', 'tfoot'].includes(tag(section)) && !section.firstElementChild) {
    section.remove();
  }

  const landing = next ? rowCells(next)[Math.min(context.cellIndex, rowCells(next).length - 1)] : null;
  if (landing) setCaret(root, landing, 0);
}

/** Delete the column holding the caret; removes the table when it was the last. */
export function deleteTableColumn(root: HTMLElement, range: Range): void {
  const context = tableContext(root, range);
  if (!context) return;

  if (context.columns <= 1) return deleteTable(root, range);

  for (const row of tableRows(context.table)) {
    rowCells(row)[context.cellIndex]?.remove();
  }

  const cells = rowCells(context.row);
  const landing = cells[Math.min(context.cellIndex, cells.length - 1)];
  if (landing) setCaret(root, landing, 0);
}

/** Remove the whole table, leaving a paragraph where it was. */
export function deleteTable(root: HTMLElement, range: Range): void {
  const context = tableContext(root, range);
  if (!context) return;

  const doc = root.ownerDocument!;
  const paragraph = emptyParagraph(doc);
  blockId(paragraph);

  context.table.parentNode?.replaceChild(paragraph, context.table);
  setCaret(root, paragraph, 0);
}

/** Toggle the first row between header cells and ordinary cells. */
export function toggleTableHeaderRow(root: HTMLElement, range: Range): void {
  const context = tableContext(root, range);
  if (!context) return;

  const first = tableRows(context.table)[0];
  if (!first) return;

  const doc = root.ownerDocument!;
  const makeHeader = tag(rowCells(first)[0] ?? first) !== 'th';

  for (const cell of rowCells(first)) {
    const replacement = doc.createElement(makeHeader ? 'th' : 'td');
    for (const attr of ['data-nk-id', 'colspan', 'rowspan', 'data-nk-align']) {
      const value = cell.getAttribute(attr);
      if (value !== null) replacement.setAttribute(attr, value);
    }
    while (cell.firstChild) replacement.appendChild(cell.firstChild);
    cell.replaceWith(replacement);
  }
}

/**
 * Move the caret to the next or previous cell, adding a row when Tab is pressed
 * in the last cell — the behaviour every word processor has.
 */
export function moveToAdjacentCell(root: HTMLElement, range: Range, forward: boolean): boolean {
  const context = tableContext(root, range);
  if (!context) return false;

  const rows = tableRows(context.table);
  const cells = rowCells(context.row);

  const nextIndex = context.cellIndex + (forward ? 1 : -1);
  if (nextIndex >= 0 && nextIndex < cells.length) {
    setCaret(root, cells[nextIndex], forward ? 0 : (cells[nextIndex].textContent ?? '').length);
    return true;
  }

  const rowStep = forward ? 1 : -1;
  const neighbourRow = rows[context.rowIndex + rowStep];

  if (!neighbourRow) {
    if (!forward) return false;
    insertTableRow(root, range, 'below');
    return true;
  }

  const neighbourCells = rowCells(neighbourRow);
  const landing = forward ? neighbourCells[0] : neighbourCells[neighbourCells.length - 1];
  if (!landing) return false;

  setCaret(root, landing, forward ? 0 : (landing.textContent ?? '').length);
  return true;
}

/**
 * Force everything after the caret onto a fresh sheet.
 *
 * The marker is an empty block the flow engine treats as taller than any
 * remaining space, so the normal overflow pass does the actual page creation.
 */
export function insertPageBreak(root: HTMLElement, range: Range): void {
  const doc = root.ownerDocument!;
  const marker = el(doc, 'div', { 'data-nk-break': 'page' });
  marker.setAttribute('contenteditable', 'false');
  insertAfterCaret(root, range, marker);

  const paragraph = emptyParagraph(doc);
  marker.parentNode?.insertBefore(paragraph, marker.nextSibling);
  blockId(paragraph);
}

/** Drop a block's own formatting, keeping its text. */
export function clearBlockFormatting(root: HTMLElement, range: Range): void {
  for (const block of blocksInRange(range, root)) {
    block.removeAttribute('data-nk-align');
    block.removeAttribute('data-nk-indent');
    block.removeAttribute('data-nk-spacing');
    block.removeAttribute('style');
    if (tag(block) !== 'li' && blockNameOf(block) !== 'paragraph') retag(block, 'paragraph');
  }
}

export { unwrap, listOf };

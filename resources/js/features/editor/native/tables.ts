/**
 * Table commands on the live DOM.
 *
 * The structure is worked out in `tablemodel.ts`: a table is read into a model
 * (rows of cells with their spans), the edit returns a new model, and `apply`
 * puts the DOM in step. Existing cells are moved rather than rebuilt, so their
 * contents and block ids — and with them the caret — survive every edit.
 *
 * Looks that are not structure live on the table as plain attributes: column
 * widths in a `<colgroup>`, the border in `data-border` / `data-border-color`,
 * a cell's fill in its own `background-color`.
 */

import { emptyParagraph, tag } from './dom';
import { blockId, setCaret } from './selection';
import { toPercent } from './sizing';
import {
  breaksBefore,
  deleteColumn as modelDeleteColumn,
  deleteRow as modelDeleteRow,
  idsIn,
  insertColumn as modelInsertColumn,
  insertRow as modelInsertRow,
  layout,
  merge as modelMerge,
  rectOf,
  split as modelSplit,
  type Model,
} from './tablemodel';

export interface TableContext {
  table: HTMLElement;
  row: HTMLElement;
  cell: HTMLElement;
  /** Index of the caret's row among all rows in the table. */
  rowIndex: number;
  /** Index of the caret's cell among the cells of its row (DOM order). */
  cellIndex: number;
  /** The grid column the caret's cell starts in. */
  column: number;
  rows: number;
  columns: number;
  rowspan: number;
  colspan: number;
}

/** The border weights a table can have, in pixels. */
export const BORDER_WIDTHS = { none: 0, thin: 1, medium: 2, thick: 3 } as const;
export type BorderWeight = keyof typeof BORDER_WIDTHS;

const PART = 'data-nk-part';
const REPEAT = 'data-nk-repeat';

/**
 * Every `tr` in a table, in visual order across thead/tbody/tfoot — but not the
 * copy of the header a page repeats at the top of a cut table's next part.
 */
export function tableRows(table: HTMLElement): HTMLElement[] {
  return Array.from(table.querySelectorAll<HTMLElement>(':scope > tr, :scope > * > tr')).filter((row) => !row.hasAttribute(REPEAT));
}

/* ------------------------------------------------ tables across pages -- */

/** Every part of a table cut across pages, in page order — just the table when it is whole. */
export function partsOf(table: HTMLElement): HTMLElement[] {
  const id = table.getAttribute('data-nk-id');
  if (!id || !table.hasAttribute(PART)) return [table];
  const scope: ParentNode = table.closest('.nk-pages') ?? table.ownerDocument!;
  return Array.from(scope.querySelectorAll<HTMLElement>(`table[data-nk-id="${CSS.escape(id)}"]`));
}

/** Put a later part's rows back at the end of the first part; the part goes. */
export function joinTableParts(owner: HTMLElement, part: HTMLElement): void {
  const sections = owner.querySelectorAll<HTMLElement>(':scope > tbody');
  const target = sections[sections.length - 1] ?? owner.appendChild(owner.ownerDocument!.createElement('tbody'));
  for (const row of Array.from(part.querySelectorAll<HTMLElement>(':scope > tr, :scope > * > tr'))) {
    if (!row.hasAttribute(REPEAT)) target.appendChild(row);
  }
  part.remove();
}

/** A table cut across pages, made one again (before its structure is edited). */
export function wholeTable(table: HTMLElement): HTMLElement {
  const [owner, ...rest] = partsOf(table);
  for (const part of rest) joinTableParts(owner, part);
  owner.removeAttribute(PART);
  return owner;
}

const isHeaderRow = (row: HTMLElement): boolean => {
  const cells = rowCells(row);
  return cells.length > 0 && cells.every((cell) => tag(cell) === 'th');
};

/**
 * Cut a table at the foot of a page: the rows that `fit` stay, the rest go to a
 * new part — returned, for the next page — under a copy of the header rows.
 * The cut falls between rows only where no merged cell spans it, and leaves at
 * least one row under the header; when it cannot, null.
 */
export function splitTable(table: HTMLElement, fits: (row: HTMLElement) => boolean): HTMLElement | null {
  const rows = Array.from(table.querySelectorAll<HTMLElement>(':scope > tr, :scope > * > tr'));
  let header = 0;
  while (header < rows.length && isHeaderRow(rows[header])) header += 1;
  let fitting = 0;
  while (fitting < rows.length && fits(rows[fitting])) fitting += 1;

  const model: Model = rows.map((row, r) => ({
    id: `r${r}`,
    cells: rowCells(row).map((cell, c) => ({ id: `${r}.${c}`, rowspan: spanOf(cell, 'rowspan'), colspan: spanOf(cell, 'colspan') })),
  }));
  const cuts = breaksBefore(model).filter((at) => at > header && at <= fitting);
  const cut = cuts[cuts.length - 1];
  if (cut === undefined || cut >= rows.length) return null;

  const doc = table.ownerDocument!;
  const part = table.cloneNode(false) as HTMLElement;
  const columns = table.querySelector(':scope > colgroup');
  if (columns) part.appendChild(columns.cloneNode(true));
  const body = part.appendChild(doc.createElement('tbody'));

  // The header again, as the page's own copy: not editable, never saved.
  for (const row of rows.slice(0, header)) {
    const copy = row.cloneNode(true) as HTMLElement;
    copy.setAttribute(REPEAT, '');
    copy.setAttribute('contenteditable', 'false');
    copy.querySelectorAll('[data-nk-id]').forEach((element) => element.removeAttribute('data-nk-id'));
    body.appendChild(copy);
  }
  for (const row of rows.slice(cut)) body.appendChild(row);

  table.querySelectorAll(':scope > thead, :scope > tbody, :scope > tfoot').forEach((section) => {
    if (!section.querySelector('tr')) section.remove();
  });

  const index = Number(table.getAttribute(PART) ?? 0);
  table.setAttribute(PART, String(index));
  part.setAttribute(PART, String(index + 1));
  return part;
}

/** The caret's table, made whole first when a page cut it, for an edit to its rows or columns. */
function contextForEdit(root: HTMLElement, range: Range): TableContext | null {
  const context = tableContext(root, range);
  if (!context || !context.table.hasAttribute(PART)) return context;
  wholeTable(context.table);
  return contextOf(context.cell);
}

/** Cells of a row, header or body. */
export function rowCells(row: HTMLElement): HTMLElement[] {
  return Array.from(row.children).filter((child) => ['td', 'th'].includes(tag(child))) as HTMLElement[];
}

const spanOf = (cell: HTMLElement, name: 'rowspan' | 'colspan'): number =>
  Math.max(1, parseInt(cell.getAttribute(name) ?? '1', 10) || 1);

interface Read {
  model: Model;
  cells: Map<string, HTMLElement>;
  rows: Map<string, HTMLElement>;
  ids: Map<HTMLElement, string>;
}

/** The table as a model, with the way back from ids to elements. */
function read(table: HTMLElement): Read {
  const cells = new Map<string, HTMLElement>();
  const rows = new Map<string, HTMLElement>();
  const ids = new Map<HTMLElement, string>();

  const model: Model = tableRows(table).map((row, r) => {
    rows.set(`r${r}`, row);
    return {
      id: `r${r}`,
      cells: rowCells(row).map((cell, c) => {
        const id = `c${r}.${c}`;
        cells.set(id, cell);
        ids.set(cell, id);
        return { id, rowspan: spanOf(cell, 'rowspan'), colspan: spanOf(cell, 'colspan'), header: tag(cell) === 'th' };
      }),
    };
  });

  return { model, cells, rows, ids };
}

/** An empty cell, ready for the caret. */
function makeCell(doc: Document, header: boolean): HTMLElement {
  const cell = doc.createElement(header ? 'th' : 'td');
  cell.appendChild(doc.createElement('br'));
  blockId(cell);
  return cell;
}

function setSpan(cell: HTMLElement, name: 'rowspan' | 'colspan', value: number): void {
  if (value > 1) cell.setAttribute(name, String(value));
  else cell.removeAttribute(name);
}

/**
 * Put the DOM in step with a model. Returns every cell element by model id,
 * the new ones included.
 */
function apply(table: HTMLElement, before: Read, next: Model): Map<string, HTMLElement> {
  const doc = table.ownerDocument!;
  const elements = new Map<string, HTMLElement>();
  const keptRows = new Set<string>();
  let previous: HTMLElement | null = null;

  next.forEach((rowModel, index) => {
    let row = before.rows.get(rowModel.id) ?? null;
    if (!row) {
      row = doc.createElement('tr');
      if (previous) previous.after(row);
      else {
        const following = next
          .slice(index + 1)
          .map((candidate) => before.rows.get(candidate.id))
          .find(Boolean);
        if (following) following.before(row);
        else (table.querySelector(':scope > tbody') ?? table.appendChild(doc.createElement('tbody'))).appendChild(row);
      }
    }
    keptRows.add(rowModel.id);

    for (const cellModel of rowModel.cells) {
      const cell = before.cells.get(cellModel.id) ?? makeCell(doc, Boolean(cellModel.header));
      setSpan(cell, 'rowspan', cellModel.rowspan);
      setSpan(cell, 'colspan', cellModel.colspan);
      elements.set(cellModel.id, cell);
      row.appendChild(cell);
    }

    previous = row;
  });

  for (const [id, cell] of before.cells) if (!elements.has(id)) cell.remove();
  for (const [id, row] of before.rows) {
    if (keptRows.has(id)) continue;
    const section = row.parentElement;
    row.remove();
    // A thead left with no rows would still take part in layout.
    if (section && section !== table && !section.querySelector('tr')) section.remove();
  }

  return elements;
}

/** The cell element covering a grid slot, after an edit. */
function cellAt(model: Model, elements: Map<string, HTMLElement>, row: number, col: number): HTMLElement | null {
  const grid = layout(model);
  const r = Math.max(0, Math.min(row, grid.height - 1));
  const c = Math.max(0, Math.min(col, grid.width - 1));
  const id = grid.slots[r]?.[c]?.id ?? grid.slots[r]?.find(Boolean)?.id;
  return id ? (elements.get(id) ?? null) : null;
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
  return cell && root.contains(cell) ? contextOf(cell) : null;
}

export function contextOf(cell: HTMLElement): TableContext | null {
  const row = cell.closest<HTMLElement>('tr');
  const table = cell.closest<HTMLElement>('table');
  if (!row || !table) return null;

  const before = read(table);
  const grid = layout(before.model);
  const origin = grid.origins.get(before.ids.get(cell) ?? '');
  if (!origin) return null;

  return {
    table,
    row,
    cell,
    rowIndex: tableRows(table).indexOf(row),
    cellIndex: rowCells(row).indexOf(cell),
    column: origin.col,
    rows: grid.height,
    columns: grid.width,
    rowspan: origin.rowspan,
    colspan: origin.colspan,
  };
}

/** Insert a row above or below the cell holding the caret. */
export function insertTableRow(root: HTMLElement, range: Range, where: 'above' | 'below'): void {
  const context = contextForEdit(root, range);
  if (!context) return;

  const before = read(context.table);
  const origin = layout(before.model).origins.get(before.ids.get(context.cell)!)!;
  const at = where === 'above' ? origin.row : origin.row + origin.rowspan;
  // A row added above the header keeps the header styling; everything else
  // becomes a body row.
  const header = where === 'above' && origin.row === 0 && tag(context.cell) === 'th';

  const next = modelInsertRow(before.model, at, header);
  const elements = apply(context.table, before, next);
  const landing = cellAt(next, elements, at, origin.col);
  if (landing) setCaret(root, landing, 0);
}

/** Insert a column left or right of the cell holding the caret. */
export function insertTableColumn(root: HTMLElement, range: Range, where: 'left' | 'right'): void {
  const context = contextForEdit(root, range);
  if (!context) return;

  const before = read(context.table);
  const origin = layout(before.model).origins.get(before.ids.get(context.cell)!)!;
  const at = where === 'left' ? origin.col : origin.col + origin.colspan;

  const next = modelInsertColumn(before.model, at);
  const elements = apply(context.table, before, next);
  resizeColumns(context.table, 'insert', at);

  const landing = cellAt(next, elements, origin.row, at);
  setCaret(root, landing && landing.isConnected ? landing : context.cell, 0);
}

/** Delete the row holding the caret; removes the table when it was the last. */
export function deleteTableRow(root: HTMLElement, range: Range): void {
  const context = contextForEdit(root, range);
  if (!context) return;
  if (context.rows <= 1) return deleteTable(root, range);

  const before = read(context.table);
  const next = modelDeleteRow(before.model, context.rowIndex);
  const elements = apply(context.table, before, next);
  const landing = cellAt(next, elements, context.rowIndex, context.column);
  if (landing) setCaret(root, landing, 0);
}

/** Delete the column holding the caret; removes the table when it was the last. */
export function deleteTableColumn(root: HTMLElement, range: Range): void {
  const context = contextForEdit(root, range);
  if (!context) return;
  if (context.columns <= 1) return deleteTable(root, range);

  const before = read(context.table);
  const origin = layout(before.model).origins.get(before.ids.get(context.cell)!)!;
  const next = modelDeleteColumn(before.model, context.column);
  const elements = apply(context.table, before, next);
  resizeColumns(context.table, 'delete', context.column);

  const landing = cellAt(next, elements, origin.row, context.column);
  if (landing) setCaret(root, landing, 0);
}

/** Remove the whole table, leaving a paragraph where it was. */
export function deleteTable(root: HTMLElement, range: Range): void {
  const context = contextForEdit(root, range);
  if (!context) return;

  const paragraph = emptyParagraph(root.ownerDocument!);
  blockId(paragraph);
  context.table.parentNode?.replaceChild(paragraph, context.table);
  setCaret(root, paragraph, 0);
}

/** Toggle the first row between header cells and ordinary cells. */
export function toggleTableHeaderRow(root: HTMLElement, range: Range): void {
  const context = contextForEdit(root, range);
  if (!context) return;

  const first = tableRows(context.table)[0];
  if (!first) return;

  const doc = root.ownerDocument!;
  const makeHeader = tag(rowCells(first)[0] ?? first) !== 'th';

  for (const cell of rowCells(first)) {
    const replacement = doc.createElement(makeHeader ? 'th' : 'td');
    for (const attr of ['data-nk-id', 'colspan', 'rowspan', 'data-nk-align', 'style']) {
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

  // Across every part of a table cut by a page, in reading order.
  const rows = partsOf(context.table).flatMap(tableRows);
  const rowIndex = rows.indexOf(context.row);
  const cells = rowCells(context.row);

  const nextIndex = context.cellIndex + (forward ? 1 : -1);
  if (nextIndex >= 0 && nextIndex < cells.length) {
    setCaret(root, cells[nextIndex], forward ? 0 : (cells[nextIndex].textContent ?? '').length);
    return true;
  }

  // Rows that only hold cells spanning down from above have no cell of their own.
  const step = forward ? 1 : -1;
  for (let r = rowIndex + step; r >= 0 && r < rows.length; r += step) {
    const neighbour = rowCells(rows[r]);
    const landing = forward ? neighbour[0] : neighbour[neighbour.length - 1];
    if (!landing) continue;
    setCaret(root, landing, forward ? 0 : (landing.textContent ?? '').length);
    return true;
  }

  if (!forward) return false;
  insertTableRow(root, range, 'below');
  return true;
}

/* ------------------------------------------------------- merged cells -- */

/** The cells of the rectangle between two cells, merged cells taken whole. */
export function cellsBetween(a: HTMLElement, b: HTMLElement): HTMLElement[] {
  const table = a.closest<HTMLElement>('table');
  if (!table || b.closest('table') !== table) return [a];

  const before = read(table);
  const rect = rectOf(before.model, before.ids.get(a) ?? '', before.ids.get(b) ?? '');
  if (!rect) return [a];
  return idsIn(before.model, rect)
    .map((id) => before.cells.get(id))
    .filter((cell): cell is HTMLElement => Boolean(cell));
}

const isBlankCell = (cell: HTMLElement): boolean =>
  !cell.querySelector('img') && (cell.textContent ?? '').replace(/\u200B/g, '').trim() === '';

/** Merge the rectangle between two cells into one; their text is kept, line by line. */
export function mergeCells(root: HTMLElement, a: HTMLElement, b: HTMLElement): HTMLElement | null {
  const first = a.closest<HTMLElement>('table');
  if (!first || first.getAttribute('data-nk-id') !== b.closest('table')?.getAttribute('data-nk-id')) return null;
  const table = wholeTable(first);
  if (b.closest('table') !== table) return null;

  const before = read(table);
  const result = modelMerge(before.model, before.ids.get(a) ?? '', before.ids.get(b) ?? '');
  if (!result.absorbed.length) return null;

  const kept = before.cells.get(result.kept)!;
  const doc = root.ownerDocument!;
  for (const id of result.absorbed) {
    const cell = before.cells.get(id);
    if (!cell || isBlankCell(cell)) continue;
    if (isBlankCell(kept)) kept.replaceChildren();
    else kept.appendChild(doc.createElement('br'));
    while (cell.firstChild) kept.appendChild(cell.firstChild);
  }

  apply(table, before, result.model);
  setCaret(root, kept, 0);
  return kept;
}

/** Give a merged cell's slots back as cells of their own. */
export function splitCell(root: HTMLElement, cell: HTMLElement): void {
  const found = cell.closest<HTMLElement>('table');
  if (!found) return;
  const table = wholeTable(found);

  const before = read(table);
  const next = modelSplit(before.model, before.ids.get(cell) ?? '');
  if (next === before.model) return;
  apply(table, before, next);
  setCaret(root, cell, 0);
}

/** Empty the cells, keeping the table as it is. */
export function clearCells(cells: HTMLElement[]): void {
  for (const cell of cells) cell.replaceChildren(cell.ownerDocument!.createElement('br'));
}

/** Fill the cells with a colour, or take it off with null. */
export function setCellFill(cells: HTMLElement[], color: string | null): void {
  for (const cell of cells) {
    if (color && color !== 'transparent') cell.style.backgroundColor = color;
    else cell.style.removeProperty('background-color');
    if (!cell.getAttribute('style')) cell.removeAttribute('style');
  }
}

/* ------------------------------------------------------------ borders -- */

export function setTableBorder(table: HTMLElement, weight: BorderWeight | null, color?: string | null): void {
  // Every part of a table a page cut, so the look survives it being joined back.
  for (const part of partsOf(table)) {
    if (weight === null || weight === 'thin') part.removeAttribute('data-border');
    else part.setAttribute('data-border', weight);

    if (color === null) part.removeAttribute('data-border-color');
    else if (color) part.setAttribute('data-border-color', color);

    paintTable(part);
  }
}

/**
 * Show a table's border in the editor.
 *
 * The colour is a free value, which CSS cannot read out of an attribute, so it
 * is handed over as custom properties. They are view only: the saved value
 * keeps the attributes and gets real inline borders instead.
 */
export function paintTable(table: HTMLElement): void {
  const weight = table.getAttribute('data-border') as BorderWeight | null;
  const color = table.getAttribute('data-border-color');

  if (color) table.style.setProperty('--nk-table-border-color', color);
  else table.style.removeProperty('--nk-table-border-color');
  if (weight && weight in BORDER_WIDTHS) table.style.setProperty('--nk-table-border-width', `${BORDER_WIDTHS[weight]}px`);
  else table.style.removeProperty('--nk-table-border-width');
  if (!table.getAttribute('style')) table.removeAttribute('style');
}

export function paintTables(root: HTMLElement): void {
  root.querySelectorAll<HTMLElement>('table').forEach(paintTable);
}

/* ------------------------------------------------------ column widths -- */

/** Column widths in percent, from the table's colgroup, or null when it has none. */
export function columnWidths(table: HTMLElement): number[] | null {
  const cols = Array.from(table.querySelectorAll<HTMLElement>(':scope > colgroup > col'));
  if (!cols.length) return null;
  const widths = cols.map((col) => parseFloat(col.style.width) || 0);
  return widths.every((width) => width > 0) ? widths : null;
}

/** Write widths (any unit, scaled to 100%) as a colgroup, or drop it with null. */
export function setColumnWidths(table: HTMLElement, widths: number[] | null): void {
  for (const part of partsOf(table)) {
    part.querySelectorAll(':scope > colgroup').forEach((group) => group.remove());
    if (!widths || !widths.length) continue;

    const doc = part.ownerDocument!;
    const group = doc.createElement('colgroup');
    for (const width of toPercent(widths)) {
      const col = doc.createElement('col');
      col.style.width = `${width}%`;
      group.appendChild(col);
    }
    part.insertBefore(group, part.firstChild);
  }
}

/** Keep the colgroup in step when a column comes or goes. */
function resizeColumns(table: HTMLElement, change: 'insert' | 'delete', at: number): void {
  const widths = columnWidths(table);
  if (!widths) return;

  if (change === 'insert') widths.splice(at, 0, widths.reduce((sum, width) => sum + width, 0) / widths.length);
  else widths.splice(at, 1);
  setColumnWidths(table, widths.length ? widths : null);
}

/**
 * The on-screen width of every column, in pixels.
 *
 * Read from the cells' edges, so it holds whatever set the widths — a
 * colgroup, the browser's own fixed layout, or a pasted table's cells.
 */
export function measureColumns(table: HTMLElement): number[] {
  const before = read(table);
  const grid = layout(before.model);
  const edges: Array<number | undefined> = new Array(grid.width + 1);

  for (const [id, origin] of grid.origins) {
    const box = before.cells.get(id)!.getBoundingClientRect();
    edges[origin.col] ??= box.left;
    edges[origin.col + origin.colspan] ??= box.right;
  }

  const box = table.getBoundingClientRect();
  edges[0] ??= box.left;
  edges[grid.width] ??= box.right;

  // A column edge no single cell reaches is spread evenly between its neighbours.
  for (let c = 1; c < grid.width; c += 1) {
    if (edges[c] !== undefined) continue;
    let next = c + 1;
    while (edges[next] === undefined) next += 1;
    edges[c] = edges[c - 1]! + (edges[next]! - edges[c - 1]!) / (next - c + 1);
  }

  return Array.from({ length: grid.width }, (_, c) => Math.max(0, edges[c + 1]! - edges[c]!));
}

/**
 * Which column boundary a pointer is on: the index of the column to its left,
 * or null. The table's outer edges are not draggable — it always fills the page.
 */
export function boundaryAt(cell: HTMLElement, clientX: number, slop = 4): number | null {
  const box = cell.getBoundingClientRect();
  const nearRight = Math.abs(box.right - clientX) <= slop;
  const nearLeft = Math.abs(clientX - box.left) <= slop;
  if (!nearRight && !nearLeft) return null;

  const context = contextOf(cell);
  if (!context) return null;
  const boundary = nearRight ? context.column + context.colspan - 1 : context.column - 1;

  return boundary !== null && boundary >= 0 && boundary < context.columns - 1 ? boundary : null;
}

/**
 * Tables with merged cells, as plain data.
 *
 * A table is rows of cells with their spans, as in HTML. Laid out, it becomes a
 * grid of slots, each covered by one cell (its origin is its top-left slot).
 * Every structural edit — merge, split, insert or delete a row or a column — is
 * computed here on that model and returns a new one, so cells spanning several
 * rows or columns grow, shrink or move instead of being cut. Cells added by an
 * edit carry `isNew`. No imports, so it runs in Node.
 */

export interface ModelCell {
  id: string;
  rowspan: number;
  colspan: number;
  header?: boolean;
  isNew?: boolean;
}

export interface ModelRow {
  id: string;
  cells: ModelCell[];
  isNew?: boolean;
}

export type Model = ModelRow[];

export interface Origin {
  row: number;
  col: number;
  rowspan: number;
  colspan: number;
}

export interface Rect {
  top: number;
  left: number;
  bottom: number;
  right: number;
}

export interface Layout {
  width: number;
  height: number;
  /** slots[row][col]: the cell covering that slot. */
  slots: Array<Array<ModelCell | null>>;
  origins: Map<string, Origin>;
}

let counter = 0;
const fresh = (header?: boolean): ModelCell => ({ id: `n${++counter}`, rowspan: 1, colspan: 1, header, isNew: true });

function clone(model: Model): Model {
  return model.map((row) => ({ ...row, cells: row.cells.map((cell) => ({ ...cell })) }));
}

export function layout(model: Model): Layout {
  const slots: Array<Array<ModelCell | null>> = model.map(() => []);
  const origins = new Map<string, Origin>();
  let width = 0;

  model.forEach((row, r) => {
    let c = 0;
    for (const cell of row.cells) {
      while (slots[r][c]) c += 1;
      const rowspan = Math.max(1, Math.min(cell.rowspan, model.length - r));
      const colspan = Math.max(1, cell.colspan);
      origins.set(cell.id, { row: r, col: c, rowspan, colspan });
      for (let dr = 0; dr < rowspan; dr += 1) {
        for (let dc = 0; dc < colspan; dc += 1) slots[r + dr][c + dc] = cell;
      }
      c += colspan;
      width = Math.max(width, c);
    }
  });

  for (const row of slots) {
    for (let c = 0; c < width; c += 1) row[c] = row[c] ?? null;
  }

  return { width, height: model.length, slots, origins };
}

/** The smallest rectangle holding both cells and every cell it cuts into. */
export function rectOf(model: Model, a: string, b: string): Rect | null {
  const { slots, origins } = layout(model);
  const first = origins.get(a);
  const second = origins.get(b);
  if (!first || !second) return null;

  const rect: Rect = {
    top: Math.min(first.row, second.row),
    left: Math.min(first.col, second.col),
    bottom: Math.max(first.row + first.rowspan, second.row + second.rowspan) - 1,
    right: Math.max(first.col + first.colspan, second.col + second.colspan) - 1,
  };

  let grown = true;
  while (grown) {
    grown = false;
    for (let r = rect.top; r <= rect.bottom; r += 1) {
      for (let c = rect.left; c <= rect.right; c += 1) {
        const cell = slots[r]?.[c];
        const origin = cell ? origins.get(cell.id) : null;
        if (!origin) continue;
        const bottom = origin.row + origin.rowspan - 1;
        const right = origin.col + origin.colspan - 1;
        if (origin.row < rect.top || origin.col < rect.left || bottom > rect.bottom || right > rect.right) {
          rect.top = Math.min(rect.top, origin.row);
          rect.left = Math.min(rect.left, origin.col);
          rect.bottom = Math.max(rect.bottom, bottom);
          rect.right = Math.max(rect.right, right);
          grown = true;
        }
      }
    }
  }

  return rect;
}

/** The cells inside a rectangle, in reading order. */
export function idsIn(model: Model, rect: Rect): string[] {
  const { slots } = layout(model);
  const ids: string[] = [];
  for (let r = rect.top; r <= rect.bottom; r += 1) {
    for (let c = rect.left; c <= rect.right; c += 1) {
      const id = slots[r]?.[c]?.id;
      if (id && !ids.includes(id)) ids.push(id);
    }
  }
  return ids;
}

function find(model: Model, id: string): { row: number; index: number } | null {
  for (let row = 0; row < model.length; row += 1) {
    const index = model[row].cells.findIndex((cell) => cell.id === id);
    if (index >= 0) return { row, index };
  }
  return null;
}

/** Where a cell starting at `col` goes among the cells that start in `row`. */
function indexFor(model: Model, origins: Map<string, Origin>, row: number, col: number): number {
  return model[row].cells.filter((cell) => !cell.isNew && (origins.get(cell.id)?.col ?? 0) < col).length;
}

/** Merge everything between two cells into the top-left one. */
export function merge(model: Model, a: string, b: string): { model: Model; kept: string; absorbed: string[] } {
  const rect = rectOf(model, a, b);
  if (!rect) return { model, kept: a, absorbed: [] };

  const ids = idsIn(model, rect);
  const { slots } = layout(model);
  const kept = slots[rect.top][rect.left]!.id;
  const absorbed = ids.filter((id) => id !== kept);

  const next = clone(model);
  for (const row of next) row.cells = row.cells.filter((cell) => !absorbed.includes(cell.id));
  const position = find(next, kept)!;
  const cell = next[position.row].cells[position.index];
  cell.rowspan = rect.bottom - rect.top + 1;
  cell.colspan = rect.right - rect.left + 1;

  return { model: next, kept, absorbed };
}

/** Give a merged cell's slots back as cells of their own. */
export function split(model: Model, id: string): Model {
  const { origins } = layout(model);
  const origin = origins.get(id);
  if (!origin || (origin.rowspan === 1 && origin.colspan === 1)) return model;

  const next = clone(model);
  const position = find(next, id)!;
  const cell = next[position.row].cells[position.index];
  cell.rowspan = 1;
  cell.colspan = 1;

  // The rest of its first row, right after it.
  next[origin.row].cells.splice(position.index + 1, 0, ...Array.from({ length: origin.colspan - 1 }, () => fresh(cell.header)));

  // The rows below, where it used to reach.
  for (let r = origin.row + 1; r < origin.row + origin.rowspan; r += 1) {
    const at = indexFor(next, origins, r, origin.col);
    next[r].cells.splice(at, 0, ...Array.from({ length: origin.colspan }, () => fresh(cell.header)));
  }

  return next;
}

/** A new column so that it becomes column `at` (0 … width). */
export function insertColumn(model: Model, at: number): Model {
  const { width, slots, origins } = layout(model);
  const next = clone(model);
  const grown = new Set<string>();

  next.forEach((row, r) => {
    const covering = at < width ? slots[r][at] : null;
    const origin = covering ? origins.get(covering.id) : null;

    // A cell reaching across the new column's place just grows, once.
    if (origin && origin.col < at) {
      if (!grown.has(covering!.id)) {
        grown.add(covering!.id);
        const position = find(next, covering!.id)!;
        next[position.row].cells[position.index].colspan += 1;
      }
      return;
    }

    const header = row.cells.length > 0 && row.cells.every((cell) => cell.header);
    row.cells.splice(indexFor(next, origins, r, at), 0, fresh(header));
  });

  return next;
}

export function deleteColumn(model: Model, col: number): Model {
  const { slots } = layout(model);
  const next = clone(model);
  const seen = new Set<string>();

  for (let r = 0; r < next.length; r += 1) {
    const covering = slots[r]?.[col];
    if (!covering || seen.has(covering.id)) continue;
    seen.add(covering.id);
    const position = find(next, covering.id)!;
    const cell = next[position.row].cells[position.index];
    if (cell.colspan > 1) cell.colspan -= 1;
    else next[position.row].cells.splice(position.index, 1);
  }

  return next;
}

/** A new row so that it becomes row `at` (0 … height). */
export function insertRow(model: Model, at: number, header = false): Model {
  const { width, height, slots, origins } = layout(model);
  const next = clone(model);
  const cells: ModelCell[] = [];
  const grown = new Set<string>();

  for (let c = 0; c < width; c += 1) {
    const covering = at < height ? slots[at][c] : null;
    const origin = covering ? origins.get(covering.id) : null;
    // A cell reaching across the new row's place just grows, once.
    if (origin && origin.row < at) {
      if (!grown.has(covering!.id)) {
        grown.add(covering!.id);
        const position = find(next, covering!.id)!;
        next[position.row].cells[position.index].rowspan += 1;
      }
      continue;
    }
    cells.push(fresh(header));
  }

  next.splice(at, 0, { id: `n${++counter}`, cells, isNew: true });
  return next;
}

export function deleteRow(model: Model, row: number): Model {
  const { width, slots, origins } = layout(model);
  const next = clone(model);
  const seen = new Set<string>();

  for (let c = 0; c < width; c += 1) {
    const covering = slots[row]?.[c];
    if (!covering || seen.has(covering.id)) continue;
    seen.add(covering.id);
    const origin = origins.get(covering.id)!;
    const position = find(next, covering.id)!;
    const cell = next[position.row].cells[position.index];

    if (origin.row < row) {
      cell.rowspan -= 1;
    } else if (cell.rowspan > 1 && row + 1 < next.length) {
      // It starts in the row going away: it moves down, one row shorter.
      next[position.row].cells.splice(position.index, 1);
      cell.rowspan -= 1;
      next[row + 1].cells.splice(indexFor(next, origins, row + 1, origin.col), 0, cell);
    } else {
      next[position.row].cells.splice(position.index, 1);
    }
  }

  next.splice(row, 1);
  return next;
}

/**
 * Where a table may be cut across two pages: before each row no cell reaches
 * into from above. Row 0 is never listed — there is nothing to cut off.
 */
export function breaksBefore(model: Model): number[] {
  const { height, origins } = layout(model);
  const blocked = new Set<number>();
  for (const origin of origins.values()) {
    for (let r = origin.row + 1; r < origin.row + origin.rowspan; r += 1) blocked.add(r);
  }
  const breaks: number[] = [];
  for (let r = 1; r < height; r += 1) if (!blocked.has(r)) breaks.push(r);
  return breaks;
}

/**
 * The arithmetic behind dragging an image corner or a column border.
 *
 * Everything is worked out from on-screen pixels, so the page zoom cancels
 * out: a width is stored as a share of what holds it. No imports, so it runs
 * in Node.
 */

export const MIN_IMAGE_WIDTH = 10;

/**
 * An image's new width, in percent of the page body, while a corner is dragged.
 *
 * `side` is the corner's side: dragging a left corner leftwards grows it.
 */
export function dragWidth(startWidth: number, dx: number, parentWidth: number, side: 'left' | 'right'): number {
  if (parentWidth <= 0) return 100;
  const width = startWidth + (side === 'right' ? dx : -dx);
  const percent = Math.round((width / parentWidth) * 100);
  return Math.max(MIN_IMAGE_WIDTH, Math.min(100, percent));
}

/**
 * Column widths after the border right of column `boundary` moves by `dx`.
 *
 * Only the two columns either side of it change, so the table keeps its width;
 * neither gets narrower than `min`.
 */
export function dragColumns(widths: number[], boundary: number, dx: number, min = 24): number[] {
  const next = widths.slice();
  if (boundary < 0 || boundary >= widths.length - 1) return next;

  const pair = widths[boundary] + widths[boundary + 1];
  const floor = Math.min(min, pair / 2);
  const left = Math.max(floor, Math.min(pair - floor, widths[boundary] + dx));
  next[boundary] = left;
  next[boundary + 1] = pair - left;
  return next;
}

/** Widths as percentages adding up to 100, to one decimal. */
export function toPercent(widths: number[]): number[] {
  const total = widths.reduce((sum, width) => sum + width, 0);
  if (total <= 0) return widths.map(() => Math.round(1000 / widths.length) / 10);
  return widths.map((width) => Math.round((width / total) * 1000) / 10);
}

/* ---------------------------------------------------------------- crop -- */

/** What a crop takes off each side — top, right, bottom, left — as shares of the picture. */
export type Insets = [number, number, number, number];

export const NO_CROP: Insets = [0, 0, 0, 0];

/** The smallest share of the picture a crop may keep, on each axis. */
export const MIN_CROP = 0.05;

export type CropHandle = 'n' | 's' | 'e' | 'w' | 'ne' | 'nw' | 'se' | 'sw' | 'move';

const clamp = (value: number, min: number, max: number): number => Math.min(max, Math.max(min, value));
const round = (value: number): number => Math.round(value * 10000) / 10000;

/**
 * A crop after a handle moved by `dx`, `dy` (shares of the picture's width and
 * height); `move` slides the whole kept area. Never keeps less than MIN_CROP.
 */
export function dragInsets(start: Insets, handle: CropHandle, dx: number, dy: number): Insets {
  let [top, right, bottom, left] = start;
  if (handle === 'move') {
    const x = clamp(dx, -left, right);
    const y = clamp(dy, -top, bottom);
    return [top + y, right - x, bottom - y, left + x].map(round) as Insets;
  }
  if (handle.includes('n')) top = clamp(start[0] + dy, 0, 1 - bottom - MIN_CROP);
  if (handle.includes('s')) bottom = clamp(start[2] - dy, 0, 1 - top - MIN_CROP);
  if (handle.includes('w')) left = clamp(start[3] + dx, 0, 1 - right - MIN_CROP);
  if (handle.includes('e')) right = clamp(start[1] - dx, 0, 1 - left - MIN_CROP);
  return [top, right, bottom, left].map(round) as Insets;
}

/** A crop carried through a quarter turn: clockwise (1) or back (-1). */
export function turnInsets([top, right, bottom, left]: Insets, turn: 1 | -1): Insets {
  return turn === 1 ? [left, top, right, bottom] : [right, bottom, left, top];
}

/** A turn added to an angle, kept to 0, 90, 180 or 270. */
export function turnAngle(angle: number, turn: 1 | -1): number {
  return (((angle + turn * 90) % 360) + 360) % 360;
}

export function parseInsets(value: string | null): Insets | null {
  const parts = (value ?? '').split(',').map(Number);
  if (parts.length !== 4 || parts.some((part) => !Number.isFinite(part) || part < 0 || part >= 1)) return null;
  if (parts[0] + parts[2] > 1 - MIN_CROP + 1e-9 || parts[1] + parts[3] > 1 - MIN_CROP + 1e-9) return null;
  return parts as Insets;
}

export function formatInsets(insets: Insets): string {
  return insets.map(round).join(',');
}

export const isUncropped = (insets: Insets): boolean => insets.every((inset) => inset === 0);

/* ---------------------------------------------------------------- move -- */

/**
 * Where something dropped at height `y` goes among blocks laid out top to
 * bottom: the index of the block it goes before (the count when it goes last).
 * Over a block, its top half means before it and its bottom half after it.
 */
export function dropGap(spans: Array<{ top: number; bottom: number }>, y: number): number {
  for (let index = 0; index < spans.length; index++) {
    const { top, bottom } = spans[index];
    if (y < top) return index;
    if (y <= bottom) return y < (top + bottom) / 2 ? index : index + 1;
  }
  return spans.length;
}

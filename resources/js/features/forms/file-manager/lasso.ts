/**
 * Selecting with a lasso: the box a mouse drag draws, and the entries it touches.
 * No DOM and no local import, so it runs in Node.
 */

export type Box = { left: number; top: number; right: number; bottom: number };

/** The box between where the drag started and where the pointer is, whichever way it went. */
export function boxFrom(a: { x: number; y: number }, b: { x: number; y: number }): Box {
  return {
    left: Math.min(a.x, b.x),
    top: Math.min(a.y, b.y),
    right: Math.max(a.x, b.x),
    bottom: Math.max(a.y, b.y),
  };
}

/** The ids of the boxes the lasso touches, a shared edge included, in their order. */
export function hits(box: Box, boxes: Array<{ id: string } & Box>): string[] {
  return boxes
    .filter((other) => other.left <= box.right && other.right >= box.left && other.top <= box.bottom && other.bottom >= box.top)
    .map((other) => other.id);
}

/**
 * Selection bookkeeping.
 *
 * Pagination physically moves blocks between page hosts, which destroys any
 * `Range` pointing into them. So the caret is stored logically instead: the
 * stable id of the block it sits in, plus a character offset inside that
 * block's text. That survives the block being re-parented, and — because a
 * block split across a page boundary keeps its id on every part — it survives
 * the block being cut in half too.
 *
 * Text offsets cannot tell the two sides of a line break apart, so a point
 * also counts the `<br>`s between the last text before it and itself.
 */

import { isText, isVoid } from './dom';

export interface CaretPoint {
  blockId: string;
  /** Character offset within the block's text, counting every part. */
  offset: number;
  /** Line breaks between the text at `offset` and the caret. */
  breaks?: number;
}

export interface SavedSelection {
  anchor: CaretPoint;
  focus: CaretPoint;
}

const ID_ATTR = 'data-nk-id';
const PART_ATTR = 'data-nk-part';

let idCounter = 0;

/** Stable id for a block, minted on first use. */
export function blockId(block: HTMLElement): string {
  let id = block.getAttribute(ID_ATTR);
  if (!id) {
    id = `b${Date.now().toString(36)}${(idCounter++).toString(36)}`;
    block.setAttribute(ID_ATTR, id);
  }
  return id;
}

/**
 * Give every block a caret can sit in an id: the top-level blocks under each
 * page host, and the leaves inside lists, tables and figures. Without one, a
 * caret in a cell would be kept relative to the whole table.
 */
export function assignIds(root: HTMLElement): void {
  root
    .querySelectorAll<HTMLElement>('[data-nk-content] > *, [data-nk-content] :is(li, td, th, figcaption)')
    .forEach(blockId);
}

/**
 * Every element carrying `id`, in document order.
 *
 * More than one means pagination split the block across pages; they are treated
 * as a single logical block whose text is the concatenation of the parts.
 */
export function blockParts(root: HTMLElement, id: string): HTMLElement[] {
  const parts = Array.from(
    root.querySelectorAll<HTMLElement>(`[${ID_ATTR}="${CSS.escape(id)}"]`)
  );
  if (parts.length < 2) return parts;
  return parts.sort((a, b) => {
    const partA = Number(a.getAttribute(PART_ATTR) ?? 0);
    const partB = Number(b.getAttribute(PART_ATTR) ?? 0);
    return partA - partB;
  });
}

/** The nearest ancestor that has a block id. */
function idOwner(node: Node | null, root: HTMLElement): HTMLElement | null {
  let current: Node | null = node;
  while (current && current !== root) {
    if (current.nodeType === Node.ELEMENT_NODE) {
      const element = current as HTMLElement;
      if (element.hasAttribute(ID_ATTR)) return element;
    }
    current = current.parentNode;
  }
  return null;
}

/** Character offset of (container, offset) measured from the start of `block`. */
function offsetInBlock(block: HTMLElement, container: Node, offset: number): number {
  const doc = block.ownerDocument!;
  const walker = doc.createTreeWalker(block, NodeFilter.SHOW_TEXT);
  let total = 0;

  // A caret anchored on an element counts the text of the children before it.
  if (!isText(container)) {
    const before = doc.createRange();
    before.selectNodeContents(block);
    try {
      before.setEnd(container, offset);
      return before.toString().length;
    } catch {
      return 0;
    }
  }

  let node = walker.nextNode();
  while (node) {
    if (node === container) return total + offset;
    total += node.nodeValue?.length ?? 0;
    node = walker.nextNode();
  }
  return total;
}

/** Turn a DOM point into a logical caret point. */
function toPoint(root: HTMLElement, container: Node, offset: number): CaretPoint | null {
  const owner = idOwner(container, root);
  if (!owner) return null;

  const id = owner.getAttribute(ID_ATTR)!;
  const parts = blockParts(root, id);
  const index = parts.indexOf(owner);

  // Text in earlier parts of a split block counts toward the offset.
  let preceding = 0;
  for (let i = 0; i < index; i += 1) preceding += parts[i].textContent?.length ?? 0;

  const point: CaretPoint = { blockId: id, offset: preceding + offsetInBlock(owner, container, offset) };
  const breaks = breaksBefore(owner, container, offset);
  if (breaks) point.breaks = breaks;
  return point;
}

const isBreak = (node: Node): boolean => node.nodeType === Node.ELEMENT_NODE && (node as Element).tagName === 'BR';

/** The `<br>`s between the last text before (container, offset) and that point. */
function breaksBefore(block: HTMLElement, container: Node, offset: number): number {
  const doc = block.ownerDocument!;
  const caret = doc.createRange();
  try {
    caret.setStart(container, offset);
  } catch {
    return 0;
  }

  const walker = doc.createTreeWalker(block, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
  let count = 0;
  for (let node = walker.nextNode(); node; node = walker.nextNode()) {
    if (node === container) {
      if (isText(node) && offset > 0) count = 0;
      break;
    }
    if (caret.comparePoint(node, 0) >= 0) break;
    if (isText(node) && node.nodeValue) count = 0;
    else if (isBreak(node)) count += 1;
  }
  return count;
}

/** Step forward from a resolved point over `count` line breaks, stopping at any text. */
function skipBreaks(part: HTMLElement, point: { node: Node; offset: number }, count: number): { node: Node; offset: number } {
  const doc = part.ownerDocument!;
  const start = doc.createRange();
  start.setStart(point.node, point.offset);

  const walker = doc.createTreeWalker(part, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
  let result = point;
  let left = count;
  for (let node = walker.nextNode(); node && left > 0; node = walker.nextNode()) {
    if (node === point.node || start.comparePoint(node, 0) < 0) continue;
    if (isText(node) && node.nodeValue) break;
    if (isBreak(node) && node.parentNode) {
      result = { node: node.parentNode, offset: Array.from(node.parentNode.childNodes).indexOf(node as ChildNode) + 1 };
      left -= 1;
    }
  }
  return result;
}

/** Resolve a logical caret point back to a concrete DOM position. */
function toDom(root: HTMLElement, point: CaretPoint): { node: Node; offset: number } | null {
  const found = toText(root, point);
  if (!found || !point.breaks) return found?.position ?? null;
  return skipBreaks(found.part, found.position, point.breaks);
}

/** The DOM position of a point's text offset, and the part it is in. */
function toText(root: HTMLElement, point: CaretPoint): { part: HTMLElement; position: { node: Node; offset: number } } | null {
  const parts = blockParts(root, point.blockId);
  if (parts.length === 0) return null;

  let remaining = point.offset;

  for (const part of parts) {
    const length = part.textContent?.length ?? 0;
    // The last part absorbs any overshoot so a shrunken block still resolves.
    const isLast = part === parts[parts.length - 1];

    if (remaining <= length || isLast) {
      const doc = part.ownerDocument!;
      const walker = doc.createTreeWalker(part, NodeFilter.SHOW_TEXT);
      let seen = 0;
      let node = walker.nextNode();

      while (node) {
        const size = node.nodeValue?.length ?? 0;
        if (seen + size >= remaining) {
          return { part, position: { node, offset: Math.max(0, Math.min(size, remaining - seen)) } };
        }
        seen += size;
        node = walker.nextNode();
      }

      // No text at all (an empty block, or one holding only a `<br>`).
      const voidChild = Array.from(part.childNodes).find(isVoid);
      if (voidChild) {
        return { part, position: { node: part, offset: Array.from(part.childNodes).indexOf(voidChild) } };
      }
      return { part, position: { node: part, offset: 0 } };
    }

    remaining -= length;
  }

  return null;
}

/** The live range, but only when it actually sits inside the editor. */
export function currentRange(root: HTMLElement): Range | null {
  const selection = root.ownerDocument!.defaultView?.getSelection();
  if (!selection || selection.rangeCount === 0) return null;

  const range = selection.getRangeAt(0);
  if (!root.contains(range.commonAncestorContainer)) return null;
  return range;
}

/** Snapshot the caret in a form that survives the DOM being rebuilt. */
export function save(root: HTMLElement): SavedSelection | null {
  const selection = root.ownerDocument!.defaultView?.getSelection();
  if (!selection || selection.rangeCount === 0 || !selection.anchorNode) return null;
  if (!root.contains(selection.anchorNode)) return null;

  const anchor = toPoint(root, selection.anchorNode, selection.anchorOffset);
  const focus = selection.focusNode
    ? toPoint(root, selection.focusNode, selection.focusOffset)
    : null;

  if (!anchor) return null;
  return { anchor, focus: focus ?? anchor };
}

/** Put the caret back where `save` found it. */
export function restore(root: HTMLElement, saved: SavedSelection | null): void {
  if (!saved) return;

  const anchor = toDom(root, saved.anchor);
  if (!anchor) return;
  const focus = toDom(root, saved.focus) ?? anchor;

  const selection = root.ownerDocument!.defaultView?.getSelection();
  if (!selection) return;

  try {
    const range = root.ownerDocument!.createRange();
    range.setStart(anchor.node, anchor.offset);
    range.setEnd(focus.node, focus.offset);
    selection.removeAllRanges();
    selection.addRange(range);
  } catch {
    // Offsets can go stale if content shrank mid-flight; a lost caret is
    // recoverable, a thrown exception mid-reflow is not.
  }
}

/** Run `mutate`, keeping the caret where the user left it. */
export function preserving<T>(root: HTMLElement, mutate: () => T): T {
  const saved = save(root);
  const result = mutate();
  restore(root, saved);
  return result;
}

/** Place the caret at a character offset inside a block. */
export function setCaret(root: HTMLElement, block: HTMLElement, offset = 0): void {
  restore(root, {
    anchor: { blockId: blockId(block), offset },
    focus: { blockId: blockId(block), offset },
  });
}

export { ID_ATTR, PART_ATTR };

/**
 * Low-level DOM primitives shared by the mark, block and pagination layers.
 *
 * Nothing here knows about the editor's commands — these are the pieces that
 * make range surgery predictable: splitting text at an offset, walking only the
 * text a range actually covers, and unwrapping an element without disturbing
 * the caret.
 */

import { BLOCK_TAGS, CONTAINER_TAGS, VOID_TAGS } from './schema';

export const tag = (node: Node | null): string =>
  node && node.nodeType === Node.ELEMENT_NODE ? (node as Element).tagName.toLowerCase() : '';

export const isText = (node: Node | null): node is Text =>
  !!node && node.nodeType === Node.TEXT_NODE;

export const isElement = (node: Node | null): node is HTMLElement =>
  !!node && node.nodeType === Node.ELEMENT_NODE;

export const isBlock = (node: Node | null): boolean => BLOCK_TAGS.has(tag(node));

export const isContainer = (node: Node | null): boolean => CONTAINER_TAGS.has(tag(node));

export const isVoid = (node: Node | null): boolean => VOID_TAGS.has(tag(node));

/** The leaf block a node lives in, or null if the node sits outside `root`. */
export function closestBlock(node: Node | null, root: HTMLElement): HTMLElement | null {
  let current: Node | null = node;
  while (current && current !== root) {
    if (isElement(current) && isBlock(current)) return current;
    current = current.parentNode;
  }
  return null;
}

/** The outermost element under `root` containing `node` — what pagination moves. */
export function topLevelAncestor(node: Node | null, root: HTMLElement): HTMLElement | null {
  let current: Node | null = node;
  while (current && current.parentNode && current.parentNode !== root) {
    current = current.parentNode;
  }
  return isElement(current) && current.parentNode === root ? current : null;
}

/**
 * Split `node` at `offset`, returning the node that holds the text *after* the
 * split. Returns the original node when the offset lands on a boundary, so
 * callers never end up with empty siblings.
 */
export function splitText(node: Text, offset: number): Text {
  if (offset <= 0) return node;
  if (offset >= node.length) {
    // Nothing after the offset — hand back an empty node the caller can use as
    // an insertion point without mutating the original.
    const empty = node.ownerDocument!.createTextNode('');
    node.parentNode?.insertBefore(empty, node.nextSibling);
    return empty;
  }
  return node.splitText(offset);
}

/**
 * Every text node the range covers, with boundary nodes already split so each
 * returned node is *entirely* inside the range.
 *
 * The range is mutated to stay valid across the splits.
 */
export function textNodesInRange(range: Range, root: HTMLElement): Text[] {
  if (range.collapsed) return [];

  // Split the end first: splitting the start would shift the end offset if both
  // boundaries share a text node.
  if (isText(range.endContainer)) {
    const end = range.endContainer;
    const offset = range.endOffset;
    if (offset > 0 && offset < end.length) {
      end.splitText(offset);
      range.setEnd(end, end.length);
    }
  }

  if (isText(range.startContainer)) {
    const start = range.startContainer;
    const offset = range.startOffset;
    if (offset > 0 && offset < start.length) {
      const after = start.splitText(offset);
      range.setStart(after, 0);
    }
  }

  const nodes: Text[] = [];

  // Only the subtree the range spans needs walking — the splits above may have
  // moved the boundary, so the ancestor is read after they happen.
  const common = range.commonAncestorContainer;
  const scope =
    (common.nodeType === Node.ELEMENT_NODE ? (common as HTMLElement) : common.parentElement) ??
    root;

  const walker = root.ownerDocument!.createTreeWalker(scope, NodeFilter.SHOW_TEXT, {
    acceptNode(node) {
      if (!node.nodeValue?.length) return NodeFilter.FILTER_REJECT;
      return range.intersectsNode(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
    },
  });

  let current = walker.nextNode();
  while (current) {
    const text = current as Text;
    // `intersectsNode` is inclusive of touching boundaries; require real overlap.
    const nodeRange = root.ownerDocument!.createRange();
    nodeRange.selectNodeContents(text);
    const startsBeforeEnd = range.compareBoundaryPoints(Range.END_TO_START, nodeRange) < 0;
    const endsAfterStart = range.compareBoundaryPoints(Range.START_TO_END, nodeRange) > 0;
    if (startsBeforeEnd && endsAfterStart) nodes.push(text);
    current = walker.nextNode();
  }

  return nodes;
}

/** All leaf blocks the range touches, in document order. */
export function blocksInRange(range: Range, root: HTMLElement): HTMLElement[] {
  const blocks: HTMLElement[] = [];

  // Scoped to the range's ancestor: the toolbar asks for this on every
  // selection change, and walking every block on every page is wasteful.
  const common = range.commonAncestorContainer;
  const scope =
    (common.nodeType === Node.ELEMENT_NODE ? (common as HTMLElement) : common.parentElement) ??
    root;

  // A collapsed caret's ancestor often *is* the block, and a TreeWalker never
  // yields its own root — so test it before walking.
  if (isBlock(scope) && range.intersectsNode(scope)) blocks.push(scope);

  const walker = root.ownerDocument!.createTreeWalker(scope, NodeFilter.SHOW_ELEMENT, {
    acceptNode(node) {
      return isBlock(node) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
    },
  });

  let current = walker.nextNode();
  while (current) {
    if (range.intersectsNode(current)) blocks.push(current as HTMLElement);
    current = walker.nextNode();
  }

  if (blocks.length === 0) {
    const single = closestBlock(range.startContainer, root);
    if (single) blocks.push(single);
  }

  return blocks;
}

/** Replace an element with its children, keeping document order. */
export function unwrap(element: HTMLElement): void {
  const parent = element.parentNode;
  if (!parent) return;
  while (element.firstChild) parent.insertBefore(element.firstChild, element);
  parent.removeChild(element);
}

/** Wrap `nodes` (assumed adjacent siblings) in a fresh element. */
export function wrapAll(nodes: Node[], wrapper: HTMLElement): HTMLElement {
  const first = nodes[0];
  if (!first?.parentNode) return wrapper;
  first.parentNode.insertBefore(wrapper, first);
  for (const node of nodes) wrapper.appendChild(node);
  return wrapper;
}

/** True when two elements are the same tag with identical attributes. */
export function sameElement(a: Element | null, b: Element | null): boolean {
  if (!a || !b || a.tagName !== b.tagName) return false;
  if (a.attributes.length !== b.attributes.length) return false;
  for (const attr of Array.from(a.attributes)) {
    if (b.getAttribute(attr.name) !== attr.value) return false;
  }
  return true;
}

/**
 * Merge identical adjacent inline elements and glue split text back together.
 *
 * Mark commands intentionally leave debris — `<strong>a</strong><strong>b</strong>`
 * or three text nodes where one would do. Running this afterwards keeps the
 * serialized HTML clean and keeps offset maths cheap.
 */
export function normalizeInline(container: HTMLElement): void {
  let child = container.firstChild;

  while (child) {
    const next = child.nextSibling;

    if (isElement(child)) {
      // Drop inline wrappers that ended up with nothing in them.
      if (!isVoid(child) && !isBlock(child) && !isContainer(child) && !child.firstChild) {
        container.removeChild(child);
        child = next;
        continue;
      }

      // Only *inline* debris may be merged. Two adjacent `<p>` elements look
      // identical once their ids are stripped, and folding those together
      // would silently collapse the whole document into one paragraph.
      const mergeable = !isBlock(child) && !isContainer(child) && !isVoid(child);

      if (mergeable && next && isElement(next) && sameElement(child, next)) {
        while (next.firstChild) child.appendChild(next.firstChild);
        container.removeChild(next);
        continue; // re-test `child` against its new sibling
      }

      normalizeInline(child);
    }

    child = next;
  }

  container.normalize();
}

/** Collapse a range down to a single point at its start. */
export function collapseToStart(range: Range): Range {
  const clone = range.cloneRange();
  clone.collapse(true);
  return clone;
}

/** Character length of an element's text content, as the caret counts it. */
export function textLength(node: Node): number {
  return node.textContent?.length ?? 0;
}

/** Create an element with attributes in one call. */
export function el<K extends keyof HTMLElementTagNameMap>(
  doc: Document,
  name: K | string,
  attrs: Record<string, string> = {}
): HTMLElement {
  const element = doc.createElement(name as string);
  for (const [key, value] of Object.entries(attrs)) element.setAttribute(key, value);
  return element;
}

/** An empty paragraph with the `<br>` browsers need to make it focusable. */
export function emptyParagraph(doc: Document): HTMLElement {
  const p = doc.createElement('p');
  p.appendChild(doc.createElement('br'));
  return p;
}

/** True when a block holds no user text (only a placeholder `<br>`). */
export function isEmptyBlock(block: HTMLElement): boolean {
  if (block.querySelector('img, hr, table')) return false;
  const text = block.textContent ?? '';
  return text.replace(/​/g, '').trim().length === 0;
}

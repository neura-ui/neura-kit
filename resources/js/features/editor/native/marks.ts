/**
 * Inline mark application.
 *
 * Marks are real elements (`<strong>`, `<a>`, `<span style="color:…">`) rather
 * than a styling side-channel, so applying one is range surgery: split the
 * boundary text nodes, then wrap or peel each covered text node.
 *
 * `document.execCommand` would do some of this, but it is deprecated, differs
 * per browser and emits `<font>` tags, so the editor owns it instead.
 */

import { isElement, isText, normalizeInline, splitText, textNodesInRange, unwrap } from './dom';
import { MARKS, type MarkName, type MarkSpec, TAG_TO_MARK } from './schema';

/** Zero-width space used to hold a mark open at a collapsed caret. */
export const ZWSP = '​';

/** True when `element` is the mark `name` — matching value too, if given. */
export function isMarkElement(element: Element, name: MarkName, value?: string): boolean {
  const spec = MARKS[name];
  const elementTag = element.tagName.toLowerCase();

  if (spec.style) {
    if (elementTag !== spec.tag) return false;
    const actual = (element as HTMLElement).style.getPropertyValue(spec.style);
    if (!actual) return false;
    return value === undefined || normalizeValue(actual) === normalizeValue(value);
  }

  if (elementTag !== spec.tag && !(spec.aliases ?? []).includes(elementTag)) return false;
  if (name === 'link' && value !== undefined) {
    return element.getAttribute('href') === value;
  }
  return true;
}

const normalizeValue = (value: string): string => value.trim().replace(/\s+/g, ' ').toLowerCase();

/** The mark element of kind `name` wrapping `node`, if any. */
export function markAncestor(
  node: Node,
  name: MarkName,
  root: HTMLElement,
  value?: string
): HTMLElement | null {
  let current: Node | null = node;
  while (current && current !== root) {
    if (isElement(current) && isMarkElement(current, name, value)) return current;
    current = current.parentNode;
  }
  return null;
}

/** Build the element that carries a mark. */
function createMark(doc: Document, name: MarkName, value?: string): HTMLElement {
  const spec: MarkSpec = MARKS[name];
  const element = doc.createElement(spec.tag);

  if (spec.style && value) {
    element.style.setProperty(spec.style, value);
  } else if (name === 'link' && value) {
    element.setAttribute('href', value);
    element.setAttribute('rel', 'noopener noreferrer nofollow');
  }

  return element;
}

/**
 * Split every ancestor between `node` and `ancestor` so that `ancestor` ends up
 * containing nothing but the path down to `node`.
 */
function isolate(node: Node, ancestor: HTMLElement): void {
  let current: Node = node;
  while (current.parentNode && current.parentNode !== ancestor) {
    current = splitAround(current.parentNode as HTMLElement, current);
  }
  if (current.parentNode === ancestor) splitAround(ancestor, current);
}

/** Split `parent` so it holds only `child`, moving the siblings into clones. */
function splitAround(parent: HTMLElement, child: Node): HTMLElement {
  const grandparent = parent.parentNode;
  if (!grandparent) return parent;

  if (child.previousSibling) {
    const before = parent.cloneNode(false) as HTMLElement;
    while (parent.firstChild && parent.firstChild !== child) {
      before.appendChild(parent.firstChild);
    }
    grandparent.insertBefore(before, parent);
  }

  if (child.nextSibling) {
    const after = parent.cloneNode(false) as HTMLElement;
    while (child.nextSibling) after.appendChild(child.nextSibling);
    grandparent.insertBefore(after, parent.nextSibling);
  }

  return parent;
}

/** Wrap each covered text node in `name`, skipping text that already has it. */
export function applyMark(
  root: HTMLElement,
  range: Range,
  name: MarkName,
  value?: string
): void {
  const spec = MARKS[name];

  // A styled mark replaces rather than nests: drop the old colour before
  // painting the new one, or the inner span would win silently.
  if (spec.style || name === 'link') removeMark(root, range, name);

  for (const other of spec.excludes ?? []) removeMark(root, range, other);

  const nodes = textNodesInRange(range, root);
  if (nodes.length === 0) return;

  const doc = root.ownerDocument!;

  for (const node of nodes) {
    if (!node.nodeValue?.length) continue;
    if (markAncestor(node, name, root, value)) continue;

    const wrapper = createMark(doc, name, value);
    node.parentNode?.insertBefore(wrapper, node);
    wrapper.appendChild(node);
  }

  // Re-anchor the range: the wrappers moved its boundary nodes.
  const first = nodes[0];
  const last = nodes[nodes.length - 1];
  range.setStart(first, 0);
  range.setEnd(last, last.length);
}

/** Peel `name` off every covered text node. */
export function removeMark(root: HTMLElement, range: Range, name: MarkName): void {
  const nodes = textNodesInRange(range, root);
  if (nodes.length === 0) return;

  for (const node of nodes) {
    let ancestor = markAncestor(node, name, root);
    // A mark can appear more than once on the same path after messy pastes.
    while (ancestor) {
      isolate(node, ancestor);
      unwrap(ancestor);
      ancestor = markAncestor(node, name, root);
    }
  }

  const first = nodes[0];
  const last = nodes[nodes.length - 1];
  if (first.isConnected && last.isConnected) {
    range.setStart(first, 0);
    range.setEnd(last, last.length);
  }
}

/**
 * Whether the mark covers the whole selection.
 *
 * Google Docs lights the toolbar button only when *every* character carries the
 * mark, so partial coverage reads as inactive and the next click applies it.
 */
export function hasMark(root: HTMLElement, range: Range, name: MarkName, value?: string): boolean {
  if (range.collapsed) {
    return !!markAncestor(range.startContainer, name, root, value);
  }

  const probe = range.cloneRange();
  const nodes = collectWithoutSplitting(probe, root);
  if (nodes.length === 0) return !!markAncestor(range.startContainer, name, root, value);

  return nodes.every((node) => !!markAncestor(node, name, root, value));
}

/** The value of a styled mark at the caret — the colour or font in force. */
export function markValue(root: HTMLElement, range: Range, name: MarkName): string | null {
  const spec = MARKS[name];
  if (!spec.style && name !== 'link') return null;

  const ancestor = markAncestor(range.startContainer, name, root);
  if (!ancestor) return null;

  return spec.style
    ? ancestor.style.getPropertyValue(spec.style) || null
    : ancestor.getAttribute('href');
}

/**
 * Text nodes a range covers, *without* splitting anything.
 *
 * Query paths run on every selection change; splitting text there would churn
 * the DOM (and dirty the undo stack) just to answer whether a button is lit.
 */
function collectWithoutSplitting(range: Range, root: HTMLElement): Text[] {
  const nodes: Text[] = [];
  const doc = root.ownerDocument!;

  // Scope the walk to what the range actually spans. Walking the whole root
  // would touch every text node on every page for each of the twelve marks the
  // toolbar asks about, on every selection change.
  const common = range.commonAncestorContainer;
  const scope = (
    common.nodeType === Node.ELEMENT_NODE ? (common as HTMLElement) : common.parentElement
  ) ?? root;

  const walker = doc.createTreeWalker(scope, NodeFilter.SHOW_TEXT);

  let node = walker.nextNode();
  while (node) {
    const text = node as Text;
    if (text.nodeValue?.replace(new RegExp(ZWSP, 'g'), '').length) {
      const nodeRange = doc.createRange();
      nodeRange.selectNodeContents(text);
      const startsBeforeEnd = range.compareBoundaryPoints(Range.END_TO_START, nodeRange) < 0;
      const endsAfterStart = range.compareBoundaryPoints(Range.START_TO_END, nodeRange) > 0;
      if (startsBeforeEnd && endsAfterStart) nodes.push(text);
    }
    node = walker.nextNode();
  }

  return nodes;
}

/**
 * Toggle a mark. With nothing selected this opens an empty mark at the caret
 * holding a zero-width space, so the next keystroke inherits the formatting —
 * the behaviour you get from hitting Ctrl+B before typing in Docs.
 */
export function toggleMark(
  root: HTMLElement,
  range: Range,
  name: MarkName,
  value?: string
): Range {
  const active = hasMark(root, range, name, value);

  if (range.collapsed) {
    if (active) {
      const ancestor = markAncestor(range.startContainer, name, root, value);
      if (ancestor) {
        // Step out of the mark by parking the caret in a bare text node after it.
        const doc = root.ownerDocument!;
        const escape = doc.createTextNode(ZWSP);
        ancestor.parentNode?.insertBefore(escape, ancestor.nextSibling);
        range.setStart(escape, 1);
        range.collapse(true);
      }
      return range;
    }

    const doc = root.ownerDocument!;
    const wrapper = createMark(doc, name, value);
    const holder = doc.createTextNode(ZWSP);
    wrapper.appendChild(holder);
    range.insertNode(wrapper);
    range.setStart(holder, 1);
    range.collapse(true);
    return range;
  }

  if (active) {
    removeMark(root, range, name);
  } else {
    applyMark(root, range, name, value);
  }

  return range;
}

/** Strip every mark from the selection, leaving the block type alone. */
export function clearMarks(root: HTMLElement, range: Range): void {
  for (const name of Object.keys(MARKS) as MarkName[]) {
    removeMark(root, range, name);
  }
}

/** Marks currently in force, for lighting up the toolbar. */
export function activeMarks(root: HTMLElement, range: Range): Set<MarkName> {
  const active = new Set<MarkName>();
  for (const name of Object.keys(MARKS) as MarkName[]) {
    if (hasMark(root, range, name)) active.add(name);
  }
  return active;
}

/** Remove the zero-width spaces used to hold marks open. */
export function stripHolders(container: HTMLElement): void {
  const doc = container.ownerDocument!;
  const walker = doc.createTreeWalker(container, NodeFilter.SHOW_TEXT);
  const empties: Text[] = [];

  let node = walker.nextNode();
  while (node) {
    const text = node as Text;
    if (text.nodeValue?.includes(ZWSP)) {
      text.nodeValue = text.nodeValue.replace(new RegExp(ZWSP, 'g'), '');
      if (!text.nodeValue.length) empties.push(text);
    }
    node = walker.nextNode();
  }

  for (const text of empties) text.parentNode?.removeChild(text);
  normalizeInline(container);
}

export { TAG_TO_MARK, splitText, isText };

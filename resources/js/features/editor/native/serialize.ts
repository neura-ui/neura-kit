/**
 * Turning the live DOM into a value, and a value back into blocks.
 *
 * The live DOM is paginated: blocks live inside page hosts and a long paragraph
 * may exist as several parts sharing one id. None of that belongs in the saved
 * value, so reading always *de-paginates* first — merge the parts, drop the
 * page wrappers, translate the editor's bookkeeping attributes into ordinary
 * inline styles so the HTML renders correctly outside the editor.
 */

import { isElement, normalizeInline, tag } from './dom';
import { stripHolders } from './marks';
import { sanitizeHtml } from './sanitize';
import { MARKS, type MarkName } from './schema';
import { ID_ATTR, PART_ATTR } from './selection';

export interface JsonMark {
  type: MarkName;
  attrs?: Record<string, string>;
}

export interface JsonNode {
  type: string;
  attrs?: Record<string, string | number>;
  text?: string;
  marks?: JsonMark[];
  content?: JsonNode[];
}

export interface JsonDoc {
  type: 'doc';
  content: JsonNode[];
}

export const emptyDoc = (): JsonDoc => ({
  type: 'doc',
  content: [{ type: 'paragraph' }],
});

/** Indent steps are rendered as this much left margin in exported HTML. */
const INDENT_STEP_REM = 2.5;

/**
 * Collect the logical document out of the paginated DOM.
 *
 * Returns a detached clone — callers mutate it freely without touching what the
 * user is editing.
 */
export function readDocument(root: HTMLElement): HTMLElement {
  const doc = root.ownerDocument!;
  const output = doc.createElement('div');

  const hosts = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content]'));
  const sources = hosts.length ? hosts : [root];

  for (const host of sources) {
    for (const child of Array.from(host.children)) {
      output.appendChild(child.cloneNode(true));
    }
  }

  mergeParts(output);
  stripHolders(output);
  materializeAttributes(output);
  normalizeInline(output);
  dropEmptyTrailing(output);

  return output;
}

/**
 * Re-join blocks the flow engine split across a page boundary.
 *
 * Parts share an id and carry an ascending `data-nk-part`; the first part
 * absorbs the rest.
 */
function mergeParts(container: HTMLElement): void {
  const seen = new Map<string, HTMLElement>();

  for (const element of Array.from(container.querySelectorAll<HTMLElement>(`[${PART_ATTR}]`))) {
    const id = element.getAttribute(ID_ATTR);
    if (!id) continue;

    const first = seen.get(id);
    if (!first) {
      seen.set(id, element);
      continue;
    }

    while (element.firstChild) first.appendChild(element.firstChild);
    element.remove();
  }

  for (const element of seen.values()) element.removeAttribute(PART_ATTR);
}

/** Translate `data-nk-*` bookkeeping into portable inline styles. */
function materializeAttributes(container: HTMLElement): void {
  for (const element of Array.from(container.querySelectorAll<HTMLElement>('*'))) {
    const align = element.getAttribute('data-nk-align');
    const indent = element.getAttribute('data-nk-indent');
    const spacing = element.getAttribute('data-nk-spacing');

    if (align) element.style.textAlign = align;
    if (indent) element.style.marginLeft = `${Number(indent) * INDENT_STEP_REM}rem`;
    if (spacing) element.style.lineHeight = spacing;

    // A manual page break is a layout instruction, not content.
    if (element.hasAttribute('data-nk-break')) {
      element.replaceWith();
      continue;
    }

    element.removeAttribute('contenteditable');

    // Every `data-nk-*` attribute is editor bookkeeping — block ids, split
    // markers, the image-load flag. Stripping the whole namespace keeps the
    // saved value clean without needing a list that must be kept in step.
    for (const attr of Array.from(element.attributes)) {
      if (attr.name.startsWith('data-nk-')) element.removeAttribute(attr.name);
    }

    if (!element.getAttribute('style')) element.removeAttribute('style');
  }
}

/** Drop the trailing empty paragraphs pagination leaves behind. */
function dropEmptyTrailing(container: HTMLElement): void {
  let last = container.lastElementChild;
  while (last && container.children.length > 1) {
    const isBlank =
      !last.querySelector('img, hr, table') && (last.textContent ?? '').trim().length === 0;
    if (!isBlank) break;
    const previous = last.previousElementSibling;
    last.remove();
    last = previous;
  }
}

/** The document as clean, portable HTML. */
export function toHtml(root: HTMLElement): string {
  return readDocument(root).innerHTML;
}

/** The document as a structured JSON tree. */
export function toJson(root: HTMLElement): JsonDoc {
  const container = readDocument(root);
  const content = Array.from(container.children)
    .map(nodeToJson)
    .filter((node): node is JsonNode => node !== null);

  return { type: 'doc', content: content.length ? content : emptyDoc().content };
}

const BLOCK_TYPE_BY_TAG: Record<string, string> = {
  p: 'paragraph',
  h1: 'heading',
  h2: 'heading',
  h3: 'heading',
  h4: 'heading',
  h5: 'heading',
  h6: 'heading',
  blockquote: 'blockquote',
  pre: 'codeBlock',
  ul: 'bulletList',
  ol: 'orderedList',
  li: 'listItem',
  table: 'table',
  tr: 'tableRow',
  td: 'tableCell',
  th: 'tableHeader',
  figure: 'figure',
  figcaption: 'caption',
  hr: 'horizontalRule',
  img: 'image',
};

function nodeToJson(node: Node): JsonNode | null {
  if (node.nodeType === Node.TEXT_NODE) {
    const text = node.nodeValue ?? '';
    if (!text.length) return null;
    return { type: 'text', text, marks: marksOf(node) };
  }

  if (!isElement(node)) return null;

  const name = tag(node);
  if (name === 'br') return { type: 'hardBreak' };

  // Marks are carried on their text children, so an inline wrapper adds nothing.
  if (name in MARK_TAGS) {
    const inner = childrenToJson(node);
    return inner.length === 1 ? inner[0] : { type: 'fragment', content: inner };
  }

  const type = BLOCK_TYPE_BY_TAG[name];
  if (!type) return null;

  const attrs: Record<string, string | number> = {};
  if (type === 'heading') attrs.level = Number(name.slice(1));

  const style = node.style;
  if (style.textAlign) attrs.align = style.textAlign;
  if (style.marginLeft) attrs.indent = Math.round(parseFloat(style.marginLeft) / INDENT_STEP_REM);
  if (style.lineHeight) attrs.spacing = style.lineHeight;

  if (name === 'img') {
    attrs.src = node.getAttribute('src') ?? '';
    attrs.alt = node.getAttribute('alt') ?? '';
    return { type, attrs };
  }
  if (name === 'hr') return { type };

  const result: JsonNode = { type };
  if (Object.keys(attrs).length) result.attrs = attrs;

  const content = childrenToJson(node);
  if (content.length) result.content = flattenFragments(content);

  return result;
}

const MARK_TAGS: Record<string, true> = (() => {
  const map: Record<string, true> = {};
  for (const spec of Object.values(MARKS)) map[spec.tag] = true;
  for (const alias of ['b', 'i', 'strike', 'del', 'ins']) map[alias] = true;
  return map;
})();

function childrenToJson(element: Element): JsonNode[] {
  return Array.from(element.childNodes)
    .map(nodeToJson)
    .filter((node): node is JsonNode => node !== null);
}

/** Inline wrappers produce `fragment` placeholders; splice them into the list. */
function flattenFragments(nodes: JsonNode[]): JsonNode[] {
  const output: JsonNode[] = [];
  for (const node of nodes) {
    if (node.type === 'fragment') output.push(...flattenFragments(node.content ?? []));
    else output.push(node);
  }
  return output;
}

/** Every mark wrapping a text node, innermost last. */
function marksOf(node: Node): JsonMark[] | undefined {
  const marks: JsonMark[] = [];
  let current = node.parentElement;

  while (current && !BLOCK_TYPE_BY_TAG[tag(current)]) {
    const name = markNameOf(current);
    if (name) {
      const mark: JsonMark = { type: name };
      const spec = MARKS[name];

      if (spec.style) {
        const value = current.style.getPropertyValue(spec.style);
        if (value) mark.attrs = { value };
      } else if (name === 'link') {
        mark.attrs = { href: current.getAttribute('href') ?? '' };
      }

      marks.unshift(mark);
    }
    current = current.parentElement;
  }

  return marks.length ? marks : undefined;
}

function markNameOf(element: HTMLElement): MarkName | null {
  const name = tag(element);

  for (const [markName, spec] of Object.entries(MARKS) as [MarkName, (typeof MARKS)[MarkName]][]) {
    if (spec.style) {
      if (name === spec.tag && element.style.getPropertyValue(spec.style)) return markName;
      continue;
    }
    if (name === spec.tag || (spec.aliases ?? []).includes(name)) return markName;
  }

  return null;
}

/** JSON back into DOM blocks. */
export function fromJson(doc: JsonDoc | JsonNode[]): DocumentFragment {
  const nodes = Array.isArray(doc) ? doc : (doc.content ?? []);
  const fragment = document.createDocumentFragment();
  for (const node of nodes) {
    const element = jsonToNode(node);
    if (element) fragment.appendChild(element);
  }
  return fragment;
}

const TAG_BY_BLOCK_TYPE: Record<string, string> = (() => {
  const map: Record<string, string> = {};
  for (const [tagName, type] of Object.entries(BLOCK_TYPE_BY_TAG)) {
    if (!(type in map)) map[type] = tagName;
  }
  return map;
})();

function jsonToNode(node: JsonNode): Node | null {
  if (node.type === 'text') {
    let result: Node = document.createTextNode(node.text ?? '');
    for (const mark of node.marks ?? []) {
      const spec = MARKS[mark.type];
      if (!spec) continue;
      const wrapper = document.createElement(spec.tag);
      if (spec.style && mark.attrs?.value) wrapper.style.setProperty(spec.style, mark.attrs.value);
      if (mark.type === 'link' && mark.attrs?.href) {
        wrapper.setAttribute('href', mark.attrs.href);
        wrapper.setAttribute('rel', 'noopener noreferrer nofollow');
      }
      wrapper.appendChild(result);
      result = wrapper;
    }
    return result;
  }

  if (node.type === 'hardBreak') return document.createElement('br');

  const tagName =
    node.type === 'heading'
      ? `h${Math.min(6, Math.max(1, Number(node.attrs?.level ?? 1)))}`
      : TAG_BY_BLOCK_TYPE[node.type];

  if (!tagName) return null;

  const element = document.createElement(tagName);

  if (node.attrs?.align) element.setAttribute('data-nk-align', String(node.attrs.align));
  if (node.attrs?.indent) element.setAttribute('data-nk-indent', String(node.attrs.indent));
  if (node.attrs?.spacing) element.setAttribute('data-nk-spacing', String(node.attrs.spacing));
  if (node.type === 'image') {
    element.setAttribute('src', String(node.attrs?.src ?? ''));
    element.setAttribute('alt', String(node.attrs?.alt ?? ''));
    return element;
  }

  for (const child of node.content ?? []) {
    const rendered = jsonToNode(child);
    if (rendered) element.appendChild(rendered);
  }

  if (!element.firstChild && !['hr', 'img'].includes(tagName)) {
    element.appendChild(document.createElement('br'));
  }

  return element;
}

/** Normalize whatever the host handed us into blocks ready to lay out. */
export function fromValue(value: unknown, mode: 'html' | 'json'): DocumentFragment {
  if (mode === 'json') {
    let parsed: unknown = value;
    if (typeof value === 'string') {
      try {
        parsed = value.trim() ? JSON.parse(value) : emptyDoc();
      } catch {
        // A JSON-mode editor handed HTML: fall back rather than losing content.
        return sanitizeHtml(value);
      }
    }
    if (!parsed || typeof parsed !== 'object') return fromJson(emptyDoc());
    return fromJson(parsed as JsonDoc);
  }

  return sanitizeHtml(typeof value === 'string' ? value : '');
}

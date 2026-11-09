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
import { BORDER_WIDTHS, columnWidths, joinTableParts, setColumnWidths, type BorderWeight } from './tables';
import { makeMarker, renumber as renumberFootnotes, write as writeFootnotes } from './footnotes';
import { DEFAULT_IMAGE_BORDER_COLOR, IMAGE_BORDERS, type ImageBorder } from './images';
import { parseInsets } from './sizing';
import { blockAttribute, BLOCK_SUGGESTION_KINDS, parseBlockSuggestion, parseFormat, writeBlockSuggestion } from './suggestions';

/** The colour of a table border given a weight but no colour. */
const DEFAULT_BORDER_COLOR = '#999999';

export interface JsonMark {
  /** A formatting mark, or review: a `comment` anchor, a suggested `insertion` or `deletion`. */
  type: MarkName | 'comment' | 'insertion' | 'deletion' | 'formatChange';
  attrs?: Record<string, string>;
}

export interface JsonNode {
  type: string;
  attrs?: Record<string, string | number | boolean>;
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
 * user is editing. `portable` writes footnotes the way HTML shows them (numbered
 * links and a list of notes at the end); JSON keeps them as markers.
 */
export function readDocument(root: HTMLElement, portable = true): HTMLElement {
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
  if (portable) writeFootnotes(output);

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

    if (tag(first) === 'table') joinTableParts(first, element);
    else {
      while (element.firstChild) first.appendChild(element.firstChild);
      element.remove();
    }
  }

  for (const element of seen.values()) element.removeAttribute(PART_ATTR);
  // The header a page repeated on a table's next part is the page's, not the document's.
  container.querySelectorAll('[data-nk-repeat]').forEach((row) => row.remove());
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

    // A page break is kept, the way print styles read it: before the next page,
    // the next right (odd) or left (even) one, or the next column.
    if (element.hasAttribute('data-nk-break')) {
      const kind = element.getAttribute('data-nk-break');
      const portable = element.ownerDocument!.createElement('div');
      portable.setAttribute('data-break', kind === 'odd' || kind === 'even' || kind === 'column' ? kind : 'page');
      portable.style.breakBefore = kind === 'odd' ? 'right' : kind === 'even' ? 'left' : kind === 'column' ? 'column' : 'page';
      element.replaceWith(portable);
      continue;
    }

    element.removeAttribute('contenteditable');

    if (tag(element) === 'table') materializeTable(element);
    if (tag(element) === 'figure') materializeFigure(element);
    if (tag(element) === 'ol' && element.hasAttribute('data-numbering')) materializeNumbering(element);

    // Every `data-nk-*` attribute is editor bookkeeping — block ids, split
    // markers, the image-load flag. Stripping the whole namespace keeps the
    // saved value clean without needing a list that must be kept in step.
    for (const attr of Array.from(element.attributes)) {
      if (attr.name.startsWith('data-nk-')) element.removeAttribute(attr.name);
    }

    if (!element.getAttribute('style')) element.removeAttribute('style');
  }
}

/**
 * A table's border, as real inline borders on its cells.
 *
 * The editor shows it through custom properties; those mean nothing outside
 * it, so they go and every cell gets the border written out.
 */
function materializeTable(table: HTMLElement): void {
  table.style.removeProperty('--nk-table-border-color');
  table.style.removeProperty('--nk-table-border-width');

  const weight = table.getAttribute('data-border') as BorderWeight | null;
  const color = table.getAttribute('data-border-color');
  if (!weight && !color) return;

  const width = BORDER_WIDTHS[weight && weight in BORDER_WIDTHS ? weight : 'thin'];
  table.style.borderCollapse = 'collapse';
  for (const cell of Array.from(table.querySelectorAll<HTMLElement>(':scope > tr > *, :scope > * > tr > *'))) {
    cell.style.border = width ? `${width}px solid ${color ?? DEFAULT_BORDER_COLOR}` : 'none';
  }
}

/** How an image's wrap and width read outside the editor. */
function materializeFigure(figure: HTMLElement): void {
  const wrap = figure.getAttribute('data-wrap');
  const width = figure.style.width;

  if (wrap === 'left') {
    figure.style.float = 'left';
    figure.style.margin = '0.25em 1em 0.5em 0';
  } else if (wrap === 'right') {
    figure.style.float = 'right';
    figure.style.margin = '0.25em 0 0.5em 1em';
  } else if (width) {
    const align = figure.getAttribute('data-nk-align');
    figure.style.marginLeft = align === 'left' ? '0' : 'auto';
    figure.style.marginRight = align === 'right' ? '0' : 'auto';
  }

  const image = figure.querySelector<HTMLElement>(':scope > img');
  if (width) image?.style.setProperty('width', '100%');

  // The border goes on the picture itself, inside its width.
  figure.style.removeProperty('--nk-image-border-color');
  const weight = figure.getAttribute('data-border') as ImageBorder | null;
  if (image && weight && weight in IMAGE_BORDERS) {
    image.style.border = `${IMAGE_BORDERS[weight]}px solid ${figure.getAttribute('data-border-color') ?? DEFAULT_IMAGE_BORDER_COLOR}`;
    image.style.boxSizing = 'border-box';
  }
}

/**
 * A list's numbering, as list styles any reader knows: letters and roman
 * numerals level by level. "1)" and "1.1" need the editor's own styles; read
 * elsewhere, they count 1. 2. 3.
 */
const NUMBERING_LEVELS: Record<string, string[]> = {
  'upper-alpha': ['upper-alpha', 'lower-alpha', 'lower-roman'],
  'lower-alpha': ['lower-alpha', 'lower-roman', 'decimal'],
  'upper-roman': ['upper-roman', 'upper-alpha', 'decimal'],
  'lower-roman': ['lower-roman', 'lower-alpha', 'decimal'],
};

function materializeNumbering(list: HTMLElement): void {
  const levels = NUMBERING_LEVELS[list.getAttribute('data-numbering') ?? ''];
  if (!levels) return;
  const paint = (ol: HTMLElement, depth: number) => {
    ol.style.listStyleType = levels[Math.min(depth, levels.length - 1)];
    for (const nested of Array.from(ol.querySelectorAll<HTMLElement>(':scope > li > ol'))) paint(nested, depth + 1);
  };
  paint(list, 0);
}

/** Drop the trailing empty paragraphs pagination leaves behind. */
function dropEmptyTrailing(container: HTMLElement): void {
  let last = container.lastElementChild;
  while (last && container.children.length > 1) {
    const isBlank =
      !last.matches('table, hr, figure, nav') &&
      !last.querySelector('img, hr, table') &&
      (last.textContent ?? '').trim().length === 0;
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
  const container = readDocument(root, false);
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
  nav: 'tableOfContents',
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

  // Chips and footnotes are atoms: what they stand for, not their text.
  if (name === 'span' && node.hasAttribute('data-chip')) {
    const kind = node.getAttribute('data-chip') === 'date' ? 'date' : 'person';
    const label = (node.textContent ?? '').replace(/^@/, '');
    return kind === 'date'
      ? { type: 'mention', attrs: { kind, date: node.getAttribute('data-date') ?? '', label } }
      : { type: 'mention', attrs: { kind, id: node.getAttribute('data-id') ?? '', label, email: node.getAttribute('data-email') ?? '' } };
  }
  if (name === 'span' && node.hasAttribute('data-footnote')) {
    return { type: 'footnote', attrs: { id: node.getAttribute('data-footnote') ?? '', note: node.getAttribute('data-note') ?? '' } };
  }
  if (name === 'nav') {
    return { type: 'tableOfContents', attrs: { style: node.getAttribute('data-toc') === 'links' ? 'links' : 'pages' } };
  }
  if (name === 'div' && node.hasAttribute('data-break')) {
    return { type: 'pageBreak', attrs: { kind: node.getAttribute('data-break') ?? 'page' } };
  }

  // Table sections carry nothing of their own: their rows go straight in the table.
  if (name === 'thead' || name === 'tbody' || name === 'tfoot') {
    return { type: 'fragment', content: childrenToJson(node) };
  }

  // Marks are carried on their text children, so an inline wrapper adds nothing.
  if (name in MARK_TAGS) {
    const inner = childrenToJson(node);
    return inner.length === 1 ? inner[0] : { type: 'fragment', content: inner };
  }

  let type = BLOCK_TYPE_BY_TAG[name];
  if (!type) return null;
  if (name === 'ul' && node.hasAttribute('data-checklist')) type = 'taskList';
  if (name === 'li' && node.hasAttribute('data-checked')) type = 'taskItem';

  const attrs: Record<string, string | number | boolean> = {};
  if (type === 'taskItem') attrs.checked = node.getAttribute('data-checked') === 'true';
  if (type === 'heading') attrs.level = Number(name.slice(1));
  if (type === 'heading' && node.id) attrs.id = node.id;
  if (name === 'ol' && node.getAttribute('data-numbering')) attrs.numbering = node.getAttribute('data-numbering')!;
  // Suggestions made to the block itself: suggestSplit, suggestJoin, suggestStyle.
  for (const kind of BLOCK_SUGGESTION_KINDS) {
    const value = node.getAttribute(blockAttribute(kind));
    if (value) attrs[`suggest${kind[0].toUpperCase()}${kind.slice(1)}`] = value;
  }

  const style = node.style;
  if (style.textAlign) attrs.align = style.textAlign;
  const indent = name === 'figure' ? 0 : Math.round(parseFloat(style.marginLeft) / INDENT_STEP_REM);
  if (indent > 0) attrs.indent = indent;
  if (style.lineHeight) attrs.spacing = style.lineHeight;

  if (name === 'img') {
    attrs.src = node.getAttribute('src') ?? '';
    attrs.alt = node.getAttribute('alt') ?? '';
    if (node.getAttribute('data-original')) {
      attrs.original = node.getAttribute('data-original')!;
      if (node.getAttribute('data-crop')) attrs.crop = node.getAttribute('data-crop')!;
      if (node.getAttribute('data-rotate')) attrs.rotate = Number(node.getAttribute('data-rotate'));
    }
    return { type, attrs };
  }
  if (name === 'td' || name === 'th') {
    const colspan = Number(node.getAttribute('colspan') ?? 1);
    const rowspan = Number(node.getAttribute('rowspan') ?? 1);
    if (colspan > 1) attrs.colspan = colspan;
    if (rowspan > 1) attrs.rowspan = rowspan;
    if (style.backgroundColor) attrs.background = style.backgroundColor;
  }
  if (name === 'table') {
    const widths = columnWidths(node);
    if (widths) attrs.widths = widths.join(',');
    if (node.getAttribute('data-border')) attrs.border = node.getAttribute('data-border')!;
    if (node.getAttribute('data-border-color')) attrs.borderColor = node.getAttribute('data-border-color')!;
  }
  if (name === 'figure') {
    if (node.getAttribute('data-wrap')) attrs.wrap = node.getAttribute('data-wrap')!;
    if (style.width.endsWith('%')) attrs.width = parseFloat(style.width);
    if (node.getAttribute('data-border')) attrs.border = node.getAttribute('data-border')!;
    if (node.getAttribute('data-border-color')) attrs.borderColor = node.getAttribute('data-border-color')!;
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
    if (current.hasAttribute('data-comment')) {
      marks.unshift({ type: 'comment', attrs: { id: current.getAttribute('data-comment') ?? '' } });
    }
    if (current.hasAttribute('data-suggest') && current.hasAttribute('data-format')) {
      marks.unshift({
        type: 'formatChange',
        attrs: {
          id: current.getAttribute('data-suggest') ?? '',
          author: current.getAttribute('data-author') ?? '',
          time: current.getAttribute('data-time') ?? '',
          changes: current.getAttribute('data-format') ?? '[]',
        },
      });
    } else if (current.hasAttribute('data-suggest')) {
      marks.unshift({
        type: tag(current) === 'ins' ? 'insertion' : 'deletion',
        attrs: {
          id: current.getAttribute('data-suggest') ?? '',
          author: current.getAttribute('data-author') ?? '',
          time: current.getAttribute('data-time') ?? '',
        },
      });
    }
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
  if (element.hasAttribute('data-suggest')) return null;

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
  renumberFootnotes(fragment);
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
      const review = reviewWrapper(mark);
      if (review) {
        review.appendChild(result);
        result = review;
        continue;
      }
      const spec = MARKS[mark.type as MarkName];
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

  if (node.type === 'mention') {
    const chip = document.createElement('span');
    const attrs = node.attrs ?? {};
    if (attrs.kind === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(String(attrs.date ?? ''))) {
      chip.setAttribute('data-chip', 'date');
      chip.setAttribute('data-date', String(attrs.date));
      chip.textContent = String(attrs.label ?? attrs.date);
    } else {
      chip.setAttribute('data-chip', 'person');
      chip.setAttribute('data-id', String(attrs.id ?? ''));
      if (attrs.email) chip.setAttribute('data-email', String(attrs.email));
      chip.textContent = `@${String(attrs.label ?? '')}`;
    }
    return chip;
  }
  if (node.type === 'footnote') {
    return makeMarker(document, String(node.attrs?.id ?? ''), String(node.attrs?.note ?? ''));
  }
  if (node.type === 'pageBreak') {
    const kind = node.attrs?.kind;
    const marker = document.createElement('div');
    marker.setAttribute('data-nk-break', kind === 'odd' || kind === 'even' || kind === 'column' ? kind : 'page');
    marker.setAttribute('contenteditable', 'false');
    return marker;
  }
  if (node.type === 'tableOfContents') {
    const toc = document.createElement('nav');
    toc.setAttribute('data-toc', node.attrs?.style === 'links' ? 'links' : 'pages');
    return toc;
  }

  const tagName =
    node.type === 'heading'
      ? `h${Math.min(6, Math.max(1, Number(node.attrs?.level ?? 1)))}`
      : node.type === 'taskList'
        ? 'ul'
        : node.type === 'taskItem'
          ? 'li'
          : TAG_BY_BLOCK_TYPE[node.type];

  if (!tagName) return null;

  const element = document.createElement(tagName);
  if (node.type === 'taskList') element.setAttribute('data-checklist', '');
  if (node.type === 'taskItem') element.setAttribute('data-checked', node.attrs?.checked === true || node.attrs?.checked === 'true' ? 'true' : 'false');

  if (node.type === 'heading' && /^h-[a-z0-9][a-z0-9-]*$/.test(String(node.attrs?.id ?? ''))) element.id = String(node.attrs!.id);
  for (const kind of BLOCK_SUGGESTION_KINDS) {
    const suggestion = parseBlockSuggestion(String(node.attrs?.[`suggest${kind[0].toUpperCase()}${kind.slice(1)}`] ?? ''), kind);
    if (suggestion) element.setAttribute(blockAttribute(kind), writeBlockSuggestion(suggestion));
  }
  if (node.type === 'orderedList' && ['paren', 'outline', 'upper-alpha', 'lower-alpha', 'upper-roman', 'lower-roman'].includes(String(node.attrs?.numbering))) {
    element.setAttribute('data-numbering', String(node.attrs!.numbering));
  }
  if (node.attrs?.align) element.setAttribute('data-nk-align', String(node.attrs.align));
  if (node.attrs?.indent) element.setAttribute('data-nk-indent', String(node.attrs.indent));
  if (node.attrs?.spacing) element.setAttribute('data-nk-spacing', String(node.attrs.spacing));
  if (node.type === 'image') {
    element.setAttribute('src', String(node.attrs?.src ?? ''));
    element.setAttribute('alt', String(node.attrs?.alt ?? ''));
    if (node.attrs?.original) {
      element.setAttribute('data-original', String(node.attrs.original));
      if (parseInsets(String(node.attrs.crop ?? ''))) element.setAttribute('data-crop', String(node.attrs.crop));
      if ([90, 180, 270].includes(Number(node.attrs.rotate))) element.setAttribute('data-rotate', String(node.attrs.rotate));
    }
    return element;
  }
  applyObjectAttrs(element, node);

  for (const child of node.content ?? []) {
    const rendered = jsonToNode(child);
    if (rendered) element.appendChild(rendered);
  }

  if (!element.firstChild && !['hr', 'img'].includes(tagName)) {
    element.appendChild(document.createElement('br'));
  }

  return element;
}

/** Spans, fills, borders, column widths and image wraps, checked on the way in. */
function applyObjectAttrs(element: HTMLElement, node: JsonNode): void {
  const attrs = node.attrs ?? {};

  if (node.type === 'tableCell' || node.type === 'tableHeader') {
    for (const name of ['colspan', 'rowspan'] as const) {
      const value = Math.floor(Number(attrs[name] ?? 1));
      if (value > 1 && value < 1000) element.setAttribute(name, String(value));
    }
    if (typeof attrs.background === 'string') element.style.backgroundColor = attrs.background;
  }

  if (node.type === 'table') {
    if (['none', 'medium', 'thick'].includes(String(attrs.border))) element.setAttribute('data-border', String(attrs.border));
    if (/^#[0-9a-f]{3,8}$/i.test(String(attrs.borderColor ?? ''))) element.setAttribute('data-border-color', String(attrs.borderColor));
    const widths = String(attrs.widths ?? '').split(',').map(Number).filter((width) => width > 0);
    if (widths.length) setColumnWidths(element, widths);
  }

  if (node.type === 'figure') {
    if (attrs.wrap === 'left' || attrs.wrap === 'right') element.setAttribute('data-wrap', attrs.wrap);
    const width = Number(attrs.width);
    if (width >= 10 && width <= 100) element.style.width = `${width}%`;
    if (['thin', 'medium', 'thick'].includes(String(attrs.border))) element.setAttribute('data-border', String(attrs.border));
    if (/^#[0-9a-f]{3,8}$/i.test(String(attrs.borderColor ?? ''))) element.setAttribute('data-border-color', String(attrs.borderColor));
  }
}

/** The element a review mark stands for, or null for a formatting mark. */
function reviewWrapper(mark: JsonMark): HTMLElement | null {
  const id = String(mark.attrs?.id ?? '');
  if (mark.type === 'comment' && /^c-[a-z0-9-]+$/i.test(id)) {
    const anchor = document.createElement('span');
    anchor.setAttribute('data-comment', id);
    return anchor;
  }
  if (mark.type === 'formatChange' && /^s-[a-z0-9-]+$/i.test(id)) {
    const changes = parseFormat(String(mark.attrs?.changes ?? ''));
    if (!changes.length) return null;
    const span = document.createElement('span');
    span.setAttribute('data-suggest', id);
    span.setAttribute('data-author', String(mark.attrs?.author ?? ''));
    span.setAttribute('data-time', String(mark.attrs?.time ?? ''));
    span.setAttribute('data-format', JSON.stringify(changes));
    return span;
  }
  if ((mark.type === 'insertion' || mark.type === 'deletion') && /^s-[a-z0-9-]+$/i.test(id)) {
    const element = document.createElement(mark.type === 'insertion' ? 'ins' : 'del');
    element.setAttribute('data-suggest', id);
    element.setAttribute('data-author', String(mark.attrs?.author ?? ''));
    element.setAttribute('data-time', String(mark.attrs?.time ?? ''));
    return element;
  }
  return null;
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

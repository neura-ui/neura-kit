/**
 * Paste and input sanitization.
 *
 * Everything entering the document — a paste from Word, a `value` handed down
 * from Livewire, a drag-and-drop — goes through here first. The schema tables
 * are the allowlist: an element that maps to no block, container, mark or void
 * is unwrapped (keeping its text) or dropped outright.
 */

import {
  ALLOWED_ATTRS,
  ALLOWED_STYLES,
  BLOCK_TAGS,
  TAG_STYLES,
  CONTAINER_TAGS,
  MARKS,
  SAFE_SCHEMES,
  TAG_TO_MARK,
  VOID_TAGS,
} from './schema';
import { lift as liftFootnotes } from './footnotes';
import { parseInsets } from './sizing';
import { blockAttribute, BLOCK_SUGGESTION_KINDS, parseBlockSuggestion, parseFormat, writeBlockSuggestion } from './suggestions';

/** How a numbered list may count (`blocks.NUMBERINGS`, kept here to stay free of the DOM commands). */
const NUMBERINGS = ['paren', 'outline', 'upper-alpha', 'lower-alpha', 'upper-roman', 'lower-roman'];

/** Tags kept as-is; anything else is unwrapped or removed. */
const KEEP = new Set<string>([
  ...BLOCK_TAGS,
  ...CONTAINER_TAGS,
  ...VOID_TAGS,
  ...Object.values(MARKS).map((spec) => spec.tag),
  ...Object.keys(TAG_TO_MARK),
  'figure',
  'figcaption',
  'br',
  // The comment threads' store, taken out as soon as a document loads.
  'aside',
]);

/** Tags whose *contents* are dropped too, not just the wrapper. */
const DROP = new Set([
  'script',
  'style',
  'iframe',
  'object',
  'embed',
  'link',
  'meta',
  'noscript',
  'form',
  'input',
  'button',
  'select',
  'textarea',
  'svg',
  'math',
  'template',
  'title',
  'head',
]);

/** True when a URL is safe to keep in an `href` or `src`. */
export function isSafeUrl(value: string): boolean {
  const trimmed = value.trim();
  if (!trimmed) return false;

  // Relative and protocol-relative URLs inherit the page's origin — fine.
  if (/^[/#?]/.test(trimmed)) return true;
  if (!/^[a-z][a-z0-9+.-]*:/i.test(trimmed)) return true;

  try {
    const url = new URL(trimmed, 'https://placeholder.invalid');
    return SAFE_SCHEMES.includes(url.protocol);
  } catch {
    return false;
  }
}

/** Data URLs are allowed for images only, and only for real image types. */
function isSafeImageSrc(value: string): boolean {
  const trimmed = value.trim();
  if (/^data:image\/(png|jpe?g|gif|webp|avif);base64,/i.test(trimmed)) return true;
  if (/^blob:/i.test(trimmed)) return true;
  return isSafeUrl(trimmed);
}

/** Keep only the CSS properties the schema recognises. */
function cleanStyle(element: HTMLElement): void {
  const style = element.getAttribute('style');
  if (!style) return;

  const own = TAG_STYLES[element.tagName.toLowerCase()] ?? [];
  const kept: string[] = [];
  for (const declaration of style.split(';')) {
    const [rawProp, ...rest] = declaration.split(':');
    const prop = rawProp?.trim().toLowerCase();
    const value = rest.join(':').trim();
    if (!prop || !value) continue;
    if (own.includes(prop)) {
      if (prop === 'width' && !/^\d{1,3}(\.\d+)?%$/.test(value)) continue;
    } else if (!ALLOWED_STYLES.has(prop)) continue;
    // `url(...)` in a style can fetch a remote asset; nothing we keep needs it.
    if (/url\s*\(/i.test(value) || /expression\s*\(/i.test(value)) continue;
    kept.push(`${prop}: ${value}`);
  }

  if (kept.length) element.setAttribute('style', kept.join('; '));
  else element.removeAttribute('style');
}

/** Strip attributes the tag is not allowed to carry. */
function cleanAttributes(element: HTMLElement): void {
  const name = element.tagName.toLowerCase();
  const allowed = ALLOWED_ATTRS[name] ?? [];

  for (const attr of Array.from(element.attributes)) {
    const attrName = attr.name.toLowerCase();

    // Editor bookkeeping is ours, not the paste source's.
    if (attrName.startsWith('data-nk-')) {
      element.removeAttribute(attr.name);
      continue;
    }
    if (attrName === 'style') continue; // handled by cleanStyle
    if (!allowed.includes(attrName)) {
      element.removeAttribute(attr.name);
    }
  }

  cleanStyle(element);

  if (name === 'a') {
    const href = element.getAttribute('href') ?? '';
    if (!isSafeUrl(href)) element.removeAttribute('href');
    else element.setAttribute('rel', 'noopener noreferrer nofollow');
  }

  if (name === 'img') {
    const src = element.getAttribute('src') ?? '';
    if (!isSafeImageSrc(src)) element.remove();
    // An edit is only kept with an original it can be redone from.
    if (!isSafeImageSrc(element.getAttribute('data-original') ?? '')) {
      for (const attr of ['data-original', 'data-crop', 'data-rotate']) element.removeAttribute(attr);
    }
    if (element.hasAttribute('data-crop') && !parseInsets(element.getAttribute('data-crop'))) element.removeAttribute('data-crop');
    if (!['90', '180', '270'].includes(element.getAttribute('data-rotate') ?? '')) element.removeAttribute('data-rotate');
  }

  // Wrap, border weight and colour come from short lists; anything else goes.
  if (name === 'figure') {
    if (!['left', 'right'].includes(element.getAttribute('data-wrap') ?? '')) element.removeAttribute('data-wrap');
    if (!['thin', 'medium', 'thick'].includes(element.getAttribute('data-border') ?? '')) element.removeAttribute('data-border');
    if (!/^#[0-9a-f]{3,8}$/i.test(element.getAttribute('data-border-color') ?? '')) element.removeAttribute('data-border-color');
    // The margins a saved figure carries only place it outside the editor;
    // read back, the left one would pass for an indent.
    element.style.removeProperty('margin-left');
    element.style.removeProperty('margin-right');
    if (!element.getAttribute('style')) element.removeAttribute('style');
  }
  if (name === 'table') {
    if (!['none', 'medium', 'thick'].includes(element.getAttribute('data-border') ?? '')) element.removeAttribute('data-border');
    if (!/^#[0-9a-f]{3,8}$/i.test(element.getAttribute('data-border-color') ?? '')) element.removeAttribute('data-border-color');
  }

  // Chips and footnote markers are atoms: no style of their own, known kinds only.
  if (element.hasAttribute('data-chip')) {
    if (!['person', 'date'].includes(element.getAttribute('data-chip') ?? '')) {
      for (const attr of ['data-chip', 'data-id', 'data-email', 'data-date']) element.removeAttribute(attr);
    } else {
      element.removeAttribute('style');
      if (!/^\d{4}-\d{2}-\d{2}$/.test(element.getAttribute('data-date') ?? '')) element.removeAttribute('data-date');
    }
  }
  if (element.hasAttribute('data-footnote')) element.removeAttribute('style');
  if (element.hasAttribute('data-comment') && !/^c-[a-z0-9-]+$/i.test(element.getAttribute('data-comment') ?? '')) {
    element.removeAttribute('data-comment');
  }
  if ((name === 'ins' || name === 'del') && !/^s-[a-z0-9-]+$/i.test(element.getAttribute('data-suggest') ?? '')) {
    for (const attr of ['data-suggest', 'data-author', 'data-time']) element.removeAttribute(attr);
  }
  // A suggested formatting change needs its id and what it changed.
  if (name === 'span' && (element.hasAttribute('data-suggest') || element.hasAttribute('data-format'))) {
    const valid = /^s-[a-z0-9-]+$/i.test(element.getAttribute('data-suggest') ?? '') && parseFormat(element.getAttribute('data-format')).length > 0;
    if (valid) element.setAttribute('data-format', JSON.stringify(parseFormat(element.getAttribute('data-format'))));
    else for (const attr of ['data-suggest', 'data-author', 'data-time', 'data-format']) element.removeAttribute(attr);
  }
  for (const kind of BLOCK_SUGGESTION_KINDS) {
    const attr = blockAttribute(kind);
    if (!element.hasAttribute(attr)) continue;
    const suggestion = parseBlockSuggestion(element.getAttribute(attr), kind);
    if (suggestion) element.setAttribute(attr, writeBlockSuggestion(suggestion));
    else element.removeAttribute(attr);
  }
  if (name === 'aside' && !element.hasAttribute('data-comments')) element.remove();
  if (/^h[1-6]$/.test(name) && !/^h-[a-z0-9][a-z0-9-]*$/.test(element.id)) element.removeAttribute('id');
  if (name === 'ol' && !NUMBERINGS.includes(element.getAttribute('data-numbering') as never)) element.removeAttribute('data-numbering');
  if (name === 'nav') element.setAttribute('data-toc', element.getAttribute('data-toc') === 'links' ? 'links' : 'pages');

  // Checklist markers carry no free text: an empty flag and a true / false state.
  if (name === 'ul' && element.hasAttribute('data-checklist')) element.setAttribute('data-checklist', '');
  if (name === 'li' && element.hasAttribute('data-checked')) {
    element.setAttribute('data-checked', element.getAttribute('data-checked') === 'true' ? 'true' : 'false');
  }
}

/**
 * Word and Google Docs wrap runs in `<span style="font-weight:700">` rather
 * than `<strong>`. Convert those to real marks so the toolbar can see them.
 */
function liftStyleMarks(element: HTMLElement): void {
  if (element.tagName.toLowerCase() !== 'span') return;

  const doc = element.ownerDocument;
  const weight = element.style.fontWeight;
  const style = element.style.fontStyle;
  const decoration = element.style.textDecoration;

  const wrappers: string[] = [];
  if (weight && (weight === 'bold' || Number(weight) >= 600)) wrappers.push('strong');
  if (style === 'italic') wrappers.push('em');
  if (decoration.includes('underline')) wrappers.push('u');
  if (decoration.includes('line-through')) wrappers.push('s');

  if (wrappers.length === 0) return;

  element.style.removeProperty('font-weight');
  element.style.removeProperty('font-style');
  element.style.removeProperty('text-decoration');
  if (!element.getAttribute('style')) element.removeAttribute('style');

  let inner: HTMLElement = element;
  for (const wrapper of wrappers) {
    const node = doc.createElement(wrapper);
    while (inner.firstChild) node.appendChild(inner.firstChild);
    inner.appendChild(node);
    inner = node;
  }
}

/** Recursively clean a subtree in place. */
function walk(node: Node): void {
  const children = Array.from(node.childNodes);

  for (const child of children) {
    if (child.nodeType === Node.COMMENT_NODE) {
      child.parentNode?.removeChild(child);
      continue;
    }

    if (child.nodeType !== Node.ELEMENT_NODE) continue;

    const element = child as HTMLElement;
    const name = element.tagName.toLowerCase();

    if (DROP.has(name)) {
      element.remove();
      continue;
    }

    // A saved page break comes back as the editor's marker.
    if (name === 'div' && element.hasAttribute('data-break')) {
      const kind = element.getAttribute('data-break');
      for (const attr of Array.from(element.attributes)) element.removeAttribute(attr.name);
      element.setAttribute('data-nk-break', kind === 'odd' || kind === 'even' || kind === 'column' ? kind : 'page');
      element.setAttribute('contenteditable', 'false');
      element.replaceChildren();
      continue;
    }

    if (!KEEP.has(name)) {
      // Unknown wrapper (`<div>`, `<section>`, `<font>`): keep the text, lose the tag.
      walk(element);
      const parent = element.parentNode;
      if (parent) {
        while (element.firstChild) parent.insertBefore(element.firstChild, element);
        parent.removeChild(element);
      }
      continue;
    }

    cleanAttributes(element);
    if (!element.isConnected) continue;

    liftStyleMarks(element);
    walk(element);
  }
}

/**
 * Clean an HTML string and return the resulting nodes.
 *
 * Parsing happens in an inert document, so `<img onerror>` and friends never
 * get a chance to run even before the attribute is stripped.
 */
export function sanitizeHtml(html: string): DocumentFragment {
  const parsed = new DOMParser().parseFromString(html, 'text/html');
  walk(parsed.body);
  liftFootnotes(parsed.body);

  const fragment = document.createDocumentFragment();
  while (parsed.body.firstChild) fragment.appendChild(parsed.body.firstChild);
  return fragment;
}

/** Plain text into paragraphs, splitting on blank lines the way a paste should. */
export function textToFragment(text: string): DocumentFragment {
  const fragment = document.createDocumentFragment();

  for (const line of text.replace(/\r\n?/g, '\n').split('\n')) {
    const paragraph = document.createElement('p');
    if (line.length) paragraph.textContent = line;
    else paragraph.appendChild(document.createElement('br'));
    fragment.appendChild(paragraph);
  }

  return fragment;
}

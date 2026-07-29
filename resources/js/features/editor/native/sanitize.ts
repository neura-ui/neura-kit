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
  CONTAINER_TAGS,
  MARKS,
  SAFE_SCHEMES,
  TAG_TO_MARK,
  VOID_TAGS,
} from './schema';

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

  const kept: string[] = [];
  for (const declaration of style.split(';')) {
    const [rawProp, ...rest] = declaration.split(':');
    const prop = rawProp?.trim().toLowerCase();
    const value = rest.join(':').trim();
    if (!prop || !value) continue;
    if (!ALLOWED_STYLES.has(prop)) continue;
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

/**
 * Tables of contents.
 *
 * A table of contents is a `<nav data-toc>` of links to the document's
 * headings, rebuilt whenever they change. Its entries are saved with it, so
 * the HTML reads right anywhere; in the editor they also show the page each
 * heading is on (`pages`) or read as plain links (`links`).
 *
 * Headings get an `id` to link to only once a table of contents exists.
 */

import { anchorsFor } from './anchors';
import { blockId, blockParts } from './selection';

export type TocStyle = 'pages' | 'links';

/** Heading levels a table of contents lists. Title and subtitle are left out. */
const LISTED = '[data-nk-content] :is(h1, h2, h3, h4):not([data-nk-style])';

export function tocsIn(root: HTMLElement): HTMLElement[] {
  return Array.from(root.querySelectorAll<HTMLElement>('[data-nk-content] nav[data-toc]'));
}

export function styleOf(toc: HTMLElement): TocStyle {
  return toc.getAttribute('data-toc') === 'links' ? 'links' : 'pages';
}

/** A new, empty table of contents; its entries come with the next sync. */
export function makeToc(doc: Document, style: TocStyle): HTMLElement {
  const toc = doc.createElement('nav');
  toc.setAttribute('data-toc', style);
  toc.setAttribute('contenteditable', 'false');
  blockId(toc);
  return toc;
}

/**
 * Bring every table of contents in step with the headings. Returns true when
 * anything was rewritten, so the caller knows the layout may have moved.
 */
export function syncTocs(root: HTMLElement, hint: string): boolean {
  const tocs = tocsIn(root);
  if (!tocs.length) return false;

  // A heading split across two pages is one heading: its first part holds the id.
  const textOf = (heading: HTMLElement): string => {
    const id = heading.getAttribute('data-nk-id');
    const parts = id && heading.hasAttribute('data-nk-part') ? blockParts(root, id) : [heading];
    return parts.map(wordsOf).join('').trim().replace(/\s+/g, ' ');
  };
  const headings = Array.from(root.querySelectorAll<HTMLElement>(LISTED)).filter(
    (heading) => Number(heading.getAttribute('data-nk-part') ?? 0) === 0 && textOf(heading) !== ''
  );
  const anchors = anchorsFor(headings.map((heading) => ({ text: textOf(heading), current: heading.id || null })));

  let changed = false;
  headings.forEach((heading, index) => {
    if (heading.id !== anchors[index]) {
      heading.id = anchors[index];
      changed = true;
    }
  });

  const entries = headings.map((heading, index) => ({
    anchor: anchors[index],
    level: Number(heading.tagName.slice(1)),
    text: textOf(heading),
  }));
  const signature = JSON.stringify(entries);

  for (const toc of tocs) {
    if (toc.getAttribute('contenteditable') !== 'false') toc.setAttribute('contenteditable', 'false');
    if (toc.getAttribute('data-nk-hint') !== hint) toc.setAttribute('data-nk-hint', hint);
    if (toc.getAttribute('data-nk-sig') === signature) continue;

    const doc = toc.ownerDocument!;
    toc.replaceChildren(
      ...entries.map((entry) => {
        const link = doc.createElement('a');
        link.setAttribute('href', `#${entry.anchor}`);
        link.setAttribute('data-level', String(entry.level));
        link.textContent = entry.text;
        return link;
      })
    );
    toc.setAttribute('data-nk-sig', signature);
    changed = true;
  }

  return changed;
}

/** Each entry learns the page its heading sits on, for the page-number style. */
export function paintTocPages(root: HTMLElement): void {
  const tocs = tocsIn(root);
  if (!tocs.length) return;

  const pages = Array.from(root.querySelectorAll<HTMLElement>('[data-nk-page]'));
  for (const toc of tocs) {
    toc.querySelectorAll<HTMLAnchorElement>('a[href^="#"]').forEach((link) => {
      const heading = headingFor(root, link.getAttribute('href')!.slice(1));
      const page = heading?.closest<HTMLElement>('[data-nk-page]');
      const number = page ? String(pages.indexOf(page) + 1) : '';
      if (link.getAttribute('data-nk-toc-page') !== number) link.setAttribute('data-nk-toc-page', number);
    });
  }
}

/** A heading's words, without the numbers of the footnotes it carries. */
function wordsOf(element: HTMLElement): string {
  const walker = element.ownerDocument!.createTreeWalker(element, NodeFilter.SHOW_TEXT, {
    acceptNode: (node) => (node.parentElement?.closest('[data-footnote]') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT),
  });
  let text = '';
  for (let node = walker.nextNode(); node; node = walker.nextNode()) text += node.nodeValue ?? '';
  return text;
}

export function headingFor(root: HTMLElement, anchor: string): HTMLElement | null {
  if (!anchor) return null;
  return root.querySelector<HTMLElement>(`[data-nk-content] [id="${CSS.escape(anchor)}"]`);
}

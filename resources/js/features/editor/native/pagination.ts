/**
 * The page flow engine.
 *
 * The document is one contenteditable region containing fixed-height page
 * hosts. After every change this measures each page and moves blocks across the
 * boundary until nothing overflows — forward when text was added, backward when
 * it was deleted — so sheets appear and disappear on their own.
 *
 * Two rules keep it stable:
 *
 *  - Reflow always starts from a clean slate. Blocks the engine previously cut
 *    in half are re-joined first, so a split is never computed from an already
 *    split block and the result does not drift as the user types.
 *  - The caret is captured logically (block id + character offset) before any
 *    node moves and restored after, because every `Range` into a moved block is
 *    dead the moment it is re-parented.
 */

import { isEmptyBlock, isVoid, tag } from './dom';
import { blockId, PART_ATTR, save as saveSelection, restore as restoreSelection } from './selection';

export type PageSize = 'a4' | 'letter' | 'legal';
export type Orientation = 'portrait' | 'landscape';

/** Physical page sizes in CSS pixels at 96dpi. */
export const PAGE_SIZES: Record<PageSize, { width: number; height: number }> = {
  a4: { width: 794, height: 1123 }, // 210 × 297 mm
  letter: { width: 816, height: 1056 }, // 8.5 × 11 in
  legal: { width: 816, height: 1344 }, // 8.5 × 14 in
};

export interface Margins {
  top: number;
  right: number;
  bottom: number;
  left: number;
}

/** One inch on every side, the default in every word processor. */
export const DEFAULT_MARGINS: Margins = { top: 96, right: 96, bottom: 96, left: 96 };

export interface PageOptions {
  size: PageSize;
  orientation: Orientation;
  margins: Margins;
}

/** Blocks that can be cut mid-way across a page boundary. */
const SPLITTABLE = new Set(['p', 'blockquote', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);

/** Tolerance in px — sub-pixel layout noise must not trigger a reflow. */
const SLACK = 1;

/**
 * Hard ceiling on pages produced by one reflow.
 *
 * If the page body ever measures as zero-height (a stylesheet that has not
 * loaded yet, a hidden container), every block looks like an overflow and the
 * engine would keep minting sheets. Capping it turns that into a visibly wrong
 * page count instead of a locked-up tab.
 */
const MAX_PAGES = 400;

/** Wall-clock budget for one reflow pass, so a huge paste cannot freeze the tab. */
const MAX_REFLOW_MS = 400;

export interface FlowStats {
  pagesVisited: number;
  moves: number;
  splits: number;
  pulls: number;
  timedOut: boolean;
}

export class PageFlow {
  private reflowing = false;

  private queued: number | null = null;

  /** Work done by the last reflow — useful when diagnosing a slow document. */
  stats: FlowStats = { pagesVisited: 0, moves: 0, splits: 0, pulls: 0, timedOut: false };

  /** Shape of the document as of the last completed reflow. */
  private settledSignature = '';

  constructor(
    /** The contenteditable region holding every page. */
    private readonly root: HTMLElement,
    private options: PageOptions,
    private readonly onPagesChanged?: (count: number) => void
  ) {}

  get pages(): HTMLElement[] {
    return Array.from(this.root.querySelectorAll<HTMLElement>('[data-nk-page]'));
  }

  get pageCount(): number {
    return this.pages.length;
  }

  /** Physical page box for the current size and orientation. */
  get geometry(): { width: number; height: number } {
    const base = PAGE_SIZES[this.options.size] ?? PAGE_SIZES.a4;
    return this.options.orientation === 'landscape'
      ? { width: base.height, height: base.width }
      : base;
  }

  /** Usable area inside the margins — the height a page body must fit in. */
  get contentBox(): { width: number; height: number } {
    const { width, height } = this.geometry;
    const { top, right, bottom, left } = this.options.margins;
    return { width: width - left - right, height: height - top - bottom };
  }

  setOptions(options: Partial<PageOptions>): void {
    this.options = { ...this.options, ...options };
    this.applyGeometry();
    this.invalidate();
    this.reflow();
  }

  /** Push the page box into CSS custom properties the stylesheet reads. */
  applyGeometry(): void {
    const { width, height } = this.geometry;
    const content = this.contentBox;
    const { top, right, bottom, left } = this.options.margins;
    const style = this.root.style;

    style.setProperty('--nk-page-width', `${width}px`);
    style.setProperty('--nk-page-height', `${height}px`);
    style.setProperty('--nk-page-content-height', `${content.height}px`);
    style.setProperty('--nk-margin-top', `${top}px`);
    style.setProperty('--nk-margin-right', `${right}px`);
    style.setProperty('--nk-margin-bottom', `${bottom}px`);
    style.setProperty('--nk-margin-left', `${left}px`);
  }

  /** Schedule a reflow on the next frame, collapsing bursts into one pass. */
  schedule(): void {
    if (this.queued !== null) return;
    this.queued = requestAnimationFrame(() => {
      this.queued = null;
      this.reflow();
    });
  }

  cancel(): void {
    if (this.queued !== null) cancelAnimationFrame(this.queued);
    this.queued = null;
  }

  /**
   * Re-distribute content across pages.
   *
   * Re-entrancy matters: moving nodes fires mutation observers that would call
   * straight back in here, so the pass guards itself.
   */
  reflow(): void {
    if (this.reflowing) return;

    // Splitting a paragraph across a boundary is inherently a rewrite: the
    // parts are re-joined and cut again on every pass. On an unchanged document
    // that rewrite is pure churn, and — because it emits mutation records — it
    // would keep waking the change pipeline. Skipping it outright is what makes
    // a settled document truly quiet.
    const signature = this.signature();
    if (signature === this.settledSignature) return;

    this.reflowing = true;

    const before = this.pageCount;
    const saved = saveSelection(this.root);

    try {
      this.ensureFirstPage();
      this.rejoinSplitBlocks();

      let index = 0;
      const deadline = performance.now() + MAX_REFLOW_MS;
      this.stats = { pagesVisited: 0, moves: 0, splits: 0, pulls: 0, timedOut: false };

      // Pages are re-read each turn: the loop creates and removes them. Both
      // the page cap and the time budget are backstops — a document that fails
      // to settle must degrade into a wrong-looking page count, never into a
      // frozen tab.
      while (index < this.pages.length && index < MAX_PAGES) {
        this.pushOverflow(index);
        this.pullBack(index);
        index += 1;
        this.stats.pagesVisited = index;

        if (performance.now() > deadline) {
          this.stats.timedOut = true;
          break;
        }
      }

      this.dropTrailingPages();
      this.renumber();
    } finally {
      restoreSelection(this.root, saved);
      this.reflowing = false;
      // Remember the shape we settled into, not the one we started from. A pass
      // that ran out of time has *not* settled, so leave it invalidated and
      // pick up where it left off on the next frame.
      this.settledSignature = this.stats.timedOut ? '' : this.signature();
    }

    if (this.stats.timedOut) this.schedule();

    const after = this.pageCount;
    if (after !== before) this.onPagesChanged?.(after);
  }

  /** True while a reflow is running — mutation handlers use this to stand down. */
  get isReflowing(): boolean {
    return this.reflowing;
  }

  private bodyOf(page: HTMLElement): HTMLElement {
    return page.querySelector<HTMLElement>('[data-nk-content]') ?? page;
  }

  /**
   * Whether a page body holds more than it can show.
   *
   * A zero `clientHeight` means the page is not being laid out — it is inside a
   * `display: none` panel, or the stylesheet has not arrived yet. Every block
   * then looks like an overflow, so the engine would mint a fresh sheet per
   * block forever. There is nothing meaningful to measure in that state, so
   * treat it as "fits" and let a later reflow do the real work.
   */
  private overflows(body: HTMLElement): boolean {
    if (body.clientHeight === 0) return false;
    return this.contentBottom(body) > body.clientHeight + SLACK;
  }

  /**
   * How far the visible content reaches down the page, in pixels from the top
   * of the body.
   *
   * Measured from the last child's border box rather than `scrollHeight`,
   * because `scrollHeight` also counts the trailing margin below the final
   * paragraph. That margin is not visible and never gets clipped, but counting
   * it makes a perfectly-filled page look permanently overflowing — the engine
   * would then keep trying to move content that is already where it belongs.
   */
  private contentBottom(body: HTMLElement): number {
    const last = body.lastElementChild as HTMLElement | null;
    if (!last) return 0;
    return last.getBoundingClientRect().bottom - body.getBoundingClientRect().top;
  }

  private ensureFirstPage(): void {
    if (this.pages.length > 0) return;
    const page = this.createPage();
    this.root.appendChild(page);

    const body = this.bodyOf(page);
    if (!body.firstChild) {
      const paragraph = this.root.ownerDocument!.createElement('p');
      paragraph.appendChild(this.root.ownerDocument!.createElement('br'));
      blockId(paragraph);
      body.appendChild(paragraph);
    }
  }

  /**
   * Clone the page template, or build one from scratch if none is present.
   *
   * The template lives *outside* the contenteditable region — inside it, the
   * caret could wander into a hidden page — so it is looked up from the
   * component host rather than from the page root.
   */
  private createPage(): HTMLElement {
    const doc = this.root.ownerDocument!;
    const host = this.root.closest('.nk-native') ?? this.root.parentElement ?? this.root;
    const template = host.querySelector<HTMLElement>('[data-nk-page-template]');

    if (template) {
      const page = template.cloneNode(true) as HTMLElement;
      page.removeAttribute('data-nk-page-template');
      page.removeAttribute('hidden');
      // Must be tagged before `renumber` runs — that pass only sees pages.
      page.setAttribute('data-nk-page', '0');
      const body = this.bodyOf(page);
      body.innerHTML = '';
      return page;
    }

    const page = doc.createElement('div');
    page.className = 'nk-page';
    page.setAttribute('data-nk-page', '1');

    const body = doc.createElement('div');
    body.className = 'nk-page-body';
    body.setAttribute('data-nk-content', '');
    page.appendChild(body);

    return page;
  }

  private pageAfter(index: number): HTMLElement {
    const pages = this.pages;
    const existing = pages[index + 1];
    if (existing) return existing;

    const page = this.createPage();
    pages[index].parentNode?.insertBefore(page, pages[index].nextSibling);
    return page;
  }

  /**
   * Undo previous splits so measurement starts from whole blocks.
   *
   * Continuation parts are appended back onto the part that owns the id, then
   * the marker attribute is dropped.
   */
  private rejoinSplitBlocks(): void {
    const parts = Array.from(this.root.querySelectorAll<HTMLElement>(`[${PART_ATTR}]`));
    if (parts.length === 0) return;

    const owners = new Map<string, HTMLElement>();

    for (const part of parts) {
      const id = part.getAttribute('data-nk-id');
      if (!id) {
        part.removeAttribute(PART_ATTR);
        continue;
      }

      const owner = owners.get(id);
      if (!owner) {
        owners.set(id, part);
        continue;
      }

      while (part.firstChild) owner.appendChild(part.firstChild);
      part.remove();
    }

    for (const owner of owners.values()) {
      owner.removeAttribute(PART_ATTR);
      owner.normalize();
    }
  }

  /** Move content off the end of a page until it fits. */
  private pushOverflow(index: number): void {
    const page = this.pages[index];
    if (!page) return;

    const body = this.bodyOf(page);

    // A manual break sends everything from the marker onwards to a new sheet.
    const marker = body.querySelector<HTMLElement>(':scope > [data-nk-break]');
    if (marker && marker !== body.firstElementChild) {
      const next = this.bodyOf(this.pageAfter(index));
      const moving: Element[] = [];
      let node: Element | null = marker;
      while (node) {
        moving.push(node);
        node = node.nextElementSibling;
      }
      for (let i = moving.length - 1; i >= 0; i -= 1) {
        next.insertBefore(moving[i], next.firstChild);
      }
    }

    if (body.clientHeight === 0) return;

    let guard = 0;
    while (this.overflows(body) && guard < 20) {
      guard += 1;

      const children = Array.from(body.children) as HTMLElement[];
      if (children.length === 0) break;

      const limit = body.clientHeight;
      const bodyTop = body.getBoundingClientRect().top;

      // Find the first block that crosses the bottom edge. Every measurement
      // happens before any node moves, so the whole page costs one layout —
      // moving blocks one at a time and re-measuring after each was what made
      // long documents blow the reflow budget and stop paginating half way.
      let cut = -1;
      let cutTop = 0;
      for (let i = 0; i < children.length; i += 1) {
        const rect = children[i].getBoundingClientRect();
        if (rect.bottom - bodyTop > limit + SLACK) {
          cut = i;
          cutTop = rect.top - bodyTop;
          break;
        }
      }

      if (cut === -1) break; // overflow is sub-pixel noise

      const next = this.bodyOf(this.pageAfter(index));

      // Everything after the crossing block moves first, so that the crossing
      // block's continuation — inserted at the front below — lands ahead of it.
      const trailing = children.slice(cut + 1);
      for (let i = trailing.length - 1; i >= 0; i -= 1) {
        next.insertBefore(trailing[i], next.firstChild);
      }
      this.stats.moves += trailing.length;

      // The crossing block begins before the bottom edge, so part of it belongs
      // on this page: cut it at the last line that fits rather than banishing
      // the whole paragraph to the next sheet. Note the test is against the
      // *bottom* edge — a paragraph taller than a whole page starts at offset
      // zero, and that is exactly the case that most needs splitting.
      const crossing = children[cut];
      if (cutTop < limit - SLACK && this.splitBlock(crossing, body)) continue;

      // It could not be split (an image, a table, or it starts past the edge):
      // move it whole, unless it is the only thing here and would loop.
      if (cut === 0 && trailing.length === 0) break;
      next.insertBefore(crossing, next.firstChild);
      this.stats.moves += 1;
    }
  }

  /**
   * Pull content back from the following page while it still fits.
   *
   * This is what makes deleting text collapse pages again.
   *
   * The fit is *measured before moving anything*. An earlier version moved the
   * block optimistically and put it back when it did not fit — which left the
   * DOM unchanged but still emitted two mutation records every single pass. The
   * editor's own MutationObserver saw those, ran the change pipeline, reflowed
   * again, and the document never stopped churning. A settled document must
   * produce a reflow that touches nothing at all.
   */
  private pullBack(index: number): void {
    const page = this.pages[index];
    if (!page) return;

    const body = this.bodyOf(page);
    let guard = 0;

    while (guard < 500) {
      guard += 1;

      const next = this.pages[index + 1];
      if (!next) return;

      const nextBody = this.bodyOf(next);
      const candidate = nextBody.firstElementChild as HTMLElement | null;
      if (!candidate) return;

      // A manual break must stay at the top of its page.
      if (candidate.hasAttribute('data-nk-break')) return;

      // A page that cannot be measured (hidden, not yet styled) must be left
      // alone rather than guessed at.
      if (body.clientHeight === 0) return;

      const remaining = body.clientHeight - this.contentBottom(body);
      if (remaining <= SLACK) return;
      if (this.spaceNeededFor(candidate) > remaining) return;

      body.appendChild(candidate);
      this.stats.pulls += 1;

      // The estimate ignores margin collapsing, so confirm and undo if it was
      // optimistic. This should be rare; the pre-check carries the common case.
      if (this.overflows(body)) {
        nextBody.insertBefore(candidate, nextBody.firstChild);
        return;
      }
    }
  }

  /**
   * Cheap fingerprint of everything that can change how the document paginates:
   * how much text there is, how it is structured, and the box it flows into.
   *
   * Deliberately not the serialized HTML — this runs on every reflow, and the
   * counts catch typing, formatting, block changes and page-setup changes alike.
   */
  private signature(): string {
    const blocks = this.root.querySelectorAll('[data-nk-content] > *').length;
    const elements = this.root.getElementsByTagName('*').length;
    const chars = this.root.textContent?.length ?? 0;
    const { width, height } = this.geometry;
    const { top, right, bottom, left } = this.options.margins;

    return `${blocks}|${elements}|${chars}|${width}x${height}|${top},${right},${bottom},${left}|${this.root.clientWidth}`;
  }

  /** Force the next reflow to run even if the document looks unchanged. */
  invalidate(): void {
    this.settledSignature = '';
  }

  /** Vertical space a block would occupy, margins included. */
  private spaceNeededFor(element: HTMLElement): number {
    const rect = element.getBoundingClientRect();
    const style = this.root.ownerDocument!.defaultView!.getComputedStyle(element);
    const top = parseFloat(style.marginTop) || 0;
    const bottom = parseFloat(style.marginBottom) || 0;
    return rect.height + top + bottom;
  }

  /**
   * Cut a block at the last line that fits, leaving a continuation on the next
   * page. Both halves keep the block's id so the caret and the serializer still
   * treat them as one paragraph.
   *
   * Returns false when the block cannot be cut (an image, a table, or a single
   * line already taller than the page).
   */
  private splitBlock(block: HTMLElement, body: HTMLElement): boolean {
    if (!SPLITTABLE.has(tag(block))) return false;

    const text = block.textContent ?? '';
    if (text.length < 2) return false;

    const doc = this.root.ownerDocument!;
    // Measure against the rendered bottom edge, not `top + clientHeight`: under
    // a zoom the two are in different coordinate spaces, and `getClientRects`
    // below reports the zoomed one.
    const limit = body.getBoundingClientRect().bottom;

    const offset = this.findFitOffset(block, limit);
    if (offset <= 0 || offset >= text.length) return false;

    const point = this.resolveOffset(block, offset);
    if (!point) return false;

    const range = doc.createRange();
    range.setStart(point.node, point.offset);
    range.setEnd(block, block.childNodes.length);
    const rest = range.extractContents();

    if (!rest.textContent?.length) return false;

    const continuation = block.cloneNode(false) as HTMLElement;
    continuation.appendChild(rest);

    const part = Number(block.getAttribute(PART_ATTR) ?? 0);
    block.setAttribute(PART_ATTR, String(part));
    continuation.setAttribute(PART_ATTR, String(part + 1));

    // A continuation of a list item must not restart the marker.
    if (tag(continuation) === 'li') continuation.setAttribute('data-nk-continued', '');

    const index = this.pages.indexOf(body.closest('[data-nk-page]') as HTMLElement);
    const nextBody = this.bodyOf(this.pageAfter(index));
    nextBody.insertBefore(continuation, nextBody.firstChild);

    if (!block.firstChild) block.appendChild(doc.createElement('br'));

    this.stats.splits += 1;
    return true;
  }

  /**
   * Largest character offset whose line still ends above `limit`.
   *
   * Binary search over `Range` rects: measuring a range's bottom edge is the
   * only way to ask the browser where a line actually broke.
   */
  private findFitOffset(block: HTMLElement, limit: number): number {
    const doc = this.root.ownerDocument!;
    const total = (block.textContent ?? '').length;

    let low = 0;
    let high = total;
    let best = 0;

    while (low <= high) {
      const mid = Math.floor((low + high) / 2);
      const point = this.resolveOffset(block, mid);
      if (!point) break;

      const range = doc.createRange();
      range.setStart(block, 0);
      try {
        range.setEnd(point.node, point.offset);
      } catch {
        break;
      }

      const rects = range.getClientRects();
      const bottom = rects.length ? rects[rects.length - 1].bottom : 0;

      if (bottom <= limit) {
        best = mid;
        low = mid + 1;
      } else {
        high = mid - 1;
      }
    }

    return this.backToWordBoundary(block, best);
  }

  /** Walk back to the space before `offset` so words are not cut in half. */
  private backToWordBoundary(block: HTMLElement, offset: number): number {
    const text = block.textContent ?? '';
    if (offset <= 0 || offset >= text.length) return offset;

    for (let i = offset; i > Math.max(0, offset - 80); i -= 1) {
      if (/\s/.test(text[i - 1] ?? '')) return i;
    }

    return offset;
  }

  /** Character offset inside a block to a concrete (text node, offset) pair. */
  private resolveOffset(
    block: HTMLElement,
    offset: number
  ): { node: Node; offset: number } | null {
    const doc = this.root.ownerDocument!;
    const walker = doc.createTreeWalker(block, NodeFilter.SHOW_TEXT);
    let seen = 0;
    let node = walker.nextNode();
    let last: Text | null = null;

    while (node) {
      const text = node as Text;
      const size = text.length;
      if (seen + size >= offset) return { node: text, offset: offset - seen };
      seen += size;
      last = text;
      node = walker.nextNode();
    }

    if (last) return { node: last, offset: last.length };
    return null;
  }

  /** Remove empty pages off the end, always keeping the first. */
  private dropTrailingPages(): void {
    const pages = this.pages;

    for (let i = pages.length - 1; i > 0; i -= 1) {
      const body = this.bodyOf(pages[i]);
      const children = Array.from(body.children) as HTMLElement[];

      const meaningful = children.some(
        (child) => !isEmptyBlock(child) || isVoid(child) || child.hasAttribute('data-nk-break')
      );
      if (meaningful) break;

      // Keep one empty paragraph alive by handing it to the previous page.
      const previousBody = this.bodyOf(pages[i - 1]);
      for (const child of children) previousBody.appendChild(child);

      pages[i].remove();
    }

    // The last page must always end in something the caret can land on.
    const last = this.pages[this.pages.length - 1];
    if (last) {
      const body = this.bodyOf(last);
      if (!body.firstElementChild) {
        const doc = this.root.ownerDocument!;
        const paragraph = doc.createElement('p');
        paragraph.appendChild(doc.createElement('br'));
        blockId(paragraph);
        body.appendChild(paragraph);
      }
    }
  }

  /** Refresh the page-number labels and the total on each sheet. */
  private renumber(): void {
    const pages = this.pages;
    const total = pages.length;

    pages.forEach((page, index) => {
      page.setAttribute('data-nk-page', String(index + 1));
      page.style.setProperty('--nk-page-number', String(index + 1));

      const number = page.querySelector<HTMLElement>('[data-nk-page-number]');
      if (number) number.textContent = String(index + 1);

      const totalLabel = page.querySelector<HTMLElement>('[data-nk-page-total]');
      if (totalLabel) totalLabel.textContent = String(total);
    });
  }

  /** 1-based number of the page the caret currently sits on. */
  currentPage(): number {
    const selection = this.root.ownerDocument!.defaultView?.getSelection();
    const node = selection?.anchorNode;
    if (!node || !this.root.contains(node)) return 1;

    const element = node.nodeType === Node.ELEMENT_NODE ? (node as HTMLElement) : node.parentElement;
    const page = element?.closest<HTMLElement>('[data-nk-page]');
    if (!page) return 1;

    return this.pages.indexOf(page) + 1;
  }

  /** Lay a fresh set of blocks into the pages, replacing what is there. */
  load(fragment: DocumentFragment): void {
    const pages = this.pages;

    // Collapse to a single page, then let reflow rebuild the rest.
    for (let i = pages.length - 1; i > 0; i -= 1) pages[i].remove();
    this.ensureFirstPage();

    const body = this.bodyOf(this.pages[0]);
    body.innerHTML = '';

    if (!fragment.firstChild) {
      const doc = this.root.ownerDocument!;
      const paragraph = doc.createElement('p');
      paragraph.appendChild(doc.createElement('br'));
      fragment.appendChild(paragraph);
    }

    body.appendChild(fragment);
    Array.from(body.children).forEach((child) => blockId(child as HTMLElement));

    this.invalidate();
    this.reflow();
  }
}

/**
 * Image commands on the live DOM.
 *
 * Every image sits in a `<figure>`, which is what the commands work on: how
 * text flows around it (`data-wrap`), how wide it is (a percentage of the page
 * body, in its `width` style), its border (`data-border`, `data-border-color`)
 * and an optional `<figcaption>`.
 *
 * A cropped or turned image shows a picture drawn from its original, which it
 * keeps (`data-original`) with what was done to it (`data-crop`, `data-rotate`),
 * so it can be cropped again, or reset, without losing anything.
 */

import { emptyParagraph, tag } from './dom';
import { blockId, PART_ATTR, setCaret } from './selection';
import { dropGap, formatInsets, isUncropped, NO_CROP, parseInsets, type Insets } from './sizing';

export const WRAPS = ['inline', 'left', 'right'] as const;
export type Wrap = (typeof WRAPS)[number];

/** A wrapped image with no width of its own would take the whole line. */
const WRAPPED_WIDTH = 50;

export function figureOf(node: Node | null, root: HTMLElement): HTMLElement | null {
  const element = node && (node.nodeType === Node.ELEMENT_NODE ? (node as HTMLElement) : node.parentElement);
  const figure = element?.closest<HTMLElement>('figure');
  return figure && root.contains(figure) && figure.querySelector('img') ? figure : null;
}

export function imageOf(figure: HTMLElement): HTMLImageElement | null {
  return figure.querySelector('img');
}

export function wrapOf(figure: HTMLElement): Wrap {
  const wrap = figure.getAttribute('data-wrap');
  return wrap === 'left' || wrap === 'right' ? wrap : 'inline';
}

/** The width in percent, or null when the image keeps its own size. */
export function widthOf(figure: HTMLElement): number | null {
  const width = parseFloat(figure.style.width);
  return Number.isFinite(width) && figure.style.width.endsWith('%') ? width : null;
}

export function setWrap(figure: HTMLElement, wrap: Wrap): void {
  if (wrap === 'inline') figure.removeAttribute('data-wrap');
  else {
    figure.setAttribute('data-wrap', wrap);
    if (widthOf(figure) === null) setWidth(figure, WRAPPED_WIDTH);
  }
}

export function setWidth(figure: HTMLElement, percent: number | null): void {
  if (percent === null) figure.style.removeProperty('width');
  else figure.style.width = `${Math.max(10, Math.min(100, Math.round(percent)))}%`;
  if (!figure.getAttribute('style')) figure.removeAttribute('style');
}

export function captionOf(figure: HTMLElement): HTMLElement | null {
  return figure.querySelector(':scope > figcaption');
}

/** Add a caption (returned, for the caret) or take it away. */
export function toggleCaption(figure: HTMLElement): HTMLElement | null {
  const existing = captionOf(figure);
  if (existing) {
    existing.remove();
    return null;
  }

  const caption = figure.ownerDocument!.createElement('figcaption');
  caption.appendChild(figure.ownerDocument!.createElement('br'));
  blockId(caption);
  figure.appendChild(caption);
  return caption;
}

export function setAlt(figure: HTMLElement, text: string): void {
  imageOf(figure)?.setAttribute('alt', text.trim());
}

/** A new picture: whatever was done to the old one goes with it. */
export function setSource(figure: HTMLElement, src: string, alt?: string): void {
  const image = imageOf(figure);
  if (!image) return;
  image.setAttribute('src', src);
  for (const attr of ['data-original', 'data-crop', 'data-rotate']) image.removeAttribute(attr);
  if (alt !== undefined && !image.getAttribute('alt')) image.setAttribute('alt', alt);
  delete image.dataset.nkWatched;
}

/* -------------------------------------------------------- crop, rotate -- */

export interface PictureEdit {
  angle: number;
  insets: Insets;
}

/** The picture an image was made from: its original, or its own when untouched. */
export function originalOf(image: HTMLImageElement): string {
  return image.getAttribute('data-original') || image.getAttribute('src') || '';
}

/** How the image's picture was turned and cropped from its original. */
export function editOf(image: HTMLImageElement): PictureEdit {
  const angle = Number(image.getAttribute('data-rotate') ?? 0);
  return {
    angle: [90, 180, 270].includes(angle) ? angle : 0,
    insets: parseInsets(image.getAttribute('data-crop')) ?? NO_CROP,
  };
}

export const isEdited = (image: HTMLImageElement): boolean => image.hasAttribute('data-original');

/** Show `src`, drawn from `original` as `edit` says — or the original itself when nothing is done to it. */
export function setPicture(figure: HTMLElement, src: string, original: string, edit: PictureEdit): void {
  const image = imageOf(figure);
  if (!image) return;
  const untouched = edit.angle === 0 && isUncropped(edit.insets);

  image.setAttribute('src', untouched ? original : src);
  if (untouched) image.removeAttribute('data-original');
  else image.setAttribute('data-original', original);
  if (untouched || isUncropped(edit.insets)) image.removeAttribute('data-crop');
  else image.setAttribute('data-crop', formatInsets(edit.insets));
  if (untouched || edit.angle === 0) image.removeAttribute('data-rotate');
  else image.setAttribute('data-rotate', String(edit.angle));
  delete image.dataset.nkWatched;
}

/**
 * A picture ready to draw on a canvas, or null when it cannot be: another
 * site serving it without leave to read it back, or a broken address.
 */
export function loadPicture(src: string): Promise<HTMLImageElement | null> {
  return new Promise((resolve) => {
    const picture = new Image();
    // Asked for with CORS, so a picture from elsewhere can be read back once drawn.
    if (/^https?:/i.test(src) && new URL(src, location.href).origin !== location.origin) picture.crossOrigin = 'anonymous';
    picture.onload = () => resolve(picture.naturalWidth ? picture : null);
    picture.onerror = () => resolve(null);
    picture.src = src;
  });
}

/** JPEG for photos that were JPEG, PNG for the rest (it keeps transparency). */
export function pictureType(src: string): 'image/jpeg' | 'image/png' {
  return /^data:image\/jpe?g|\.jpe?g(?:$|[?#])/i.test(src) ? 'image/jpeg' : 'image/png';
}

/** The picture turned by `edit.angle`, then cropped by `edit.insets`; null when it cannot be read back. */
export function drawPicture(picture: HTMLImageElement, edit: PictureEdit, type: string): Promise<Blob | null> {
  const width = picture.naturalWidth;
  const height = picture.naturalHeight;
  const sideways = edit.angle === 90 || edit.angle === 270;
  const turnedWidth = sideways ? height : width;
  const turnedHeight = sideways ? width : height;

  const turned = document.createElement('canvas');
  turned.width = turnedWidth;
  turned.height = turnedHeight;
  const context = turned.getContext('2d');
  if (!context) return Promise.resolve(null);
  if (edit.angle === 90) context.translate(turnedWidth, 0);
  else if (edit.angle === 180) context.translate(turnedWidth, turnedHeight);
  else if (edit.angle === 270) context.translate(0, turnedHeight);
  context.rotate((edit.angle * Math.PI) / 180);
  context.drawImage(picture, 0, 0);

  const [top, right, bottom, left] = edit.insets;
  const out = document.createElement('canvas');
  out.width = Math.max(1, Math.round(turnedWidth * (1 - left - right)));
  out.height = Math.max(1, Math.round(turnedHeight * (1 - top - bottom)));
  const outContext = out.getContext('2d');
  if (!outContext) return Promise.resolve(null);
  if (type === 'image/jpeg') {
    // JPEG has no transparency: what was see-through turns white, not black.
    outContext.fillStyle = '#ffffff';
    outContext.fillRect(0, 0, out.width, out.height);
  }
  outContext.drawImage(turned, Math.round(turnedWidth * left), Math.round(turnedHeight * top), out.width, out.height, 0, 0, out.width, out.height);

  return new Promise((resolve) => {
    try {
      out.toBlob((blob) => resolve(blob), type, 0.92);
    } catch {
      // A picture from another site without CORS taints the canvas.
      resolve(null);
    }
  });
}

/* -------------------------------------------------------------- border -- */

export const IMAGE_BORDERS = { thin: 1, medium: 2, thick: 4 } as const;
export type ImageBorder = keyof typeof IMAGE_BORDERS;
export const DEFAULT_IMAGE_BORDER_COLOR = '#000000';

export function borderOf(figure: HTMLElement): ImageBorder | null {
  const weight = figure.getAttribute('data-border');
  return weight && weight in IMAGE_BORDERS ? (weight as ImageBorder) : null;
}

/** A border weight (null takes it away) and, when given, its colour (null for the default). */
export function setBorder(figure: HTMLElement, weight: ImageBorder | null | undefined, color?: string | null): void {
  if (weight === null) figure.removeAttribute('data-border');
  else if (weight) figure.setAttribute('data-border', weight);
  if (color === null) figure.removeAttribute('data-border-color');
  else if (color) figure.setAttribute('data-border-color', color);
  paintFigure(figure);
}

/** The border's colour as a custom property, which the editor's styles draw with. */
export function paintFigure(figure: HTMLElement): void {
  const color = figure.getAttribute('data-border-color');
  if (color) figure.style.setProperty('--nk-image-border-color', color);
  else figure.style.removeProperty('--nk-image-border-color');
  if (!figure.getAttribute('style')) figure.removeAttribute('style');
}

/* ---------------------------------------------------------------- move -- */

export interface DropSlot {
  body: HTMLElement;
  /** The block the image goes before; null for the end of the page. */
  before: HTMLElement | null;
  /** Where to draw the line that shows it, in viewport pixels. */
  line: { left: number; top: number; width: number };
}

/**
 * Where an image dragged to (`x`, `y`) would land: between two blocks of the
 * page under the pointer, or of the nearest one. Null where it already is.
 */
export function dropSlot(root: HTMLElement, figure: HTMLElement, x: number, y: number): DropSlot | null {
  const bodies = Array.from(root.querySelectorAll<HTMLElement>('.nk-page-body[data-nk-content]'));
  let body: HTMLElement | null = null;
  let nearest = Infinity;
  for (const candidate of bodies) {
    const box = candidate.getBoundingClientRect();
    const distance = y < box.top ? box.top - y : y > box.bottom ? y - box.bottom : 0;
    if (distance < nearest) {
      nearest = distance;
      body = candidate;
    }
  }
  if (!body) return null;

  const children = Array.from(body.children) as HTMLElement[];
  const spans = children.map((child) => child.getBoundingClientRect());
  const gap = dropGap(spans, y);
  const before = children[gap] ?? null;
  const after = children[gap - 1] ?? null;
  if (before === figure || after === figure) return null;

  const area = body.getBoundingClientRect();
  const top = before && after ? (after.bottom + before.top) / 2 : before ? before.getBoundingClientRect().top : after ? after.getBoundingClientRect().bottom : area.top;
  return { body, before, line: { left: area.left, top, width: area.width } };
}

/**
 * Move an image to where `dropSlot` said. A paragraph a page cut in two is
 * never split by it: it goes after the paragraph's last part.
 */
export function moveFigure(root: HTMLElement, figure: HTMLElement, slot: DropSlot): void {
  const before = slot.before;
  const after = before ? (before.previousElementSibling as HTMLElement | null) : (slot.body.lastElementChild as HTMLElement | null);
  const cut = [after, before].find((block) => block?.hasAttribute(PART_ATTR) && partsAfter(root, block).length);
  if (cut) {
    const parts = partsAfter(root, cut);
    parts[parts.length - 1].after(figure);
  } else if (before) before.before(figure);
  else slot.body.appendChild(figure);
}

/** The parts of a cut block that come after `block`, or would join it. */
function partsAfter(root: HTMLElement, block: HTMLElement): HTMLElement[] {
  const id = block.getAttribute('data-nk-id');
  if (!id) return [];
  const parts = Array.from(root.querySelectorAll<HTMLElement>(`[data-nk-id="${CSS.escape(id)}"]`));
  const at = parts.indexOf(block);
  // A continuation always has an earlier part: going before it would split the block too.
  return Number(block.getAttribute(PART_ATTR) ?? 0) > 0 ? parts.slice(Math.max(at, 0)) : parts.slice(at + 1);
}

/** The block after a figure, made when there is none, for the caret to go to. */
export function blockAfter(figure: HTMLElement): HTMLElement {
  const next = figure.nextElementSibling as HTMLElement | null;
  if (next && !['figure', 'table', 'hr'].includes(tag(next)) && !next.hasAttribute('data-nk-break')) return next;
  return paragraphAfter(figure);
}

/** A fresh paragraph right after a figure, as Enter in its caption makes. */
export function paragraphAfter(figure: HTMLElement): HTMLElement {
  const paragraph = emptyParagraph(figure.ownerDocument!);
  blockId(paragraph);
  figure.after(paragraph);
  return paragraph;
}

/** Take the image out, leaving the caret where it was. */
export function removeFigure(root: HTMLElement, figure: HTMLElement): void {
  const landing = blockAfter(figure);
  figure.remove();
  setCaret(root, landing, 0);
}

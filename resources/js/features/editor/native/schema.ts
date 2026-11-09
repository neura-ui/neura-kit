/**
 * Native editor schema.
 *
 * The document is a flat list of block elements living inside page hosts.
 * Inline formatting is expressed as nested *mark* elements inside those blocks.
 *
 * Everything the editor is allowed to produce or accept is described here —
 * `sanitize.ts` uses these tables as its allowlist, so a tag that is not
 * reachable from this file cannot enter the document.
 */

export type BlockName =
  | 'paragraph'
  | 'title'
  | 'subtitle'
  | 'heading1'
  | 'heading2'
  | 'heading3'
  | 'heading4'
  | 'blockquote'
  | 'codeblock';

export interface BlockSpec {
  /** Element the block renders as. */
  tag: string;
  /** Marker attribute so `h1` used as Title is distinguishable from Heading 1. */
  variant?: string;
  /** Label shown in the style dropdown. */
  label: string;
  /** Outline depth; absent means the block never appears in the outline. */
  level?: number;
  /** Blocks that keep their text verbatim (no mark commands, no autoformat). */
  plain?: boolean;
}

export const BLOCKS: Record<BlockName, BlockSpec> = {
  paragraph: { tag: 'p', label: 'Normal text' },
  title: { tag: 'h1', variant: 'title', label: 'Title' },
  subtitle: { tag: 'h2', variant: 'subtitle', label: 'Subtitle' },
  heading1: { tag: 'h1', label: 'Heading 1', level: 1 },
  heading2: { tag: 'h2', label: 'Heading 2', level: 2 },
  heading3: { tag: 'h3', label: 'Heading 3', level: 3 },
  heading4: { tag: 'h4', label: 'Heading 4', level: 4 },
  blockquote: { tag: 'blockquote', label: 'Quote' },
  codeblock: { tag: 'pre', label: 'Code block', plain: true },
};

/** Blocks offered in the toolbar style dropdown, in menu order. */
export const STYLE_MENU: BlockName[] = [
  'paragraph',
  'title',
  'subtitle',
  'heading1',
  'heading2',
  'heading3',
  'heading4',
];

export type MarkName =
  | 'bold'
  | 'italic'
  | 'underline'
  | 'strike'
  | 'code'
  | 'superscript'
  | 'subscript'
  | 'link'
  | 'highlight'
  | 'color'
  | 'fontFamily'
  | 'fontSize';

export interface MarkSpec {
  tag: string;
  /**
   * Marks carried by a CSS property on a `<span>` rather than by their tag.
   * Two such marks of the same kind always replace each other instead of
   * nesting, so `color` applied twice leaves one span, not two.
   */
  style?: string;
  /** Tags that mean the same mark when pasted from elsewhere. */
  aliases?: string[];
  /** A mark that cannot coexist with these on the same text. */
  excludes?: MarkName[];
}

export const MARKS: Record<MarkName, MarkSpec> = {
  bold: { tag: 'strong', aliases: ['b'] },
  italic: { tag: 'em', aliases: ['i'] },
  underline: { tag: 'u', aliases: ['ins'] },
  strike: { tag: 's', aliases: ['strike', 'del'] },
  code: { tag: 'code' },
  superscript: { tag: 'sup', excludes: ['subscript'] },
  subscript: { tag: 'sub', excludes: ['superscript'] },
  link: { tag: 'a' },
  highlight: { tag: 'mark' },
  color: { tag: 'span', style: 'color' },
  fontFamily: { tag: 'span', style: 'font-family' },
  fontSize: { tag: 'span', style: 'font-size' },
};

/** Reverse lookup from a lowercase tag name to the mark it represents. */
export const TAG_TO_MARK: Record<string, MarkName> = (() => {
  const map: Record<string, MarkName> = {};
  for (const [name, spec] of Object.entries(MARKS) as [MarkName, MarkSpec][]) {
    if (spec.style) continue; // styled marks are matched by CSS property, not tag
    map[spec.tag] = name;
    for (const alias of spec.aliases ?? []) map[alias] = name;
  }
  return map;
})();

/** Container tags that may appear between the page host and a leaf block. */
export const CONTAINER_TAGS = new Set(['ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'colgroup', 'nav']);

/** Leaf block tags — a caret can sit directly inside these. */
export const BLOCK_TAGS = new Set([
  'p',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  'blockquote',
  'pre',
  'li',
  'td',
  'th',
  'figure',
  'figcaption',
]);

/** Blocks that hold no text and are selected rather than edited. */
export const VOID_TAGS = new Set(['hr', 'img', 'br', 'col']);

export type Alignment = 'left' | 'center' | 'right' | 'justify';

export const FONT_FAMILIES = [
  { label: 'Arial', value: 'Arial, Helvetica, sans-serif' },
  { label: 'Georgia', value: 'Georgia, serif' },
  { label: 'Times New Roman', value: '"Times New Roman", Times, serif' },
  { label: 'Courier New', value: '"Courier New", Courier, monospace' },
  { label: 'Verdana', value: 'Verdana, Geneva, sans-serif' },
  { label: 'Roboto', value: 'Roboto, system-ui, sans-serif' },
  { label: 'Inter', value: 'Inter, system-ui, sans-serif' },
];

export const FONT_SIZES = [8, 9, 10, 11, 12, 14, 18, 24, 30, 36, 48, 60, 72, 96];

const BLOCK_SUGGEST_ATTRS = ['data-suggest-split', 'data-suggest-join', 'data-suggest-style'];

/** Attributes each allowed tag may keep after a paste. */
export const ALLOWED_ATTRS: Record<string, string[]> = {
  // `data-level`: a table of contents entry's heading level.
  a: ['href', 'target', 'rel', 'title', 'data-level'],
  // A cropped or turned image keeps its original and what was done to it.
  img: ['src', 'alt', 'width', 'height', 'data-original', 'data-crop', 'data-rotate'],
  // A smart chip (a person or a date) and a footnote marker with its note.
  span: ['style', 'data-chip', 'data-id', 'data-email', 'data-date', 'data-footnote', 'data-note', 'data-comment', 'data-suggest', 'data-author', 'data-time', 'data-format'],
  // Suggested changes, with who made them and when; the comment threads' store.
  ins: ['data-suggest', 'data-author', 'data-time'],
  del: ['data-suggest', 'data-author', 'data-time'],
  aside: ['data-comments', 'hidden'],
  sup: ['data-footnote'],
  // Headings keep the anchor a table of contents links to; text blocks, the
  // suggestions made to the block itself (a new paragraph, a join, its look).
  h1: ['id', ...BLOCK_SUGGEST_ATTRS],
  h2: ['id', ...BLOCK_SUGGEST_ATTRS],
  h3: ['id', ...BLOCK_SUGGEST_ATTRS],
  h4: ['id', ...BLOCK_SUGGEST_ATTRS],
  h5: ['id', ...BLOCK_SUGGEST_ATTRS],
  h6: ['id', ...BLOCK_SUGGEST_ATTRS],
  p: [...BLOCK_SUGGEST_ATTRS],
  blockquote: [...BLOCK_SUGGEST_ATTRS],
  pre: [...BLOCK_SUGGEST_ATTRS],
  nav: ['data-toc'],
  td: ['colspan', 'rowspan'],
  th: ['colspan', 'rowspan'],
  // A table's border weight and colour; an image's text wrap and border.
  table: ['data-border', 'data-border-color'],
  figure: ['data-wrap', 'data-border', 'data-border-color'],
  // `data-footnotes`: the list of notes at the end of a saved document.
  ol: ['start', 'data-footnotes', 'data-numbering'],
  // A checklist is a bulleted list marked as one; each item says whether it is done.
  ul: ['data-checklist'],
  li: ['data-checked', 'data-footnote', ...BLOCK_SUGGEST_ATTRS],
};

/** CSS properties a pasted `style` attribute may keep. */
export const ALLOWED_STYLES = new Set([
  'color',
  'background-color',
  'font-family',
  'font-size',
  'font-weight',
  'font-style',
  'text-decoration',
  'text-align',
  'text-indent',
  'margin-left',
  'line-height',
]);

/**
 * Properties only some tags may keep, beyond `ALLOWED_STYLES`: an image's
 * width and the table column widths, both in percent.
 */
export const TAG_STYLES: Record<string, string[]> = {
  figure: ['width'],
  col: ['width'],
};

/** URL schemes a link or image may point at. */
export const SAFE_SCHEMES = ['http:', 'https:', 'mailto:', 'tel:'];

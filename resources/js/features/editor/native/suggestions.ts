/**
 * Suggested changes beyond typed and deleted text, as plain data.
 *
 * A formatting change on some text (`<span data-suggest data-format>`) keeps,
 * mark by mark, what the text had before, so a rejection puts it back. A block
 * keeps the suggestions made to the block itself, each in its own attribute:
 * a new paragraph (`data-suggest-split`), two paragraphs joined
 * (`data-suggest-join`), a paragraph style, alignment or spacing
 * (`data-suggest-style`, with the block's look before). They travel with the
 * document, so anything can be in there: only well-formed ones are read back.
 * No imports, so it runs in Node.
 */

export type BlockSuggestionKind = 'split' | 'join' | 'style';

export const BLOCK_SUGGESTION_KINDS: BlockSuggestionKind[] = ['split', 'join', 'style'];

/** How a block looked: its paragraph style, alignment, line spacing and indent. */
export interface BlockLook {
  name: string;
  align: string;
  spacing: string;
  indent: string;
}

export interface BlockSuggestion {
  id: string;
  author: string;
  time: string;
  /** For a style suggestion: the block as it was. */
  before?: BlockLook;
}

/** A mark set or taken off: `true` / `false` for bold and the like, a value or null for colours, fonts, sizes. */
export interface FormatChange {
  mark: string;
  after: string | boolean | null;
  before: string | boolean | null;
}

export const FORMAT_MARKS = ['bold', 'italic', 'underline', 'strike', 'code', 'superscript', 'subscript', 'highlight', 'color', 'fontFamily', 'fontSize'];

const SUGGESTION_ID = /^s-[a-z0-9-]+$/i;

const clean = (value: unknown, max: number): string => (typeof value === 'string' ? value.slice(0, max) : '');

const markValue = (value: unknown): string | boolean | null =>
  typeof value === 'boolean' ? value : typeof value === 'string' && value.length <= 200 && !/[<>"]|url\s*\(|expression/i.test(value) ? value : null;

/** The attribute a block suggestion of `kind` lives in. */
export const blockAttribute = (kind: BlockSuggestionKind): string => `data-suggest-${kind}`;

/** A block suggestion read from its attribute, or null when it is not one. */
export function parseBlockSuggestion(value: string | null, kind: BlockSuggestionKind): BlockSuggestion | null {
  if (!value) return null;
  let raw: unknown;
  try {
    raw = JSON.parse(value);
  } catch {
    return null;
  }
  if (!raw || typeof raw !== 'object') return null;
  const data = raw as Record<string, unknown>;
  const id = clean(data.id, 64);
  if (!SUGGESTION_ID.test(id)) return null;
  const suggestion: BlockSuggestion = { id, author: clean(data.author, 120), time: clean(data.time, 40) };
  if (kind === 'style') {
    const before = data.before as Record<string, unknown> | undefined;
    if (!before || typeof before !== 'object') return null;
    suggestion.before = {
      name: clean(before.name, 20),
      align: /^(left|center|right|justify)?$/.test(clean(before.align, 10)) ? clean(before.align, 10) : '',
      spacing: /^(\d+(\.\d+)?)?$/.test(clean(before.spacing, 6)) ? clean(before.spacing, 6) : '',
      indent: /^\d?$/.test(clean(before.indent, 1)) ? clean(before.indent, 1) : '',
    };
    if (!/^[a-z0-9]+$/i.test(suggestion.before.name)) return null;
  }
  return suggestion;
}

export function writeBlockSuggestion(suggestion: BlockSuggestion): string {
  return JSON.stringify(suggestion);
}

/** A text's formatting changes read from its attribute; empty when it is not one. */
export function parseFormat(value: string | null): FormatChange[] {
  if (!value) return [];
  let raw: unknown;
  try {
    raw = JSON.parse(value);
  } catch {
    return [];
  }
  if (!Array.isArray(raw)) return [];
  const changes: FormatChange[] = [];
  for (const entry of raw.slice(0, 12)) {
    if (!entry || typeof entry !== 'object') continue;
    const data = entry as Record<string, unknown>;
    const mark = clean(data.mark, 20);
    if (!FORMAT_MARKS.includes(mark) || changes.some((change) => change.mark === mark)) continue;
    changes.push({ mark, after: markValue(data.after), before: markValue(data.before) });
  }
  return changes;
}

/**
 * A new change added to the ones a text already has: the same mark changed
 * again keeps what it was at first, and a change back to that drops it.
 */
export function addChange(changes: FormatChange[], change: FormatChange): FormatChange[] {
  const existing = changes.find((other) => other.mark === change.mark);
  const before = existing ? existing.before : change.before;
  const rest = changes.filter((other) => other.mark !== change.mark);
  return sameValue(before, change.after) ? rest : [...rest, { mark: change.mark, after: change.after, before }];
}

/** Two mark values alike: `false` and none are the same, colours and sizes ignore case and spaces. */
export function sameValue(a: string | boolean | null, b: string | boolean | null): boolean {
  const norm = (value: string | boolean | null) => (value === false || value === null || value === '' ? null : typeof value === 'string' ? value.replace(/\s+/g, '').toLowerCase() : value);
  return norm(a) === norm(b);
}

/** Two looks of a block alike. */
export function sameLook(a: BlockLook, b: BlockLook): boolean {
  return a.name === b.name && a.align === b.align && a.spacing === b.spacing && a.indent === b.indent;
}

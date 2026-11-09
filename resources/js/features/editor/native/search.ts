/**
 * Find and replace, as plain text functions.
 *
 * A query becomes a global regular expression: escaped unless `regex` is on,
 * case-insensitive unless `matchCase`, and bounded by non-letters with
 * `wholeWord` (letters in any script, so "équipe" is a word too). No imports,
 * so it runs in Node.
 */

export interface SearchOptions {
  matchCase?: boolean;
  wholeWord?: boolean;
  regex?: boolean;
}

export type Compiled =
  | { pattern: RegExp; error: null }
  | { pattern: null; error: string | null };

function source(query: string, options: SearchOptions): string {
  const body = options.regex ? query : query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return options.wholeWord ? `(?<![\\p{L}\\p{N}_])(?:${body})(?![\\p{L}\\p{N}_])` : body;
}

/** The query as a pattern, or why it is not one. An empty query is neither. */
export function compile(query: string, options: SearchOptions = {}): Compiled {
  if (!query) return { pattern: null, error: null };
  try {
    return { pattern: new RegExp(source(query, options), `gu${options.matchCase ? '' : 'i'}`), error: null };
  } catch (error) {
    return { pattern: null, error: error instanceof Error ? error.message : String(error) };
  }
}

/** Start and end of every non-empty match in `text`. */
export function findInText(text: string, pattern: RegExp, limit = 5000): Array<[number, number]> {
  const out: Array<[number, number]> = [];
  pattern.lastIndex = 0;
  let match: RegExpExecArray | null;
  while ((match = pattern.exec(text)) && out.length < limit) {
    if (!match[0].length) {
      pattern.lastIndex += 1;
      continue;
    }
    out.push([match.index, match.index + match[0].length]);
  }
  return out;
}

/** What a match becomes: the replacement as typed, or with $1… filled in regex mode. */
export function replacementFor(match: string, query: string, replacement: string, options: SearchOptions): string {
  if (!options.regex) return replacement;
  try {
    return match.replace(new RegExp(source(query, options), `u${options.matchCase ? '' : 'i'}`), replacement);
  } catch {
    return replacement;
  }
}

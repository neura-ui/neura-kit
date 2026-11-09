/**
 * Renaming in place: the part of a name selected first, and why a name is refused.
 * No DOM and no local import, so it runs in Node.
 */

/** What renaming selects first: the name without its extension, or all of it. */
export function stemRange(name: string): [number, number] {
  const dot = name.lastIndexOf('.');

  return dot > 0 && dot < name.length - 1 ? [0, dot] : [0, name.length];
}

export type NameError = 'empty' | 'slash' | 'taken' | null;

/**
 * Why a name cannot be used in a folder holding `siblings`, or null when it can. Names
 * are compared without case, as most file systems do; the entry's own name is not "taken".
 */
export function nameError(name: string, siblings: string[], current?: string): NameError {
  const trimmed = name.trim();
  if (!trimmed) return 'empty';
  if (/[/\\]/.test(trimmed)) return 'slash';

  const wanted = trimmed.toLowerCase();
  const own = current?.trim().toLowerCase();
  if (wanted !== own && siblings.some((sibling) => sibling.trim().toLowerCase() === wanted)) return 'taken';

  return null;
}

/**
 * The type filter: the groups a folder holds, in a fixed order, and what each keeps.
 * No DOM and no local import, so it runs in Node.
 */

export const GROUP_ORDER = ['folder', 'image', 'document', 'video', 'audio', 'archive', 'code'];

/** The groups present among entries, in `GROUP_ORDER`. */
export function groupsPresent(entries: Array<{ group: string }>): string[] {
  const present = new Set(entries.map((entry) => entry.group));

  return GROUP_ORDER.filter((group) => present.has(group));
}

/** The entries of one group; all of them when no group is picked. */
export function byGroup<T extends { group: string }>(entries: T[], group: string | null): T[] {
  return group ? entries.filter((entry) => entry.group === group) : entries;
}

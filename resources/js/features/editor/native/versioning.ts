/**
 * Version history, as plain data: when a snapshot becomes a new version, which
 * old versions go, how versions are grouped by day, and each author's colour.
 * No imports, so it runs in Node.
 */

export interface VersionMeta {
  id: string;
  /** ISO time of the last change it holds. */
  time: string;
  /** Empty for a version recorded on its own; set when someone names it. */
  name: string;
  authors: string[];
}

/**
 * What a snapshot of the document does: nothing when it has not changed, a
 * new version when there is none yet, when the last one is named (a named
 * version is never overwritten) or older than `interval`; otherwise it updates
 * the last version, so a stretch of editing is one version, not hundreds.
 */
export function planSnapshot(
  latest: { time: string; name: string } | null,
  changed: boolean,
  now: number,
  interval: number
): 'skip' | 'add' | 'update' {
  if (!changed) return 'skip';
  if (!latest || latest.name) return 'add';
  const at = Date.parse(latest.time);
  return Number.isNaN(at) || now - at > interval ? 'add' : 'update';
}

/** Newest first: every named version, and the `limit` newest of the others. */
export function prune<T extends VersionMeta>(versions: T[], limit: number): T[] {
  let unnamed = 0;
  return versions.filter((version) => version.name !== '' || unnamed++ < limit);
}

const dayKey = (date: Date): string => `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;

/** Versions under "Today", "Yesterday", then their date. */
export function dayGroups<T extends VersionMeta>(
  versions: T[],
  now: Date,
  labels: { today: string; yesterday: string },
  format: (date: Date) => string
): Array<{ label: string; items: T[] }> {
  const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
  const groups: Array<{ key: string; label: string; items: T[] }> = [];

  for (const version of versions) {
    const date = new Date(version.time);
    if (Number.isNaN(date.getTime())) continue;
    const key = dayKey(date);
    let group = groups.find((candidate) => candidate.key === key);
    if (!group) {
      const label = key === dayKey(now) ? labels.today : key === dayKey(yesterday) ? labels.yesterday : format(date);
      group = { key, label, items: [] };
      groups.push(group);
    }
    group.items.push(version);
  }

  return groups.map(({ label, items }) => ({ label, items }));
}

const COLOURS = ['#6366f1', '#ec4899', '#22c55e', '#f59e0b', '#0ea5e9', '#a855f7', '#ef4444', '#14b8a6'];

export function authorColor(name: string): string {
  let hash = 0;
  for (const char of name) hash = (hash * 31 + char.charCodeAt(0)) | 0;
  return COLOURS[Math.abs(hash) % COLOURS.length];
}

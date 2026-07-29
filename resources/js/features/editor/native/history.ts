/**
 * Undo/redo.
 *
 * Snapshot-based rather than transaction-based: each entry is the serialized
 * document plus the caret at that moment. Consecutive typing coalesces into one
 * entry so undo walks back in words rather than characters, which is what users
 * expect from Ctrl+Z.
 */

import type { SavedSelection } from './selection';

export interface HistoryEntry {
  html: string;
  selection: SavedSelection | null;
}

/** Changes tagged `typing` merge with the previous entry inside the window. */
export type ChangeKind = 'typing' | 'command';

export class History {
  private past: HistoryEntry[] = [];

  private future: HistoryEntry[] = [];

  private present: HistoryEntry;

  private lastAt = 0;

  private lastKind: ChangeKind = 'command';

  constructor(
    initial: HistoryEntry,
    private readonly limit = 200,
    private readonly coalesceMs = 600
  ) {
    this.present = initial;
  }

  get canUndo(): boolean {
    return this.past.length > 0;
  }

  get canRedo(): boolean {
    return this.future.length > 0;
  }

  /** Current snapshot — what the document should look like right now. */
  get current(): HistoryEntry {
    return this.present;
  }

  /**
   * Record a new document state.
   *
   * Runs of typing inside the coalesce window overwrite the pending entry
   * instead of stacking, so a sentence is one undo step, not forty.
   */
  record(entry: HistoryEntry, kind: ChangeKind = 'command'): void {
    if (entry.html === this.present.html) {
      // Same content, moved caret — keep the newer selection for a better undo.
      this.present = entry;
      return;
    }

    const now = Date.now();
    const coalesce =
      kind === 'typing' && this.lastKind === 'typing' && now - this.lastAt < this.coalesceMs;

    if (!coalesce) {
      this.past.push(this.present);
      if (this.past.length > this.limit) this.past.shift();
    }

    this.present = entry;
    this.future = [];
    this.lastAt = now;
    this.lastKind = kind;
  }

  /** Force the next `record` to start a fresh entry. */
  breakCoalescing(): void {
    this.lastKind = 'command';
  }

  undo(): HistoryEntry | null {
    const previous = this.past.pop();
    if (!previous) return null;

    this.future.push(this.present);
    this.present = previous;
    this.lastKind = 'command';
    return previous;
  }

  redo(): HistoryEntry | null {
    const next = this.future.pop();
    if (!next) return null;

    this.past.push(this.present);
    this.present = next;
    this.lastKind = 'command';
    return next;
  }

  /** Drop all history and restart from `entry`. */
  reset(entry: HistoryEntry): void {
    this.past = [];
    this.future = [];
    this.present = entry;
    this.lastKind = 'command';
  }
}

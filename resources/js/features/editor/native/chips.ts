/**
 * Smart chips: a person or a date set in the text as one unit, as "@" makes.
 *
 * A chip is a `<span data-chip>` the caret steps over rather than into. It
 * carries what it stands for — a person's id and email, a date as YYYY-MM-DD —
 * and says it in its text, so the saved HTML reads right anywhere.
 */

import type { Person } from './commands';

export type ChipKind = 'person' | 'date';

const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;

export function personChip(doc: Document, person: Person): HTMLElement {
  const chip = doc.createElement('span');
  chip.setAttribute('data-chip', 'person');
  chip.setAttribute('data-id', String(person.id));
  if (person.email) chip.setAttribute('data-email', person.email);
  chip.textContent = `@${person.name}`;
  return chip;
}

export function dateChip(doc: Document, iso: string, label: string): HTMLElement {
  const chip = doc.createElement('span');
  chip.setAttribute('data-chip', 'date');
  chip.setAttribute('data-date', iso);
  chip.textContent = label;
  return chip;
}

/** Put a chip at the caret, with a space after it, and the caret after that. */
export function insertChip(range: Range, chip: HTMLElement): void {
  const doc = chip.ownerDocument;
  range.deleteContents();
  const space = doc.createTextNode(' ');
  range.insertNode(space);
  range.insertNode(chip);

  const caret = doc.createRange();
  caret.setStart(space, 1);
  caret.collapse(true);
  const selection = doc.defaultView?.getSelection();
  selection?.removeAllRanges();
  selection?.addRange(caret);
}

/** What a chip stands for, read back from its attributes. */
export function readChip(chip: HTMLElement): { kind: ChipKind; id: string; label: string; email: string; date: string } {
  const kind: ChipKind = chip.getAttribute('data-chip') === 'date' ? 'date' : 'person';
  return {
    kind,
    id: chip.getAttribute('data-id') ?? '',
    label: (chip.textContent ?? '').replace(/^@/, ''),
    email: chip.getAttribute('data-email') ?? '',
    date: chip.getAttribute('data-date') ?? '',
  };
}

export function setChipDate(chip: HTMLElement, iso: string, label: string): void {
  if (!ISO_DATE.test(iso)) return;
  chip.setAttribute('data-date', iso);
  if (chip.textContent !== label) chip.textContent = label;
}

/**
 * Chips and footnote markers are single units in the editor: the caret steps
 * over them and Backspace takes them whole. That is editor behaviour only, so
 * it is set on the live DOM and never saved.
 */
export function decorateAtoms(root: HTMLElement): void {
  root.querySelectorAll<HTMLElement>('[data-nk-content] :is([data-chip], [data-footnote])').forEach((atom) => {
    if (atom.getAttribute('contenteditable') !== 'false') atom.setAttribute('contenteditable', 'false');
  });
}

/** A date as YYYY-MM-DD, in local time. */
export function isoDate(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function fromIso(iso: string): Date | null {
  if (!ISO_DATE.test(iso)) return null;
  const [y, m, d] = iso.split('-').map(Number);
  const date = new Date(y, m - 1, d);
  return date.getMonth() === m - 1 ? date : null;
}

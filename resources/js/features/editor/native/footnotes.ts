/**
 * Footnotes.
 *
 * In the text, a footnote is a numbered marker, `<span data-footnote="fn-…">`,
 * that carries the note itself in `data-note`. Each page shows the notes of the
 * markers it holds in an area at its foot, and gives up that much height to
 * them, so the flow engine moves text on to the next sheet when notes grow —
 * the way a word processor lays them out.
 *
 * The note areas are the editor's view, outside the page bodies, so they are
 * never saved as such: the saved HTML has `<sup data-footnote>` markers and an
 * `<ol data-footnotes>` list of the notes at the end, read back on load.
 */

const MARKERS = '[data-nk-content] [data-footnote]';

/** Space between the text and the note area, in px. */
const GAP = 12;

let counter = 0;

export function newId(): string {
  return `fn-${Date.now().toString(36)}${(counter++).toString(36)}`;
}

export function markersIn(root: ParentNode): HTMLElement[] {
  return Array.from(root.querySelectorAll<HTMLElement>(MARKERS));
}

export function markerFor(root: ParentNode, id: string): HTMLElement | null {
  return root.querySelector<HTMLElement>(`[data-nk-content] [data-footnote="${CSS.escape(id)}"]`);
}

export function makeMarker(doc: Document, id: string, note = ''): HTMLElement {
  const marker = doc.createElement('span');
  marker.setAttribute('data-footnote', id);
  marker.setAttribute('data-note', note);
  marker.setAttribute('contenteditable', 'false');
  marker.textContent = '0';
  return marker;
}

/** A marker at the caret (after the selection, which is kept). Returns its id. */
export function insertMarker(range: Range): string {
  const doc = range.startContainer.ownerDocument!;
  const id = newId();
  const marker = makeMarker(doc, id);
  const at = range.cloneRange();
  at.collapse(false);
  at.insertNode(marker);
  return id;
}

/**
 * Number the markers in document order, and give a copied marker (pasted, or
 * duplicated by Ctrl+C / Ctrl+V) an id of its own.
 */
export function renumber(root: ParentNode): void {
  const seen = new Set<string>();
  const all = Array.from(root.querySelectorAll<HTMLElement>('[data-footnote]')).filter(
    (marker) => !marker.closest('[data-nk-notes], [data-nk-repeat]')
  );
  all.forEach((marker, index) => {
    let id = marker.getAttribute('data-footnote') ?? '';
    if (!/^fn-[a-z0-9-]+$/i.test(id) || seen.has(id)) {
      id = newId();
      marker.setAttribute('data-footnote', id);
    }
    seen.add(id);
    const number = String(index + 1);
    if (marker.textContent !== number) marker.textContent = number;
  });
}

/** Copy what was typed in the note areas back onto the markers. */
export function collect(root: HTMLElement): void {
  root.querySelectorAll<HTMLElement>('[data-nk-notes] [data-nk-note]').forEach((note) => {
    const marker = markerFor(root, note.getAttribute('data-nk-note') ?? '');
    const text = (note.querySelector('[data-nk-note-text]')?.textContent ?? '').replace(/\u200B/g, '');
    if (marker && marker.getAttribute('data-note') !== text) marker.setAttribute('data-note', text);
  });
}

/** The editable text of a note, wherever its page is. */
export function noteText(root: HTMLElement, id: string): HTMLElement | null {
  return root.querySelector<HTMLElement>(`[data-nk-note="${CSS.escape(id)}"] [data-nk-note-text]`);
}

function makeNote(doc: Document, id: string): HTMLElement {
  const note = doc.createElement('div');
  note.className = 'nk-note';
  note.setAttribute('data-nk-note', id);

  const number = doc.createElement('span');
  number.className = 'nk-note-number';
  const text = doc.createElement('span');
  text.className = 'nk-note-text';
  text.setAttribute('data-nk-note-text', '');
  // The caret is kept by block id: a fixed one survives the note being rebuilt.
  text.setAttribute('data-nk-id', `note-${id}`);
  note.append(number, text);
  return note;
}

/**
 * Lay out the notes of the markers a page holds and make room for them.
 *
 * Note elements are moved rather than rebuilt, so the one being typed in keeps
 * its caret; `editing` names it, so a rebuilt one is editable again.
 */
export function layoutPage(page: HTMLElement, body: HTMLElement, editing: string | null, hint: string): void {
  // A header a page repeats is a copy: its notes are on the page of the original.
  const markers = Array.from(body.querySelectorAll<HTMLElement>('[data-footnote]')).filter((marker) => !marker.closest('[data-nk-repeat]'));
  let area = page.querySelector<HTMLElement>(':scope > [data-nk-notes]');

  if (!markers.length) {
    if (area) area.remove();
    if (body.style.height) body.style.removeProperty('height');
    return;
  }

  const doc = page.ownerDocument!;
  const root = (page.closest('.nk-pages') as HTMLElement | null) ?? page.parentElement ?? page;
  if (!area) {
    area = doc.createElement('div');
    area.className = 'nk-page-notes';
    area.setAttribute('data-nk-notes', '');
    area.setAttribute('contenteditable', 'false');
    page.appendChild(area);
  }

  const wanted = markers.map((marker) => marker.getAttribute('data-footnote') ?? '');
  const current = Array.from(area.children).map((child) => child.getAttribute('data-nk-note'));
  const created = new Set<string>();
  if (current.join('|') !== wanted.join('|')) {
    for (const id of wanted) {
      const existing = root.querySelector<HTMLElement>(`[data-nk-note="${CSS.escape(id)}"]`);
      if (!existing) created.add(id);
      area.appendChild(existing ?? makeNote(doc, id));
    }
    for (const child of Array.from(area.children)) {
      if (!wanted.includes(child.getAttribute('data-nk-note') ?? '')) child.remove();
    }
  }

  markers.forEach((marker, index) => {
    const note = area!.children[index] as HTMLElement;
    const number = note.querySelector<HTMLElement>('.nk-note-number')!;
    if (number.textContent !== marker.textContent) number.textContent = marker.textContent;

    const text = note.querySelector<HTMLElement>('[data-nk-note-text]')!;
    const id = wanted[index];
    if (text.getAttribute('data-nk-hint') !== hint) text.setAttribute('data-nk-hint', hint);
    // The note being written keeps what is in it, unless it was just rebuilt
    // (its marker left the page for a moment while the page was cut).
    if (id !== editing || created.has(id)) {
      const note = marker.getAttribute('data-note') ?? '';
      if (text.textContent !== note) text.textContent = note;
    }
    if (id === editing) {
      if (text.getAttribute('contenteditable') !== 'true') text.setAttribute('contenteditable', 'true');
    } else if (text.hasAttribute('contenteditable')) {
      text.removeAttribute('contenteditable');
    }
  });

  const height = `calc(var(--nk-page-content-height, 931px) - ${area.offsetHeight + GAP}px)`;
  if (body.style.height !== height) body.style.height = height;
}

/** Every page at once, for a document that is not paginated. */
export function layoutAll(root: HTMLElement, editing: string | null, hint: string): void {
  root.querySelectorAll<HTMLElement>('[data-nk-page]').forEach((page) => {
    const body = page.querySelector<HTMLElement>('[data-nk-content]');
    if (body) layoutPage(page, body, editing, hint);
  });
}

/**
 * Saved HTML into editor markers: each `<sup data-footnote>` becomes a marker
 * carrying its note from the `<ol data-footnotes>` list, which goes away.
 */
export function lift(container: ParentNode): void {
  const notes = new Map<string, string>();
  container.querySelectorAll<HTMLElement>('ol[data-footnotes]').forEach((list) => {
    list.querySelectorAll<HTMLElement>('li[data-footnote]').forEach((item) => {
      notes.set(item.getAttribute('data-footnote') ?? '', (item.textContent ?? '').trim());
    });
    list.remove();
  });

  container.querySelectorAll<HTMLElement>('sup[data-footnote], span[data-footnote]').forEach((found) => {
    const id = found.getAttribute('data-footnote') ?? '';
    const note = found.getAttribute('data-note') ?? notes.get(id) ?? '';
    const marker = makeMarker(found.ownerDocument!, id, note);
    found.replaceWith(marker);
  });

  renumber(container);
}

/** Editor markers into saved HTML: numbered `<sup>` links and the list of notes at the end. */
export function write(container: HTMLElement): void {
  const markers = Array.from(container.querySelectorAll<HTMLElement>('span[data-footnote]'));
  if (!markers.length) return;

  const doc = container.ownerDocument!;
  const list = doc.createElement('ol');
  list.setAttribute('data-footnotes', '');

  markers.forEach((marker, index) => {
    const id = marker.getAttribute('data-footnote') ?? '';
    const sup = doc.createElement('sup');
    sup.setAttribute('data-footnote', id);
    const link = doc.createElement('a');
    link.setAttribute('href', `#${id}`);
    link.textContent = String(index + 1);
    sup.appendChild(link);

    const item = doc.createElement('li');
    item.id = id;
    item.setAttribute('data-footnote', id);
    item.textContent = marker.getAttribute('data-note') ?? '';
    list.appendChild(item);

    marker.replaceWith(sup);
  });

  container.appendChild(list);
}

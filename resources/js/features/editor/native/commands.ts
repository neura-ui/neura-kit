/**
 * The menus typing opens: "/" for commands, "@" for people and dates.
 *
 * Labels come from the editor's phrases at run time; `keywords` let a command
 * be found in either language and by its usual names (h1, todo, hr…). No
 * imports, so it runs in Node.
 */

export interface CommandSpec {
  id: string;
  group: 'text' | 'insert';
  keywords: string[];
  /** A 16×16 stroke icon. */
  icon: string;
}

export const COMMANDS: CommandSpec[] = [
  { id: 'paragraph', group: 'text', keywords: ['normal', 'text', 'texte', 'p'], icon: 'M3 3.5h10M3 8h10M3 12.5h6' },
  { id: 'title', group: 'text', keywords: ['title', 'titre'], icon: 'M3 3.5h10M8 3.5v9' },
  { id: 'subtitle', group: 'text', keywords: ['subtitle', 'sous-titre'], icon: 'M4 4.5h8M8 4.5v8M5 12.5h6' },
  { id: 'heading1', group: 'text', keywords: ['h1', 'heading', 'titre'], icon: 'M3 3.5v9M9 3.5v9M3 8h6M11.5 6l1.5-1v7.5' },
  { id: 'heading2', group: 'text', keywords: ['h2', 'heading', 'titre'], icon: 'M2.5 3.5v9M8 3.5v9M2.5 8H8M10.5 6.5a1.5 1.5 0 0 1 3 .3c0 1.2-3 2.4-3 3.7h3' },
  { id: 'heading3', group: 'text', keywords: ['h3', 'heading', 'titre'], icon: 'M2.5 3.5v9M8 3.5v9M2.5 8H8M10.5 5.5h3l-1.5 2a1.5 1.5 0 1 1-1.5 2' },
  { id: 'bulletList', group: 'text', keywords: ['ul', 'bullet', 'list', 'liste', 'puces'], icon: 'M6 4h7.5M6 8h7.5M6 12h7.5M3 4h.01M3 8h.01M3 12h.01' },
  { id: 'orderedList', group: 'text', keywords: ['ol', 'numbered', 'list', 'liste', 'numerotee'], icon: 'M6.5 4h7M6.5 8h7M6.5 12h7M2.5 3l1-.5v3' },
  { id: 'checklist', group: 'text', keywords: ['todo', 'task', 'checklist', 'tache', 'cases'], icon: 'M2.5 3.5h3v3h-3zM3.25 11.75l1 1 1.5-2M8 5h5.5M8 11.5h5.5' },
  { id: 'quote', group: 'text', keywords: ['quote', 'blockquote', 'citation'], icon: 'M4 4h3v3.5L5 11M9.5 4h3v3.5l-2 3.5' },
  { id: 'codeblock', group: 'text', keywords: ['code', 'pre'], icon: 'M5.5 4.5 2 8l3.5 3.5M10.5 4.5 14 8l-3.5 3.5' },
  { id: 'table', group: 'insert', keywords: ['table', 'tableau', 'grid', 'grille'], icon: 'M2.5 3h11v10h-11zM2.5 6.5h11M2.5 10h11M6.5 3v10' },
  { id: 'image', group: 'insert', keywords: ['image', 'picture', 'photo', 'img'], icon: 'M2.5 3h11v10h-11zM2.5 11l3.5-3.5 3 3 2-2 2.5 2.5' },
  { id: 'link', group: 'insert', keywords: ['link', 'lien', 'url'], icon: 'M6.5 9.5l3-3M7 4.5l1.2-1.2a2.5 2.5 0 0 1 3.5 3.5L10.5 8M9 11.5l-1.2 1.2a2.5 2.5 0 0 1-3.5-3.5L5.5 8' },
  { id: 'divider', group: 'insert', keywords: ['hr', 'divider', 'rule', 'separateur', 'ligne'], icon: 'M2 8h12' },
  { id: 'pageBreak', group: 'insert', keywords: ['page', 'break', 'saut'], icon: 'M3 2.5v3h10v-3M3 13.5v-3h10v3M2 8h2M6 8h4M12 8h2' },
  { id: 'date', group: 'insert', keywords: ['date', 'today', 'aujourd', 'jour'], icon: 'M2.5 3.5h11v10h-11zM2.5 6.5h11M5.5 2v3M10.5 2v3' },
  { id: 'toc', group: 'insert', keywords: ['toc', 'contents', 'sommaire', 'table des matieres'], icon: 'M2.5 4h.01M2.5 8h.01M2.5 12h.01M5 4h8.5M6.5 8h7M6.5 12h7' },
  { id: 'footnote', group: 'insert', keywords: ['footnote', 'note', 'renvoi', 'bas de page'], icon: 'M3 12.5h10M4 3.5v5M10.5 3.5l2 2.5-2 2.5' },
  { id: 'special', group: 'insert', keywords: ['emoji', 'special', 'character', 'symbol', 'caractere', 'symbole'], icon: 'M8 1.5v13M1.5 8h13M3.4 3.4l9.2 9.2M12.6 3.4l-9.2 9.2' },
  { id: 'mention', group: 'insert', keywords: ['mention', 'person', 'people', 'personne', 'chip', '@'], icon: 'M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM10.5 8v1a1.75 1.75 0 0 0 3.5 0V8a6 6 0 1 0-2.4 4.8' },
];

/** Lower case without accents, for matching typed text against labels. */
export function fold(text: string): string {
  return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

/**
 * The items a query keeps, best first: labels starting with it, then labels
 * containing it, then keywords starting with it. Ties keep their order.
 */
export function filterCommands<T extends { label: string; keywords: string[] }>(items: T[], query: string): T[] {
  const q = fold(query.trim());
  if (!q) return items.slice();

  const scored: Array<{ item: T; score: number; index: number }> = [];
  items.forEach((item, index) => {
    const label = fold(item.label);
    let score = -1;
    if (label.startsWith(q) || label.split(/\s+/).some((word) => word.startsWith(q))) score = 0;
    else if (label.includes(q)) score = 1;
    else if (item.keywords.some((keyword) => fold(keyword).startsWith(q))) score = 2;
    if (score >= 0) scored.push({ item, score, index });
  });

  return scored.sort((a, b) => a.score - b.score || a.index - b.index).map((entry) => entry.item);
}

/**
 * What was typed after the "/" that opened the menu, or null when the menu
 * should close: the caret went back over the slash, the slash is gone, or the
 * text no longer looks like a command.
 */
export function slashQuery(text: string, slashAt: number, caret: number, trigger = '/'): string | null {
  if (caret <= slashAt || text.charAt(slashAt) !== trigger) return null;
  const query = text.slice(slashAt + 1, caret);
  if (query.length > 30 || /\s\s/.test(query) || /^\s/.test(query)) return null;
  return query;
}

export interface Person {
  id: string | number;
  name: string;
  email?: string;
  avatar?: string;
}

/**
 * The people a query after "@" keeps, best first: a name starting with it, then
 * a word of the name or the email starting with it, then anywhere in either.
 */
export function filterPeople<T extends Person>(people: T[], query: string, limit = 6): T[] {
  const q = fold(query.trim());
  if (!q) return people.slice(0, limit);

  const scored: Array<{ person: T; score: number; index: number }> = [];
  people.forEach((person, index) => {
    const name = fold(person.name);
    const email = fold(person.email ?? '');
    let score = -1;
    if (name.startsWith(q)) score = 0;
    else if (name.split(/[\s.'-]+/).some((word) => word.startsWith(q)) || email.startsWith(q)) score = 1;
    else if (name.includes(q) || email.includes(q)) score = 2;
    if (score >= 0) scored.push({ person, score, index });
  });

  return scored
    .sort((a, b) => a.score - b.score || a.index - b.index)
    .slice(0, limit)
    .map((entry) => entry.person);
}

/** One or two letters for an avatar: the first and last words' initials. */
export function initials(name: string): string {
  const words = name.trim().split(/\s+/).filter(Boolean);
  if (!words.length) return '?';
  const first = words[0].charAt(0);
  const last = words.length > 1 ? words[words.length - 1].charAt(0) : '';
  return (first + last).toUpperCase();
}

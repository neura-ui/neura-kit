/**
 * Comment threads as plain data, and how they are read back from a saved value.
 *
 * The threads come from the document itself — a hidden attribute in HTML,
 * `comments` in JSON — so anything can be in there: only well-formed threads
 * are kept, with every text cut to a sane length. No imports, so it runs in Node.
 */

/** Someone a comment mentions with "@Name", told about it by the app. */
export interface CommentMention {
  id: string;
  name: string;
  email: string;
}

export interface ReviewComment {
  author: string;
  time: string;
  text: string;
  /** When it was last changed, if it was. */
  edited?: string;
  mentions?: CommentMention[];
}

export interface Thread {
  id: string;
  resolved: boolean;
  quote: string;
  comments: ReviewComment[];
}

const COMMENT_ID = /^c-[a-z0-9-]+$/i;

const clean = (value: unknown, max: number): string => (typeof value === 'string' ? value.slice(0, max) : '');

/** Threads read from anywhere (a saved value, JSON), keeping only well-formed ones. */
export function validThreads(value: unknown): Thread[] {
  if (!Array.isArray(value)) return [];
  const threads: Thread[] = [];
  for (const raw of value) {
    if (!raw || typeof raw !== 'object') continue;
    const thread = raw as Record<string, unknown>;
    const id = clean(thread.id, 64);
    if (!COMMENT_ID.test(id) || threads.some((other) => other.id === id)) continue;
    const comments = (Array.isArray(thread.comments) ? thread.comments : [])
      .filter((comment): comment is Record<string, unknown> => Boolean(comment) && typeof comment === 'object')
      .map((comment) => {
        const kept: ReviewComment = { author: clean(comment.author, 120), time: clean(comment.time, 40), text: clean(comment.text, 5000) };
        const edited = clean(comment.edited, 40);
        if (edited) kept.edited = edited;
        const mentions = validMentions(comment.mentions);
        if (mentions.length) kept.mentions = mentions;
        return kept;
      })
      .filter((comment) => comment.text.trim() !== '');
    if (!comments.length) continue;
    threads.push({ id, resolved: thread.resolved === true, quote: clean(thread.quote, 300), comments });
  }
  return threads;
}


function validMentions(value: unknown): CommentMention[] {
  if (!Array.isArray(value)) return [];
  return value
    .filter((person): person is Record<string, unknown> => Boolean(person) && typeof person === 'object')
    .map((person) => ({
      id: typeof person.id === 'number' ? String(person.id) : clean(person.id, 64),
      name: clean(person.name, 120),
      email: clean(person.email, 200),
    }))
    .filter((person) => person.name.trim() !== '')
    .slice(0, 20);
}

/* ------------------------------------------------------------ mentions -- */

/**
 * The "@query" being typed just before `caret` in a comment: where its "@" is
 * and what follows. An "@" counts at the start or after a space or a bracket,
 * so an email address is left alone.
 */
export function mentionQuery(text: string, caret: number): { at: number; query: string } | null {
  const before = text.slice(0, caret);
  const at = before.lastIndexOf('@');
  if (at < 0 || (at > 0 && !/[\s(]/.test(before[at - 1]))) return null;
  const query = before.slice(at + 1);
  if (query.length > 30 || /\n/.test(query) || /\s\s/.test(query) || /^\s/.test(query)) return null;
  return { at, query };
}

/** The text with "@query" (from `at` to `caret`) turned into "@Name ", and where the caret goes. */
export function putMention(text: string, at: number, caret: number, name: string): { text: string; caret: number } {
  const mention = `@${name} `;
  const after = text.slice(caret).replace(/^ /, '');
  return { text: text.slice(0, at) + mention + after, caret: at + mention.length };
}

/** Who a comment still mentions: the people whose "@Name" is in its text, once each. */
export function mentionsIn(text: string, people: CommentMention[]): CommentMention[] {
  const kept: CommentMention[] = [];
  for (const person of people) {
    if (!person.name || !text.includes(`@${person.name}`)) continue;
    if (kept.some((other) => other.id === person.id && other.name === person.name)) continue;
    kept.push(person);
  }
  return kept;
}

/** A comment's text in runs, its mentions apart, so they can be drawn as chips. */
export function commentRuns(text: string, mentions: CommentMention[] = []): Array<{ text: string; mention: boolean }> {
  const names = [...new Set(mentions.map((person) => `@${person.name}`))].filter((name) => name.length > 1).sort((a, b) => b.length - a.length);
  if (!names.length) return [{ text, mention: false }];

  const runs: Array<{ text: string; mention: boolean }> = [];
  let rest = text;
  while (rest) {
    let first: { at: number; name: string } | null = null;
    for (const name of names) {
      const at = rest.indexOf(name);
      if (at >= 0 && (!first || at < first.at)) first = { at, name };
    }
    if (!first) {
      runs.push({ text: rest, mention: false });
      break;
    }
    if (first.at) runs.push({ text: rest.slice(0, first.at), mention: false });
    runs.push({ text: first.name, mention: true });
    rest = rest.slice(first.at + first.name.length);
  }
  return runs;
}

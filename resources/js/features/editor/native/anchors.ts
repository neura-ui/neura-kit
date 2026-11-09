/**
 * Anchors for headings, so a table of contents can link to them.
 *
 * An anchor is made from the heading's text the first time it is needed and
 * then kept, so a link to it survives the heading being reworded. Two headings
 * never share one: a copy (Enter at the end of a heading clones its id) gets
 * its own. No imports, so it runs in Node.
 */

const ANCHOR = /^h-[a-z0-9][a-z0-9-]*$/;

/** Lower case, accents dropped (and ᵉ, ² read as e, 2), words joined by dashes. */
export function slugify(text: string): string {
  const slug = text
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60)
    .replace(/-+$/, '');
  return slug || 'section';
}

/** One anchor per heading, in order: the one it has when it is the first to have it, else a new one. */
export function anchorsFor(headings: Array<{ text: string; current?: string | null }>): string[] {
  const kept = headings.map(({ current }, index) =>
    current && ANCHOR.test(current) && headings.findIndex((heading) => heading.current === current) === index ? current : null
  );
  const taken = new Set(kept.filter((anchor): anchor is string => anchor !== null));

  return headings.map(({ text }, index) => {
    const own = kept[index];
    if (own) return own;
    const base = `h-${slugify(text)}`;
    let candidate = base;
    for (let n = 2; taken.has(candidate); n += 1) candidate = `${base}-${n}`;
    taken.add(candidate);
    return candidate;
  });
}

// Utility to merge Tailwind classes, similar to clsx/twMerge
// This is a minimal implementation for demo; consider using 'tailwind-merge' for production
export function merge(...inputs: Array<string | undefined | null | false>): string {
  // Remove falsy values, split by whitespace, and deduplicate
  const classList = inputs
    .filter(Boolean)
    .flatMap(str => (str as string).split(/\s+/))
    .filter(Boolean);
  // Deduplicate, keeping last occurrence (Notion/shadcn style)
  const seen = new Map<string, number>();
  classList.forEach((cls, i) => seen.set(cls, i));
  return classList.filter((cls, i) => seen.get(cls) === i).join(' ');
}

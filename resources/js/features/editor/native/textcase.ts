/**
 * Text case changes, as plain text functions.
 *
 * `before` is the text right before the part being changed (the rest of the
 * word or sentence), so a selection that starts mid-word or mid-sentence is
 * capitalised the way the whole would be. No imports, so it runs in Node.
 */

export type CaseMode = 'lower' | 'upper' | 'title' | 'sentence';

const LETTER = /[\p{L}\p{N}]/u;
/** Inside a word: letters, digits, and the apostrophes and hyphens that join them. */
const JOINER = /[\p{L}\p{N}'’-]/u;

export function changeCase(text: string, mode: CaseMode, before = ''): string {
  if (mode === 'lower') return text.toLocaleLowerCase();
  if (mode === 'upper') return text.toLocaleUpperCase();

  let previous = before.slice(-1);
  // A sentence starts at the beginning, or after . ! ? and spaces.
  let sentenceStart = mode === 'sentence' && (/^\s*$/.test(before) || /[.!?]\s+$/.test(before) || /[.!?]$/.test(before));
  let out = '';

  for (const char of text) {
    const isLetter = LETTER.test(char);
    if (mode === 'title') {
      const startsWord = isLetter && !JOINER.test(previous);
      out += startsWord ? char.toLocaleUpperCase() : char.toLocaleLowerCase();
    } else {
      if (isLetter && sentenceStart) {
        out += char.toLocaleUpperCase();
        sentenceStart = false;
      } else {
        out += char.toLocaleLowerCase();
        if (/[.!?]/.test(char)) sentenceStart = true;
        else if (isLetter) sentenceStart = false;
      }
    }
    previous = char;
  }

  return out;
}

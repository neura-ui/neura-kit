/**
 * Voice typing: what the speech recogniser hears, as text to put in.
 *
 * Punctuation can be said ("virgule", "question mark"), and so can a new line
 * or a new paragraph. Spacing follows the language — in French a thin space
 * before ? ! : ; and inside « » — and a sentence starts with a capital. No
 * imports, so it runs in Node.
 */

export type DictationPiece = { text: string } | { command: 'line' | 'paragraph' };

type Language = 'fr' | 'en';

const PUNCTUATION: Record<Language, Array<[string, string]>> = {
  fr: [
    ["point d'interrogation", '?'],
    ["point d'exclamation", '!'],
    ['points de suspension', '…'],
    ['point-virgule', ';'],
    ['point virgule', ';'],
    ['deux-points', ':'],
    ['deux points', ':'],
    ['ouvrez les guillemets', '«'],
    ['fermez les guillemets', '»'],
    ['ouvrez la parenthèse', '('],
    ['fermez la parenthèse', ')'],
    ['virgule', ','],
    ['point', '.'],
  ],
  en: [
    ['question mark', '?'],
    ['exclamation mark', '!'],
    ['exclamation point', '!'],
    ['semicolon', ';'],
    ['colon', ':'],
    ['comma', ','],
    ['full stop', '.'],
    ['period', '.'],
    ['ellipsis', '…'],
    ['open quote', '“'],
    ['close quote', '”'],
    ['open parenthesis', '('],
    ['close parenthesis', ')'],
  ],
};

const COMMANDS: Record<Language, Array<[string, 'line' | 'paragraph']>> = {
  fr: [
    ['nouveau paragraphe', 'paragraph'],
    ['nouvelle ligne', 'line'],
    ['à la ligne', 'line'],
  ],
  en: [
    ['new paragraph', 'paragraph'],
    ['new line', 'line'],
  ],
};

const THIN = ' ';

const languageOf = (lang: string): Language => (lang.toLowerCase().startsWith('fr') ? 'fr' : 'en');

/** A spoken phrase as a whole-word pattern (accents included, which `\b` misses). */
const phrase = (words: string): RegExp => new RegExp(`(^|\\s)${words.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?=\\s|$)`, 'giu');

/** Said punctuation turned into signs, spaced the way the language wants. */
function punctuate(text: string, lang: Language): string {
  let result = ` ${text} `;
  for (const [words, sign] of PUNCTUATION[lang]) result = result.replace(phrase(words), `$1${sign}`);
  result = result.replace(/\s+/g, ' ');

  // No space before closing signs, none after opening ones.
  result = result.replace(/ ([,.…)”])/g, '$1').replace(/([(“]) /g, '$1');
  if (lang === 'fr') {
    // French: a thin no-break space before ? ! : ; and inside « ».
    result = result.replace(/ ?([?!:;])/g, `${THIN}$1`).replace(/« ?/g, `«${THIN}`).replace(/ ?»/g, `${THIN}»`);
  } else {
    result = result.replace(/ ([?!:;])/g, '$1');
  }
  return result.trim();
}

/** A transcript as text and the line or paragraph breaks said in it. */
export function parseDictation(transcript: string, lang: string): DictationPiece[] {
  const language = languageOf(lang);
  let rest = transcript.trim();
  const pieces: DictationPiece[] = [];

  while (rest) {
    let first: { at: number; length: number; command: 'line' | 'paragraph' } | null = null;
    for (const [words, command] of COMMANDS[language]) {
      const match = phrase(words).exec(rest);
      if (match && (!first || match.index < first.at)) first = { at: match.index, length: match[0].length, command };
    }
    if (!first) {
      pieces.push({ text: punctuate(rest, language) });
      break;
    }
    const before = rest.slice(0, first.at).trim();
    if (before) pieces.push({ text: punctuate(before, language) });
    pieces.push({ command: first.command });
    rest = rest.slice(first.at + first.length).trim();
  }

  return pieces.filter((piece) => !('text' in piece) || piece.text !== '');
}

/**
 * Text as it goes in after what is already there: a space between words but
 * not before punctuation, and a capital where a sentence starts.
 */
export function joinDictation(before: string, text: string, lang: string): string {
  if (!text) return '';
  const language = languageOf(lang);
  const tail = before.replace(/[\s  ]+$/, '');
  const startsSentence = tail === '' || /[.!?…]$/.test(tail);

  let result = text;
  if (startsSentence) result = result.replace(/^\p{Ll}/u, (letter) => letter.toUpperCase());
  // Within the piece too: after . ! ? a new sentence starts.
  result = result.replace(/([.!?…][\s ]+)(\p{Ll})/gu, (_, gap: string, letter: string) => gap + letter.toUpperCase());

  const glued = /^[,.…)”]/.test(result) || (language === 'en' && /^[?!:;]/.test(result)) || result.startsWith(THIN);
  const needsSpace = before !== '' && !/[\s  («“]$/.test(before) && !glued;
  return (needsSpace ? ' ' : '') + result;
}

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { CHARACTERS, findCharacters } from '../../../resources/js/features/editor/native/characters.ts';
import { joinDictation, parseDictation } from '../../../resources/js/features/editor/native/dictation.ts';

const THIN = ' ';

test('special characters are found by name, in English or French', () => {
  assert.equal(findCharacters('euro')[0].char, '€');
  assert.ok(findCharacters('flèche droite').some((entry) => entry.char === '→'));
  assert.ok(findCharacters('coeur').some((entry) => entry.char === '❤️'));
  assert.ok(findCharacters('heart').some((entry) => entry.char === '❤️'));
  assert.equal(findCharacters('espace fine')[0].char, ' ');
  assert.deepEqual(findCharacters('zzzz'), []);
  // No search: the whole group.
  assert.ok(findCharacters('', 'greek').every((entry) => entry.group === 'greek'));
  assert.ok(CHARACTERS.length > 150);
});

test('said punctuation becomes signs, spaced as French wants', () => {
  assert.deepEqual(parseDictation("bonjour virgule comment ça va point d'interrogation", 'fr-FR'), [{ text: `bonjour, comment ça va${THIN}?` }]);
  assert.deepEqual(parseDictation('ouvrez les guillemets oui fermez les guillemets point', 'fr'), [{ text: `«${THIN}oui${THIN}». ` .trim() }]);
  assert.deepEqual(parseDictation('hello comma how are you question mark', 'en-US'), [{ text: 'hello, how are you?' }]);
  // A "point" inside a word is no full stop.
  assert.deepEqual(parseDictation('un pointeur', 'fr'), [{ text: 'un pointeur' }]);
});

test('a new line or a new paragraph can be said', () => {
  assert.deepEqual(parseDictation('première ligne nouvelle ligne deuxième nouveau paragraphe suite', 'fr'), [
    { text: 'première ligne' },
    { command: 'line' },
    { text: 'deuxième' },
    { command: 'paragraph' },
    { text: 'suite' },
  ]);
  assert.deepEqual(parseDictation('new paragraph', 'en'), [{ command: 'paragraph' }]);
});

test('dictated text joins what is there: spaces, and capitals where a sentence starts', () => {
  assert.equal(joinDictation('', 'bonjour à tous', 'fr'), 'Bonjour à tous');
  assert.equal(joinDictation('Le budget', 'est validé', 'fr'), ' est validé');
  assert.equal(joinDictation('Fin.', 'nouvelle phrase. et une autre', 'fr'), ' Nouvelle phrase. Et une autre');
  assert.equal(joinDictation('Mot', ', suite', 'fr'), ', suite');
  assert.equal(joinDictation('Done ', 'next', 'en'), 'next');
  assert.equal(joinDictation('Really', '? yes', 'en'), '? Yes');
});

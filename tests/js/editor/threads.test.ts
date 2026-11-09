import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validThreads } from '../../../resources/js/features/editor/native/threads.ts';

const thread = (overrides: Record<string, unknown> = {}) => ({
  id: 'c-abc1',
  resolved: false,
  quote: 'budget Meta',
  comments: [{ author: 'Akram', time: '2026-11-08T10:00:00Z', text: 'À vérifier' }],
  ...overrides,
});

test('well-formed threads come back as they were', () => {
  assert.deepEqual(validThreads([thread(), thread({ id: 'c-abc2', resolved: true })]), [thread(), thread({ id: 'c-abc2', resolved: true })]);
});

test('anything that is not a thread is left out', () => {
  assert.deepEqual(validThreads('nope'), []);
  assert.deepEqual(validThreads(null), []);
  assert.deepEqual(validThreads([null, 42, 'x', {}]), []);
  // Ids are ours, and never twice.
  assert.deepEqual(validThreads([thread({ id: 'evil"><script>' })]), []);
  assert.equal(validThreads([thread(), thread()]).length, 1);
  // A thread with nothing said in it is dropped; so are empty comments.
  assert.deepEqual(validThreads([thread({ comments: [] })]), []);
  assert.deepEqual(validThreads([thread({ comments: [{ author: 'A', time: '', text: '   ' }, 'x'] })]), []);
});

test('fields are strings of a sane length, resolved only when true', () => {
  const [kept] = validThreads([thread({ resolved: 'yes', quote: 'q'.repeat(1000), comments: [{ author: 7, time: {}, text: 't'.repeat(9000) }] })]);
  assert.equal(kept.resolved, false);
  assert.equal(kept.quote.length, 300);
  assert.deepEqual([kept.comments[0].author, kept.comments[0].time, kept.comments[0].text.length], ['', '', 5000]);
});

import { commentRuns, mentionQuery, mentionsIn, putMention } from '../../../resources/js/features/editor/native/threads.ts';

test('an edited comment and its mentions are kept, checked', () => {
  const [kept] = validThreads([thread({ comments: [{ author: 'A', time: 't', text: '@Léa ok', edited: '2026-11-08T11:00:00Z', mentions: [{ id: 7, name: 'Léa', email: 'lea@x.fr' }, { id: 'x' }, 'bad'] }] })]);
  assert.equal(kept.comments[0].edited, '2026-11-08T11:00:00Z');
  assert.deepEqual(kept.comments[0].mentions, [{ id: '7', name: 'Léa', email: 'lea@x.fr' }]);
});

test('an "@" being typed in a comment is found, an email is not', () => {
  assert.deepEqual(mentionQuery('Voir avec @Lé', 13), { at: 10, query: 'Lé' });
  assert.deepEqual(mentionQuery('@', 1), { at: 0, query: '' });
  assert.equal(mentionQuery('ecrire a lea@x.fr', 17), null);
  assert.equal(mentionQuery('@Léa  plus loin', 15), null);
  assert.equal(mentionQuery('rien', 4), null);
});

test('a picked person replaces the query, the caret after the name', () => {
  assert.deepEqual(putMention('Voir avec @Lé merci', 10, 13, 'Léa Martin'), { text: 'Voir avec @Léa Martin merci', caret: 22 });
  assert.deepEqual(putMention('@', 0, 1, 'Tom'), { text: '@Tom ', caret: 5 });
});

test('only people still named in the text are mentioned, drawn apart', () => {
  const lea = { id: '1', name: 'Léa Martin', email: '' };
  const tom = { id: '2', name: 'Tom', email: '' };
  assert.deepEqual(mentionsIn('Merci @Léa Martin', [lea, tom, lea]), [lea]);
  assert.deepEqual(commentRuns('Merci @Léa Martin et @Tom !', [lea, tom]), [
    { text: 'Merci ', mention: false },
    { text: '@Léa Martin', mention: true },
    { text: ' et ', mention: false },
    { text: '@Tom', mention: true },
    { text: ' !', mention: false },
  ]);
  assert.deepEqual(commentRuns('Sans mention'), [{ text: 'Sans mention', mention: false }]);
});

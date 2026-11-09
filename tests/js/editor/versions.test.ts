import { test } from 'node:test';
import assert from 'node:assert/strict';
import { diffWords, pairBlocks } from '../../../resources/js/features/editor/native/textdiff.ts';
import { authorColor, dayGroups, planSnapshot, prune } from '../../../resources/js/features/editor/native/versioning.ts';

const show = (ops: ReturnType<typeof diffWords>) => ops.map((op) => (op.kind === 'same' ? op.text : op.kind === 'ins' ? `+[${op.text}]` : `-[${op.text}]`)).join('');

test('a word diff says what was added and what was taken away', () => {
  assert.equal(show(diffWords('un coût sous les 18 €.', 'un coût sous les 15 €.')), 'un coût sous les -[18 ]+[15 ]€.');
  assert.equal(show(diffWords('Lancement prévu en janvier.', 'Lancement le 2 décembre.')), 'Lancement -[prévu en janvier.]+[le 2 décembre.]');
  assert.equal(show(diffWords('', 'Nouveau texte')), '+[Nouveau texte]');
  assert.equal(show(diffWords('Ancien', '')), '-[Ancien]');
  assert.deepEqual(diffWords('Pareil', 'Pareil'), [{ kind: 'same', text: 'Pareil' }]);
  // Rebuilding the text from the ops gives both versions back.
  const ops = diffWords('Google Ads reste le seul canal.', 'Google Ads reste le canal principal.');
  assert.equal(ops.filter((op) => op.kind !== 'ins').map((op) => op.text).join(''), 'Google Ads reste le seul canal.');
  assert.equal(ops.filter((op) => op.kind !== 'del').map((op) => op.text).join(''), 'Google Ads reste le canal principal.');
});

test('too long to compare word by word: the whole text is replaced', () => {
  const before = 'mot '.repeat(800);
  const after = 'autre '.repeat(800);
  assert.deepEqual(diffWords(before, after).map((op) => op.kind), ['del', 'ins']);
});

test('paragraphs are paired: unchanged ones match, changed ones face each other', () => {
  assert.deepEqual(pairBlocks(['A', 'B', 'C'], ['A', 'B2', 'C']), [
    { before: 0, after: 0 },
    { before: 1, after: 1 },
    { before: 2, after: 2 },
  ]);
  assert.deepEqual(pairBlocks(['A', 'C'], ['A', 'B', 'C']), [
    { before: 0, after: 0 },
    { before: null, after: 1 },
    { before: 1, after: 2 },
  ]);
  assert.deepEqual(pairBlocks(['A', 'B', 'C'], ['A', 'C']), [
    { before: 0, after: 0 },
    { before: 1, after: null },
    { before: 2, after: 1 },
  ]);
  assert.deepEqual(pairBlocks([], ['A']), [{ before: null, after: 0 }]);
});

test('a snapshot is a new version, an update of the last one, or nothing', () => {
  const now = Date.parse('2026-11-08T10:00:00Z');
  const minutesAgo = (m: number) => new Date(now - m * 60000).toISOString();
  const interval = 5 * 60000;
  assert.equal(planSnapshot(null, true, now, interval), 'add');
  assert.equal(planSnapshot({ time: minutesAgo(1), name: '' }, false, now, interval), 'skip');
  assert.equal(planSnapshot({ time: minutesAgo(1), name: '' }, true, now, interval), 'update');
  assert.equal(planSnapshot({ time: minutesAgo(10), name: '' }, true, now, interval), 'add');
  // A named version is never overwritten.
  assert.equal(planSnapshot({ time: minutesAgo(1), name: 'Envoyée au client' }, true, now, interval), 'add');
});

test('old unnamed versions go first; named ones stay', () => {
  const versions = [
    { id: 'v5', time: '5', name: '', authors: [] },
    { id: 'v4', time: '4', name: '', authors: [] },
    { id: 'v3', time: '3', name: 'Premier jet', authors: [] },
    { id: 'v2', time: '2', name: '', authors: [] },
    { id: 'v1', time: '1', name: '', authors: [] },
  ];
  assert.deepEqual(prune(versions, 2).map((v) => v.id), ['v5', 'v4', 'v3']);
  assert.deepEqual(prune(versions, 10).map((v) => v.id), ['v5', 'v4', 'v3', 'v2', 'v1']);
});

test('versions are grouped by day: today, yesterday, then dates', () => {
  const now = new Date(2026, 10, 8, 15, 0);
  const versions = [
    { id: 'a', time: new Date(2026, 10, 8, 10, 42).toISOString(), name: '', authors: [] },
    { id: 'b', time: new Date(2026, 10, 7, 18, 5).toISOString(), name: '', authors: [] },
    { id: 'c', time: new Date(2026, 10, 7, 11, 30).toISOString(), name: '', authors: [] },
    { id: 'd', time: new Date(2026, 10, 6, 16, 20).toISOString(), name: '', authors: [] },
  ];
  const groups = dayGroups(versions, now, { today: "Aujourd'hui", yesterday: 'Hier' }, (date) => `${date.getDate()} nov.`);
  assert.deepEqual(groups.map((group) => [group.label, group.items.map((v) => v.id).join('')]), [
    ["Aujourd'hui", 'a'],
    ['Hier', 'bc'],
    ['6 nov.', 'd'],
  ]);
});

test('each author keeps one colour', () => {
  assert.equal(authorColor('Akram Zerarka'), authorColor('Akram Zerarka'));
  assert.match(authorColor('Zoé'), /^#[0-9a-f]{6}$/i);
});

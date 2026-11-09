import { test } from 'node:test';
import assert from 'node:assert/strict';
import { byGroup, GROUP_ORDER, groupsPresent } from '../../../resources/js/features/forms/file-manager/filters.ts';

/** The type filter: which chips a folder offers, and what each keeps. */

const entries = [
  { id: 'a', group: 'document' },
  { id: 'b', group: 'folder' },
  { id: 'c', group: 'image' },
  { id: 'd', group: 'document' },
];

test('chips follow the groups present, in a fixed order', () => {
  assert.deepEqual(groupsPresent(entries), ['folder', 'image', 'document']);
  assert.deepEqual(groupsPresent([]), []);
  assert.deepEqual(GROUP_ORDER, ['folder', 'image', 'document', 'video', 'audio', 'archive', 'code']);
});

test('a group keeps its entries only; none keeps them all', () => {
  assert.deepEqual(byGroup(entries, 'document').map((entry) => entry.id), ['a', 'd']);
  assert.deepEqual(byGroup(entries, null).map((entry) => entry.id), ['a', 'b', 'c', 'd']);
  assert.deepEqual(byGroup(entries, 'video'), []);
});

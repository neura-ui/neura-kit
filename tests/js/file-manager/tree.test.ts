import { test } from 'node:test';
import assert from 'node:assert/strict';
import { folderRows, pathTo } from '../../../resources/js/features/forms/file-manager/tree.ts';

/** The side panel's folder tree: which rows show, and the path down to a folder. */

const tree = [
  { id: 'projects', name: 'Projects', type: 'folder', children: [
    { id: 'campaign', name: 'Campagne Q4', type: 'folder', children: [] },
    { id: 'brand', name: 'Brand', type: 'folder', children: [
      { id: 'logos', name: 'Logos', type: 'folder', children: [] },
    ] },
    { id: 'brief', name: 'brief.pdf' },
  ] },
  { id: 'media', name: 'Media', children: [] },
  { id: 'readme', name: 'README.md' },
];

test('only folders show, children only under an expanded parent', () => {
  assert.deepEqual(folderRows(tree, new Set()).map((row) => [row.id, row.depth, row.hasChildren, row.expanded]), [
    ['projects', 0, true, false],
    ['media', 0, false, false],
  ]);
  assert.deepEqual(folderRows(tree, new Set(['projects'])).map((row) => [row.id, row.depth]), [
    ['projects', 0],
    ['campaign', 1],
    ['brand', 1],
    ['media', 0],
  ]);
  assert.deepEqual(folderRows(tree, new Set(['projects', 'brand'])).find((row) => row.id === 'logos')?.path, ['projects', 'brand', 'logos']);
});

test('the path to a folder lists the folders from the top down to it', () => {
  assert.deepEqual(pathTo(tree, 'brand'), ['projects', 'brand']);
  assert.deepEqual(pathTo(tree, 'logos'), ['projects', 'brand', 'logos']);
  assert.deepEqual(pathTo(tree, 'media'), ['media']);
  assert.equal(pathTo(tree, 'nowhere'), null);
});

import { allFolderIds } from '../../../resources/js/features/forms/file-manager/tree.ts';

test('every folder of the tree, top down, for the "Move to" list', () => {
  assert.deepEqual(allFolderIds(tree), ['projects', 'campaign', 'brand', 'logos', 'media']);
});

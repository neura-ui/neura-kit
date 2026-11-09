import { test } from 'node:test';
import assert from 'node:assert/strict';
import { nameError, stemRange } from '../../../resources/js/features/forms/file-manager/names.ts';

/** Renaming in place: the part selected first, and the names refused. */

test('the name is selected without its extension', () => {
  assert.deepEqual(stemRange('rapport.final.pdf'), [0, 13]);
  assert.deepEqual(stemRange('archive.tar.gz'), [0, 11]);
  assert.deepEqual(stemRange('.env'), [0, 4]);
  assert.deepEqual(stemRange('LISEZMOI'), [0, 8]);
  assert.deepEqual(stemRange('Photo de l’équipe.jpeg'), [0, 17]);
  assert.deepEqual(stemRange('point-final.'), [0, 12]);
});

test('empty names, slashes and names already taken are refused', () => {
  assert.equal(nameError('', []), 'empty');
  assert.equal(nameError('   ', []), 'empty');
  assert.equal(nameError('a/b', []), 'slash');
  assert.equal(nameError('a\\b', []), 'slash');
  assert.equal(nameError('Brand', ['brand', 'Media']), 'taken');
  assert.equal(nameError('Brand', ['brand'], 'Brand'), null);
  assert.equal(nameError('Brand 2', ['brand']), null);
});

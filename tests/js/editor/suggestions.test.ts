import { test } from 'node:test';
import assert from 'node:assert/strict';
import { addChange, parseBlockSuggestion, parseFormat, sameLook, writeBlockSuggestion } from '../../../resources/js/features/editor/native/suggestions.ts';

test('a formatting change read back keeps known marks and safe values only', () => {
  assert.deepEqual(parseFormat('[{"mark":"bold","after":true,"before":false},{"mark":"color","after":"#ff0000","before":null}]'), [
    { mark: 'bold', after: true, before: false },
    { mark: 'color', after: '#ff0000', before: null },
  ]);
  assert.deepEqual(parseFormat('[{"mark":"onclick","after":true}]'), []);
  assert.deepEqual(parseFormat('[{"mark":"color","after":"url(javascript:x)","before":null}]'), [{ mark: 'color', after: null, before: null }]);
  assert.deepEqual(parseFormat('nope'), []);
  assert.deepEqual(parseFormat(null), []);
});

test('changing a mark again keeps what it was at first, and changing it back drops it', () => {
  const bold = addChange([], { mark: 'bold', after: true, before: false });
  assert.deepEqual(bold, [{ mark: 'bold', after: true, before: false }]);
  assert.deepEqual(addChange(bold, { mark: 'bold', after: false, before: true }), []);
  const red = addChange([], { mark: 'color', after: '#f00', before: '#00f' });
  assert.deepEqual(addChange(red, { mark: 'color', after: '#0f0', before: '#f00' }), [{ mark: 'color', after: '#0f0', before: '#00f' }]);
  assert.deepEqual(addChange(red, { mark: 'color', after: '#00F', before: '#f00' }), []);
});

test('a block suggestion is read back only when well-formed', () => {
  const split = { id: 's-abc', author: 'Léa', time: '2026-11-08T10:00:00Z' };
  assert.deepEqual(parseBlockSuggestion(writeBlockSuggestion(split), 'split'), split);
  assert.equal(parseBlockSuggestion('{"id":"x"}', 'join'), null);
  assert.equal(parseBlockSuggestion('{bad', 'join'), null);
  const style = { ...split, before: { name: 'paragraph', align: 'center', spacing: '1.5', indent: '' } };
  assert.deepEqual(parseBlockSuggestion(JSON.stringify(style), 'style'), style);
  // A style suggestion needs the look it would go back to.
  assert.equal(parseBlockSuggestion(writeBlockSuggestion(split), 'style'), null);
  assert.equal(parseBlockSuggestion(JSON.stringify({ ...split, before: { name: '<p>' } }), 'style'), null);
  assert.ok(sameLook(style.before, { ...style.before }));
});

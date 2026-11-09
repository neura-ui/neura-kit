import { test } from 'node:test';
import assert from 'node:assert/strict';
import { boxFrom, hits } from '../../../resources/js/features/forms/file-manager/lasso.ts';

/** The lasso: the box a drag draws, and the entries it touches. */

test('a box is drawn whichever way the pointer goes', () => {
  assert.deepEqual(boxFrom({ x: 10, y: 40 }, { x: 2, y: 5 }), { left: 2, top: 5, right: 10, bottom: 40 });
  assert.deepEqual(boxFrom({ x: 2, y: 5 }, { x: 10, y: 40 }), { left: 2, top: 5, right: 10, bottom: 40 });
});

test('the entries touched are picked, a shared edge included', () => {
  const box = { left: 0, top: 0, right: 100, bottom: 50 };
  const entries = [
    { id: 'inside', left: 10, top: 10, right: 40, bottom: 40 },
    { id: 'edge', left: 100, top: 20, right: 150, bottom: 30 },
    { id: 'across', left: 90, top: 45, right: 200, bottom: 90 },
    { id: 'below', left: 0, top: 51, right: 100, bottom: 80 },
    { id: 'right', left: 101, top: 0, right: 150, bottom: 50 },
  ];
  assert.deepEqual(hits(box, entries), ['inside', 'edge', 'across']);
});

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { dragColumns, dragWidth, toPercent } from '../../../resources/js/features/editor/native/sizing.ts';

test('an image corner sets a width in percent of the page body', () => {
  assert.equal(dragWidth(300, 60, 600, 'right'), 60);
  // A left corner grows the image when dragged left.
  assert.equal(dragWidth(300, -60, 600, 'left'), 60);
  assert.equal(dragWidth(300, 60, 600, 'left'), 40);
  // Never narrower than 10%, never wider than the page.
  assert.equal(dragWidth(300, -1000, 600, 'right'), 10);
  assert.equal(dragWidth(300, 1000, 600, 'right'), 100);
  assert.equal(dragWidth(300, 10, 0, 'right'), 100);
});

test('a column border only moves the two columns beside it', () => {
  assert.deepEqual(dragColumns([100, 100, 100], 0, 30), [130, 70, 100]);
  assert.deepEqual(dragColumns([100, 100, 100], 1, -30), [100, 70, 130]);
  // Each keeps a minimum width.
  assert.deepEqual(dragColumns([100, 100, 100], 0, 500, 24), [176, 24, 100]);
  // Outer edges do not move.
  assert.deepEqual(dragColumns([100, 100], 1, 30), [100, 100]);
  assert.deepEqual(dragColumns([100, 100], -1, 30), [100, 100]);
});

test('widths turn into percentages adding up to 100', () => {
  assert.deepEqual(toPercent([200, 100, 100]), [50, 25, 25]);
  assert.deepEqual(toPercent([0, 0]), [50, 50]);
});

import { dragInsets, formatInsets, parseInsets, turnAngle, turnInsets, NO_CROP } from '../../../resources/js/features/editor/native/sizing.ts';

test('a crop handle takes from its side, never past the other', () => {
  assert.deepEqual(dragInsets(NO_CROP, 'n', 0, 0.2), [0.2, 0, 0, 0]);
  assert.deepEqual(dragInsets(NO_CROP, 'se', -0.25, -0.1), [0, 0.25, 0.1, 0]);
  // Dragged back out, it stops at the picture's edge.
  assert.deepEqual(dragInsets([0.1, 0, 0, 0], 'n', 0, -0.5), [0, 0, 0, 0]);
  // At least 5% of the picture stays.
  assert.deepEqual(dragInsets([0, 0, 0.5, 0], 'n', 0, 0.9), [0.45, 0, 0.5, 0]);
});

test('the kept area slides inside the picture', () => {
  assert.deepEqual(dragInsets([0.1, 0.2, 0.3, 0.4], 'move', 0.1, -0.05), [0.05, 0.1, 0.35, 0.5]);
  assert.deepEqual(dragInsets([0.1, 0.2, 0.3, 0.4], 'move', 1, 1), [0.4, 0, 0, 0.6]);
});

test('a crop turns with the picture', () => {
  assert.deepEqual(turnInsets([0.1, 0.2, 0.3, 0.4], 1), [0.4, 0.1, 0.2, 0.3]);
  assert.deepEqual(turnInsets(turnInsets([0.1, 0.2, 0.3, 0.4], 1), -1), [0.1, 0.2, 0.3, 0.4]);
  assert.equal(turnAngle(0, -1), 270);
  assert.equal(turnAngle(270, 1), 0);
});

test('a crop is read back only when it makes sense', () => {
  assert.deepEqual(parseInsets(formatInsets([0.1, 0.2, 0.3, 0.4])), [0.1, 0.2, 0.3, 0.4]);
  assert.equal(parseInsets('0.6,0,0.5,0'), null);
  assert.equal(parseInsets('a,b'), null);
  assert.equal(parseInsets(null), null);
});

import { dropGap } from '../../../resources/js/features/editor/native/sizing.ts';

test('a dropped image goes between the blocks nearest the pointer', () => {
  const spans = [{ top: 0, bottom: 20 }, { top: 30, bottom: 90 }, { top: 100, bottom: 120 }];
  assert.equal(dropGap(spans, -5), 0);
  assert.equal(dropGap(spans, 5), 0);
  assert.equal(dropGap(spans, 15), 1);
  // Between two blocks: before the next one.
  assert.equal(dropGap(spans, 25), 1);
  assert.equal(dropGap(spans, 70), 2);
  assert.equal(dropGap(spans, 200), 3);
  assert.equal(dropGap([], 10), 0);
});

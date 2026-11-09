import { test } from 'node:test';
import assert from 'node:assert/strict';
import { hiddenIn, middle, modes, settle } from '../../../resources/js/features/overlays/breadcrumbs/model.ts';

/**
 * A breadcrumb folds its middle, then gives way to a back link, when the trail does not fit.
 */

test('the middle is what lies between the kept ends', () => {
    assert.deepEqual(middle(7, 1, 2), [1, 2, 3, 4]);
    assert.deepEqual(middle(4, 1, 2), [1]);
    assert.deepEqual(middle(3, 1, 2), []);
    assert.deepEqual(middle(5, 0, 1), [0, 1, 2, 3]);
    assert.deepEqual(middle(5, 2, 0), [2, 3]);
});

test('each layout hides its items', () => {
    assert.deepEqual(hiddenIn('full', 6, 1, 2), []);
    assert.deepEqual(hiddenIn('full', 6, 1, 2, [1, 2]), [1, 2]);
    assert.deepEqual(hiddenIn('folded', 6, 1, 2), [1, 2, 3]);
    assert.deepEqual(hiddenIn('back', 6, 1, 2, [2]), [2]);
});

test('the layouts worth trying, widest first', () => {
    assert.deepEqual(modes(6, 1, 2, [], true), ['full', 'folded', 'back']);
    assert.deepEqual(modes(3, 1, 2, [], true), ['full', 'back']);
    assert.deepEqual(modes(6, 1, 2, [1, 2, 3], false), ['full']);
    assert.deepEqual(modes(6, 1, 2, [], false), ['full', 'folded']);
});

test('the widest layout that fits wins, else the narrowest', () => {
    const widths = { full: 600, folded: 380, back: 120 };
    const at = (room: number) => settle(['full', 'folded', 'back'], (mode) => widths[mode] <= room);
    assert.equal(at(800), 'full');
    assert.equal(at(500), 'folded');
    assert.equal(at(200), 'back');
    assert.equal(at(50), 'back');
});

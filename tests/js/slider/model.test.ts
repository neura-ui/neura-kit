import { test } from 'node:test';
import assert from 'node:assert/strict';
import { constrain, fromPosition, normalize, precisionOf, snap, toPosition, toSlot, fromSlot } from '../../../resources/js/features/forms/slider/model.ts';

/**
 * The slider works on a scale: a linear min / max / step, or a list of allowed values.
 * Positions run from 0 to 1 along the track; slots are what the native range inputs hold.
 */

const linear = { min: 0, max: 1000, step: 10, steps: null };
const listed = { min: 0.1, max: 5, step: 1, steps: [0.1, 0.25, 0.5, 1, 2, 5] };

test('precisionOf counts the decimals of a step', () => {
    assert.equal(precisionOf(1), 0);
    assert.equal(precisionOf(0.25), 2);
    assert.equal(precisionOf(0.1), 1);
    assert.equal(precisionOf(1e-7), 7);
});

test('snap keeps a value on the scale', () => {
    assert.equal(snap(253, linear), 250);
    assert.equal(snap(256, linear), 260);
    assert.equal(snap(-5, linear), 0);
    assert.equal(snap(5000, linear), 1000);
    assert.equal(snap(0.3, { min: 0, max: 1, step: 0.1, steps: null }), 0.3);
    assert.equal(snap(0.7, listed), 0.5);
    assert.equal(snap(1.6, listed), 2);
    assert.equal(snap(99, listed), 5);
});

test('positions and slots follow the scale, listed values evenly spaced', () => {
    assert.equal(toPosition(250, linear), 0.25);
    assert.equal(fromPosition(0.5, linear), 500);
    assert.equal(fromPosition(1.4, linear), 1000);
    assert.equal(toPosition(1, listed), 0.6);
    assert.equal(fromPosition(0.6, listed), 1);
    assert.equal(toSlot(250, linear), 250);
    assert.equal(toSlot(2, listed), 4);
    assert.equal(fromSlot(4, listed), 2);
    assert.equal(fromSlot(9, listed), 5);
});

test('normalize reads a single value or a range in any order', () => {
    assert.deepEqual(normalize(null, linear, false), [0]);
    assert.deepEqual(normalize('253', linear, false), [250]);
    assert.deepEqual(normalize([800, 200], linear, true), [200, 800]);
    assert.deepEqual(normalize({ 0: 100, 1: 300 }, linear, true), [100, 300]);
    assert.deepEqual(normalize(null, linear, true), [0, 1000]);
    assert.deepEqual(normalize(400, linear, true), [400, 1000]);
    assert.deepEqual(normalize([0.3, 3], listed, true), [0.25, 2]);
});

test('constrain keeps the two thumbs apart by the gap', () => {
    assert.deepEqual(constrain([300, 200], 0, 0, linear), [200, 200]);
    assert.deepEqual(constrain([300, 200], 1, 0, linear), [300, 300]);
    assert.deepEqual(constrain([190, 200], 0, 50, linear), [150, 200]);
    assert.deepEqual(constrain([150, 160], 1, 50, linear), [150, 200]);
    assert.deepEqual(constrain([980, 990], 1, 50, linear), [950, 1000]);
    // On a list, the gap counts listed values.
    assert.deepEqual(constrain([2, 2], 1, 1, listed), [2, 5]);
});

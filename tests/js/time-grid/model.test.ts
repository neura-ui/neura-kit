import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cellsBetween, coverage, paint, parse, serialize, toClock, toMinutes } from '../../../resources/js/features/forms/time-grid/model.ts';

/**
 * The time grid keeps minute-precise spans per column and reads and writes both value
 * shapes. Run with `node --test tests/js/` (Node strips the types itself).
 */

test('toMinutes reads the accepted time formats', () => {
    assert.equal(toMinutes('9:00'), 540);
    assert.equal(toMinutes('09:00:00'), 540);
    assert.equal(toMinutes('24:00'), 1440);
    assert.equal(toMinutes('nope'), null);
    assert.equal(toMinutes(null), null);
});

test('toClock writes the end of the day as 24:00', () => {
    assert.equal(toClock(540), '09:00');
    assert.equal(toClock(1440), '24:00');
});

test('paint merges two neighbouring spans of the same level', () => {
    assert.deepEqual(paint(paint([], 540, 600, 'on'), 600, 660, 'on'), [{ start: 540, end: 660, level: 'on' }]);
});

test('paint with another level splits the span', () => {
    assert.deepEqual(paint([{ start: 540, end: 720, level: 'on' }], 600, 660, 'boost'), [
        { start: 540, end: 600, level: 'on' },
        { start: 600, end: 660, level: 'boost' },
        { start: 660, end: 720, level: 'on' },
    ]);
});

test('paint with no level erases the middle', () => {
    assert.deepEqual(paint([{ start: 540, end: 720, level: 'on' }], 600, 660, null), [
        { start: 540, end: 600, level: 'on' },
        { start: 660, end: 720, level: 'on' },
    ]);
});

test('a span off the step stays intact when painting elsewhere', () => {
    assert.deepEqual(paint([{ start: 560, end: 600, level: 'on' }], 720, 780, 'on'), [
        { start: 560, end: 600, level: 'on' },
        { start: 720, end: 780, level: 'on' },
    ]);
});

test('coverage tells full, partial and mixed cells apart', () => {
    assert.deepEqual(coverage([{ start: 540, end: 600, level: 'on' }], 540, 600), { level: 'on', partial: false });
    assert.deepEqual(coverage([{ start: 560, end: 600, level: 'on' }], 540, 600), { level: null, partial: true });
    assert.deepEqual(
        coverage([{ start: 540, end: 570, level: 'on' }, { start: 570, end: 600, level: 'boost' }], 540, 600),
        { level: null, partial: true },
    );
    assert.deepEqual(coverage([], 540, 600), { level: null, partial: false });
});

test('parse sorts and merges overlapping spans', () => {
    const spans = parse([
        { day: 'mon', start: '14:00', end: '18:00' },
        { day: 'mon', start: '09:00', end: '12:00' },
        { day: 'mon', start: '11:00', end: '13:00' },
    ], 'on');

    assert.deepEqual(spans.get('mon'), [
        { start: 540, end: 780, level: 'on' },
        { start: 840, end: 1080, level: 'on' },
    ]);
});

test('parse reads an end at 00:00 as 24:00 and drops an end before its start', () => {
    const spans = parse([
        { day: 'sun', start: '22:00', end: '00:00' },
        { day: 'sun', start: '10:00', end: '09:00' },
    ], 'on');

    assert.deepEqual(spans.get('sun'), [{ start: 1320, end: 1440, level: 'on' }]);
});

test('parse accepts the per-day shape, the column key and a level', () => {
    assert.deepEqual(parse({ mon: [{ start: '09:00', end: '12:00' }], tue: [] }, 'on').get('mon'), [
        { start: 540, end: 720, level: 'on' },
    ]);
    assert.deepEqual(parse([{ column: 'a', start: '10:00', end: '11:00', level: 'boost' }], 'on').get('a'), [
        { start: 600, end: 660, level: 'boost' },
    ]);
});

test('serialize ranges follows the shown order, then keeps hidden columns', () => {
    const spans = parse([
        { day: 'mon', start: '09:00', end: '10:00' },
        { day: 'sat', start: '06:00', end: '07:00' },
        { day: 'tue', start: '23:00', end: '24:00' },
    ], 'on');

    assert.deepEqual(serialize(spans, { format: 'ranges', keyName: 'day', order: ['tue', 'mon'], withLevel: false }), [
        { day: 'tue', start: '23:00', end: '24:00' },
        { day: 'mon', start: '09:00', end: '10:00' },
        { day: 'sat', start: '06:00', end: '07:00' },
    ]);
});

test('serialize adds the level only when asked', () => {
    const spans = parse([{ column: 'a', start: '10:00', end: '11:00', level: 'boost' }], 'on');

    assert.deepEqual(serialize(spans, { format: 'ranges', keyName: 'column', order: ['a'], withLevel: true }), [
        { column: 'a', start: '10:00', end: '11:00', level: 'boost' },
    ]);
});

test('serialize days lists every shown column, even empty', () => {
    const spans = parse([
        { day: 'mon', start: '09:00', end: '12:00' },
        { day: 'sat', start: '06:00', end: '07:00' },
    ], 'on');

    assert.deepEqual(serialize(spans, { format: 'days', keyName: 'day', order: ['mon', 'tue'], withLevel: false }), {
        mon: [{ start: '09:00', end: '12:00' }],
        tue: [],
        sat: [{ start: '06:00', end: '07:00' }],
    });
});

test('parse then serialize gives the same value back', () => {
    const value = [
        { day: 'mon', start: '09:00', end: '12:00' },
        { day: 'mon', start: '14:00', end: '18:00' },
    ];

    assert.deepEqual(serialize(parse(value, 'on'), { format: 'ranges', keyName: 'day', order: ['mon'], withLevel: false }), value);
});

test('parse reads a PHP list with gaps in its keys, sent as an object', () => {
    const spans = parse({
        0: { day: 'mon', start: '09:00', end: '10:00' },
        2: { day: 'tue', start: '11:00', end: '12:00' },
    }, 'on');

    assert.deepEqual(spans.get('mon'), [{ start: 540, end: 600, level: 'on' }]);
    assert.deepEqual(spans.get('tue'), [{ start: 660, end: 720, level: 'on' }]);
});

test('parse reads a per-day list with gaps in its keys', () => {
    const spans = parse({ mon: { 0: { start: '09:00', end: '10:00' }, 3: { start: '14:00', end: '15:00' } } }, 'on');

    assert.deepEqual(spans.get('mon'), [
        { start: 540, end: 600, level: 'on' },
        { start: 840, end: 900, level: 'on' },
    ]);
});

test('parse reads 00:00 – 00:00 as the whole day', () => {
    assert.deepEqual(parse([{ day: 'mon', start: '00:00', end: '00:00' }], 'on').get('mon'), [{ start: 0, end: 1440, level: 'on' }]);
});

test('cellsBetween walks every cell from one to the next, both included', () => {
    assert.deepEqual(cellsBetween([0, 0], [0, 3]), [[0, 0], [0, 1], [0, 2], [0, 3]]);
    assert.deepEqual(cellsBetween([2, 1], [0, 1]), [[2, 1], [1, 1], [0, 1]]);
    assert.deepEqual(cellsBetween([0, 0], [2, 4]), [[0, 0], [1, 1], [1, 2], [2, 3], [2, 4]]);
    assert.deepEqual(cellsBetween([1, 1], [1, 1]), [[1, 1]]);
});

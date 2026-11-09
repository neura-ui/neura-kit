import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    breaksBefore, deleteColumn, deleteRow, insertColumn, insertRow, layout, merge, rectOf, split,
    type Model,
} from '../../../resources/js/features/editor/native/tablemodel.ts';

/**
 * Tables with merged cells, as a grid of slots. Each picture below is the model laid out:
 * one letter per slot, the cell id that covers it.
 */

const make = (rows: string[][], spans: Record<string, [number, number]> = {}): Model =>
    rows.map((cells, r) => ({
        id: `r${r}`,
        cells: cells.map((id) => ({ id, rowspan: spans[id]?.[0] ?? 1, colspan: spans[id]?.[1] ?? 1 })),
    }));

const picture = (model: Model) => layout(model).slots.map((row) => row.map((slot) => slot?.id ?? '.').join('')).join('/');

const grid3 = () => make([['a', 'b', 'c'], ['d', 'e', 'f'], ['g', 'h', 'i']]);

test('layout places cells around the ones spanning into a row', () => {
    const model = make([['a', 'b'], ['c']], { a: [2, 1] });
    assert.equal(picture(model), 'ab/ac');
    assert.deepEqual(layout(model).origins.get('c'), { row: 1, col: 1, rowspan: 1, colspan: 1 });
    assert.equal(layout(model).width, 2);
});

test('a selection grows to take in whole merged cells', () => {
    const model = make([['a', 'b', 'c'], ['d', 'f'], ['g', 'h', 'i']], { b: [2, 1] });
    assert.equal(picture(model), 'abc/dbf/ghi');
    assert.deepEqual(rectOf(model, 'a', 'b'), { top: 0, left: 0, bottom: 1, right: 1 });
    assert.deepEqual(rectOf(model, 'h', 'b'), { top: 0, left: 1, bottom: 2, right: 1 });
});

test('merging keeps the top-left cell and says which were absorbed', () => {
    const { model, kept, absorbed } = merge(grid3(), 'e', 'a');
    assert.equal(kept, 'a');
    assert.deepEqual(absorbed, ['b', 'd', 'e']);
    assert.equal(picture(model), 'aac/aaf/ghi');
    assert.deepEqual(model[0].cells.map((c) => [c.id, c.rowspan, c.colspan]), [['a', 2, 2], ['c', 1, 1]]);
});

test('splitting gives back one cell per slot', () => {
    const merged = merge(grid3(), 'a', 'e').model;
    const model = split(merged, 'a');
    assert.equal(picture(model).replace(/[n]\d*/g, '+'), 'a+c/++f/ghi');
    assert.equal(model[1].cells.length, 3);
    assert.ok(model[1].cells[0].isNew && model[1].cells[1].isNew);
});

test('columns go in and out around merged cells', () => {
    const wide = make([['a', 'c'], ['d', 'e', 'f']], { a: [1, 2] });
    assert.equal(picture(wide), 'aac/def');
    // Inside a merged cell: it grows instead of being cut.
    assert.equal(picture(insertColumn(wide, 1)).replace(/n\d*/g, '+'), 'aaac/d+ef');
    // At the left edge: a new cell in every row.
    assert.equal(picture(insertColumn(wide, 0)).replace(/n\d*/g, '+'), '+aac/+def');
    // Deleting a column a merged cell spans narrows it.
    assert.equal(picture(deleteColumn(wide, 0)), 'ac/ef');
    assert.equal(picture(deleteColumn(wide, 2)), 'aa/de');
});

test('rows go in and out around merged cells', () => {
    const tall = make([['a', 'b'], ['c'], ['d', 'e']], { a: [2, 1] });
    assert.equal(picture(tall), 'ab/ac/de');
    // Between the rows a merged cell spans: it grows.
    assert.equal(picture(insertRow(tall, 1)).replace(/n\d*/g, '+'), 'ab/a+/ac/de');
    assert.equal(picture(insertRow(tall, 0)).replace(/n\d*/g, '+'), '++/ab/ac/de');
    // Deleting the row a merged cell starts in moves it down, shorter.
    assert.equal(picture(deleteRow(tall, 0)), 'ac/de');
    // Deleting a row it reaches into shortens it.
    assert.equal(picture(deleteRow(tall, 1)), 'ab/de');
});

test('a table is cut across pages only between rows no merged cell spans', () => {
  // a spans rows 0-1, so the cut can come before row 2 or 3, not before row 1.
  const model = make([['a', 'b'], ['c'], ['d', 'e'], ['f', 'g']], { a: [2, 1] });
  assert.deepEqual(breaksBefore(model), [2, 3]);
  assert.deepEqual(breaksBefore(make([['a'], ['b'], ['c']])), [1, 2]);
  assert.deepEqual(breaksBefore(make([['a']])), []);
});

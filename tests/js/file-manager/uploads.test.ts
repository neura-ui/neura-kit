import { test } from 'node:test';
import assert from 'node:assert/strict';
import { applyUploadEvent, queueFiles, uploadSummary } from '../../../resources/js/features/forms/file-manager/uploads.ts';

/** The upload panel's list: what the app says about each file moves it along. */

let n = 0;
const makeId = () => `fm-${++n}`;

test('files dropped wait, each with its own id', () => {
  n = 0;
  const list = queueFiles([{ name: 'a.jpg', size: 10 }, { name: 'b.pdf', size: 20 }, { name: 'c.mp4', size: 30 }], makeId);
  assert.deepEqual(list.map((upload) => [upload.id, upload.status, upload.progress]), [['fm-1', 'waiting', 0], ['fm-2', 'waiting', 0], ['fm-3', 'waiting', 0]]);
});

test('progress, done, failed and retry move a file along; unknown ids change nothing', () => {
  n = 0;
  let list = queueFiles([{ name: 'a.jpg', size: 10 }, { name: 'b.pdf', size: 20 }], makeId);
  list = applyUploadEvent(list, { type: 'progress', id: 'fm-1', progress: 64 });
  assert.deepEqual([list[0].status, list[0].progress], ['sending', 64]);
  list = applyUploadEvent(list, { type: 'progress', id: 'fm-1', progress: 140 });
  assert.equal(list[0].progress, 100);
  list = applyUploadEvent(list, { type: 'uploaded', id: 'fm-1' });
  assert.deepEqual([list[0].status, list[0].progress], ['done', 100]);
  list = applyUploadEvent(list, { type: 'failed', id: 'fm-2', message: 'Too big' });
  assert.deepEqual([list[1].status, list[1].message], ['failed', 'Too big']);
  list = applyUploadEvent(list, { type: 'retry', id: 'fm-2' });
  assert.deepEqual([list[1].status, list[1].progress, list[1].message], ['waiting', 0, undefined]);
  list = applyUploadEvent(list, { type: 'cancel', id: 'fm-2' });
  assert.equal(list[1].status, 'cancelled');
  assert.equal(applyUploadEvent(list, { type: 'progress', id: 'other', progress: 50 }), list);
});

test('a finished file does not go back to sending', () => {
  n = 0;
  let list = queueFiles([{ name: 'a.jpg', size: 10 }], makeId);
  list = applyUploadEvent(list, { type: 'uploaded', id: 'fm-1' });
  list = applyUploadEvent(list, { type: 'progress', id: 'fm-1', progress: 20 });
  assert.equal(list[0].status, 'done');
});

test('the summary counts what is still going, done and failed', () => {
  n = 0;
  let list = queueFiles([{ name: 'a', size: 1 }, { name: 'b', size: 1 }, { name: 'c', size: 1 }, { name: 'd', size: 1 }], makeId);
  list = applyUploadEvent(list, { type: 'uploaded', id: 'fm-1' });
  list = applyUploadEvent(list, { type: 'failed', id: 'fm-2' });
  list = applyUploadEvent(list, { type: 'progress', id: 'fm-3', progress: 10 });
  assert.deepEqual(uploadSummary(list), { active: 2, done: 1, failed: 1 });
});

test('only a failed or cancelled file can be sent again', () => {
  n = 0;
  let list = queueFiles([{ name: 'a', size: 1 }], makeId);
  list = applyUploadEvent(list, { type: 'uploaded', id: 'fm-1' });
  assert.equal(applyUploadEvent(list, { type: 'retry', id: 'fm-1' }), list);
});

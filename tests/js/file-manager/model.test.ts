import { test } from 'node:test';
import assert from 'node:assert/strict';
import { decorate, extensionOf, formatDuration, relativeDate } from '../../../resources/js/features/forms/file-manager/model.ts';

/**
 * The file manager's entries, worked out from the nodes the app gives: kind, group,
 * labels, the picture to preview, and dates said the way people say them.
 * Run with `node --test tests/js/`.
 */

const now = Date.UTC(2026, 10, 9, 15, 0);
const phrases = { t: (_key: string, fallback: string) => fallback };

test('dates read as people say them, in the locale', () => {
  assert.equal(relativeDate(now - 20_000, now, 'fr'), 'maintenant');
  assert.equal(relativeDate(now - 5 * 60_000, now, 'fr'), 'il y a 5 minutes');
  assert.equal(relativeDate(now - 2 * 3_600_000, now, 'fr'), 'il y a 2 heures');
  assert.equal(relativeDate(Date.UTC(2026, 10, 8, 10, 0), now, 'fr'), 'hier');
  assert.equal(relativeDate(Date.UTC(2026, 10, 3, 9, 0), now, 'fr'), '3 nov.');
  assert.equal(relativeDate(Date.UTC(2025, 10, 3, 9, 0), now, 'fr'), '3 nov. 2025');
  assert.equal(relativeDate(Date.UTC(2026, 10, 8, 10, 0), now, 'en'), 'yesterday');
  assert.equal(relativeDate(Date.UTC(2026, 10, 3, 9, 0), now, 'en'), 'Nov 3');
  assert.equal(relativeDate(0, now, 'en'), '—');
});

test('the extension is the last part after a dot, never a leading dot', () => {
  assert.equal(extensionOf('archive.tar.gz'), 'gz');
  assert.equal(extensionOf('.env'), '');
  assert.equal(extensionOf('sans-extension'), '');
  assert.equal(extensionOf('Photo de l’équipe.JPEG'), 'jpeg');
});

test('a video with a thumbnail previews as a video, with its length', () => {
  const entry = decorate({ id: 'v', name: 'teaser.mp4', thumbnail: '/t.jpg', duration: 42, modified: now }, 'fr', now, phrases);
  assert.deepEqual(entry.preview, { kind: 'video', src: '/t.jpg', duration: '0:42' });
  assert.equal(entry.group, 'video');
});

test('an image without a thumbnail previews from its own address', () => {
  const entry = decorate({ id: 'i', name: 'hero.png', url: '/hero.png' }, 'fr', now, phrases);
  assert.deepEqual(entry.preview, { kind: 'image', src: '/hero.png', duration: null });
});

test('a PDF is a document shown as a tile with its label', () => {
  const entry = decorate({ id: 'p', name: 'brief.pdf', size: 1_200_000 }, 'fr', now, phrases);
  assert.deepEqual(entry.preview, { kind: 'tile', src: null, duration: null });
  assert.equal(entry.badge, 'PDF');
  assert.equal(entry.group, 'document');
  assert.equal(entry.typeLabel, 'PDF');
});

test('a folder is a folder, whatever its name', () => {
  const entry = decorate({ id: 'f', name: 'photos.zip', type: 'folder', children: [] }, 'fr', now, phrases);
  assert.equal(entry.group, 'folder');
  assert.equal(entry.typeLabel, 'Folder');
  assert.equal(entry.isFolder, true);
});

test('a date keeps its full form for the tooltip', () => {
  const entry = decorate({ id: 'n', name: 'notes.md', modified: Date.UTC(2026, 10, 3, 9, 0) }, 'en', now, phrases);
  assert.equal(entry.modifiedLabel, 'Nov 3');
  assert.match(entry.modifiedTitle, /2026/);
});

test('durations read as minutes and seconds, hours when needed', () => {
  assert.equal(formatDuration(3725), '1:02:05');
  assert.equal(formatDuration(42), '0:42');
  assert.equal(formatDuration(undefined), null);
});

test('addresses that would run script are never used for a preview', () => {
  for (const url of ['javascript:alert(1)', ' JaVaScRiPt:alert(1)', 'java\tscript:alert(1)', 'vbscript:msgbox(1)', 'data:text/html,<script>alert(1)</script>']) {
    const entry = decorate({ id: 'x', name: 'brief.pdf', url, thumbnail: url }, 'en', now, phrases);
    assert.equal(entry.url, undefined, url);
    assert.equal(entry.thumbnail, undefined, url);
    assert.deepEqual(entry.preview, { kind: 'tile', src: null, duration: null });
  }
  const fine = decorate({ id: 'y', name: 'a.png', url: '/files/a.png', thumbnail: 'https://cdn.example.com/a.png' }, 'en', now, phrases);
  assert.equal(fine.url, '/files/a.png');
  assert.equal(fine.preview.src, 'https://cdn.example.com/a.png');
  const inline = decorate({ id: 'z', name: 'b.png', url: 'data:image/png;base64,AAAA' }, 'en', now, phrases);
  assert.equal(inline.preview.src, 'data:image/png;base64,AAAA');
});

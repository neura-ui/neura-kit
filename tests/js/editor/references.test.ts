import { test } from 'node:test';
import assert from 'node:assert/strict';
import { anchorsFor, slugify } from '../../../resources/js/features/editor/native/anchors.ts';
import { filterPeople, initials, slashQuery } from '../../../resources/js/features/editor/native/commands.ts';

test('headings get readable anchors', () => {
  assert.equal(slugify('Résumé du 3ᵉ trimestre !'), 'resume-du-3e-trimestre');
  assert.equal(slugify('   '), 'section');
  assert.equal(slugify('x'.repeat(80)).length, 60);
});

test('anchors are kept once made, and never shared', () => {
  assert.deepEqual(anchorsFor([{ text: 'Intro' }, { text: 'Intro' }, { text: 'Plan' }]), ['h-intro', 'h-intro-2', 'h-plan']);
  // A reworded heading keeps its anchor, so links to it still work.
  assert.deepEqual(anchorsFor([{ text: 'Introduction', current: 'h-intro' }]), ['h-intro']);
  // A copy (Enter at the end of a heading clones its id) gets a new one; the first keeps it.
  assert.deepEqual(anchorsFor([{ text: 'Budget', current: 'h-budget' }, { text: 'Suite', current: 'h-budget' }]), ['h-budget', 'h-suite']);
  // A new heading never takes an anchor a later one already owns.
  assert.deepEqual(anchorsFor([{ text: 'Plan' }, { text: 'Autre', current: 'h-plan' }]), ['h-plan-2', 'h-plan']);
  // Ids that are not ours are replaced.
  assert.deepEqual(anchorsFor([{ text: 'Plan', current: 'main' }]), ['h-plan']);
});

test('"@" keeps the people a query matches, best first', () => {
  const people = [
    { id: 1, name: 'Akram Zerarka', email: 'akram@neura.dev' },
    { id: 2, name: 'Zoé Martin', email: 'zoe@agence.fr' },
    { id: 3, name: 'Marc Akkari', email: 'marc@agence.fr' },
    { id: 4, name: 'Léa Dubois', email: 'lea.dubois@agence.fr' },
  ];
  assert.deepEqual(filterPeople(people, 'ak').map((p) => p.id), [1, 3]);
  assert.deepEqual(filterPeople(people, 'zoe').map((p) => p.id), [2]);
  assert.deepEqual(filterPeople(people, 'agence').map((p) => p.id), [2, 3, 4]);
  assert.deepEqual(filterPeople(people, 'martin').map((p) => p.id), [2]);
  assert.deepEqual(filterPeople(people, '').map((p) => p.id), [1, 2, 3, 4]);
  assert.deepEqual(filterPeople(people, '', 2).map((p) => p.id), [1, 2]);
  assert.equal(initials('Akram Zerarka'), 'AZ');
  assert.equal(initials('Léa'), 'L');
  assert.equal(initials('  '), '?');
});

test('the "@" query reads like the "/" one', () => {
  assert.equal(slashQuery('Voir @ak', 5, 8, '@'), 'ak');
  assert.equal(slashQuery('Voir @Akram Z', 5, 13, '@'), 'Akram Z');
  assert.equal(slashQuery('Voir /ak', 5, 8, '@'), null);
  assert.equal(slashQuery('Voir @a  b', 5, 10, '@'), null);
});

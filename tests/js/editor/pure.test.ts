import { test } from 'node:test';
import assert from 'node:assert/strict';
import { compile, findInText, replacementFor } from '../../../resources/js/features/editor/native/search.ts';
import { changeCase } from '../../../resources/js/features/editor/native/textcase.ts';
import { filterCommands, slashQuery } from '../../../resources/js/features/editor/native/commands.ts';

/**
 * The editor's find and replace, text case and "/" menu, as plain functions.
 */

const spans = (text: string, query: string, options = {}) => {
    const compiled = compile(query, options);
    return compiled.pattern ? findInText(text, compiled.pattern) : compiled.error;
};

test('find matches ignore case unless asked, and can take whole words only', () => {
    const text = 'Meta et métaux : META, Meta-test, metadata.';
    assert.deepEqual(spans(text, 'meta'), [[0, 4], [17, 21], [23, 27], [34, 38]]);
    assert.deepEqual(spans(text, 'Meta', { matchCase: true }), [[0, 4], [23, 27]]);
    assert.deepEqual(spans(text, 'meta', { wholeWord: true }), [[0, 4], [17, 21], [23, 27]]);
    assert.deepEqual(spans('l’équipe et équipe', 'équipe', { wholeWord: true }), [[2, 8], [12, 18]]);
    assert.deepEqual(spans('a.b axb', 'a.b'), [[0, 3]]);
});

test('regular expressions, and their errors', () => {
    assert.deepEqual(spans('15 €, 18 € et 120 €', '\\d+ €', { regex: true }), [[0, 4], [6, 10], [14, 19]]);
    assert.equal(typeof spans('x', '(', { regex: true }), 'string');
    assert.deepEqual(spans('aaa', 'a*', { regex: true }), [[0, 3]]);
    assert.equal(compile('').pattern, null);
    assert.equal(compile('').error, null);
});

test('a replacement can reuse groups in regex mode only', () => {
    assert.equal(replacementFor('18 €', '(\\d+) €', '€$1', { regex: true }), '€18');
    assert.equal(replacementFor('Meta', 'meta', '$1 Facebook', {}), '$1 Facebook');
});

test('text case', () => {
    assert.equal(changeCase('Le Budget META', 'lower'), 'le budget meta');
    assert.equal(changeCase('le budget méta', 'upper'), 'LE BUDGET MÉTA');
    assert.equal(changeCase('le budget de l’équipe', 'title'), 'Le Budget De L’équipe');
    assert.equal(changeCase('LE BUDGET. ET META ! fin', 'sentence'), 'Le budget. Et meta ! Fin');
    // Mid-word, the text before decides whether a letter starts a word or a sentence.
    assert.equal(changeCase('dget meta', 'title', 'bu'), 'dget Meta');
    assert.equal(changeCase('suite', 'sentence', 'Fin. '), 'Suite');
    assert.equal(changeCase('suite', 'sentence', 'avant '), 'suite');
});

test('the "/" menu filters by label and keywords, accents aside', () => {
    const items = [
        { id: 'heading1', label: 'Titre 1', keywords: ['h1', 'heading'] },
        { id: 'table', label: 'Tableau', keywords: ['table', 'grid'] },
        { id: 'checklist', label: 'Liste de contrôle', keywords: ['todo', 'checklist', 'tache'] },
        { id: 'bullets', label: 'Liste à puces', keywords: ['ul', 'bullet'] },
    ];
    const ids = (q: string) => filterCommands(items, q).map((i) => i.id);
    assert.deepEqual(ids(''), ['heading1', 'table', 'checklist', 'bullets']);
    assert.deepEqual(ids('tab'), ['table']);
    assert.deepEqual(ids('controle'), ['checklist']);
    assert.deepEqual(ids('liste'), ['checklist', 'bullets']);
    assert.deepEqual(ids('h1'), ['heading1']);
    assert.deepEqual(ids('todo'), ['checklist']);
    assert.deepEqual(ids('zzz'), []);
});

test('the "/" query is read from the text of the block', () => {
    assert.equal(slashQuery('/tab', 0, 4), 'tab');
    assert.equal(slashQuery('Avant /ti', 6, 9), 'ti');
    assert.equal(slashQuery('/', 0, 1), '');
    // The caret went before the slash, the slash was deleted, or the query got long: closed.
    assert.equal(slashQuery('/tab', 0, 0), null);
    assert.equal(slashQuery('tab', 0, 3), null);
    assert.equal(slashQuery('/une phrase bien trop longue pour un menu', 0, 42), null);
    assert.equal(slashQuery('/a  b', 0, 5), null);
});

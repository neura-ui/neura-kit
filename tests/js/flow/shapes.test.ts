import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    agentInputs,
    canConnect,
    cssColor,
    isIconName,
    labelPlacement,
    nodeLineage,
    particleCount,
    portsFor,
    resolveShape,
    resolveStatus,
    resolveVariant,
    rowLineage,
    rowPortTop,
    tableHeight,
    tableRows,
    verticalPath,
} from '../../../resources/js/features/flow/shapes.ts';

/**
 * The flow's node shapes, card styles and n8n-like rules, kept pure so Node tests them
 * directly: `node --test 'tests/js/**\/*.test.ts'`.
 */

test('resolveShape: data.shape wins, then the node type, then the fallback', () => {
    assert.equal(resolveShape({ type: 'step', data: { shape: 'card' } }, 'node'), 'card');
    assert.equal(resolveShape({ type: 'trigger' }, 'node'), 'trigger');
    assert.equal(resolveShape({ type: 'step', data: { trigger: true } }, 'node'), 'trigger');
    assert.equal(resolveShape({ type: 'agent' }, 'node'), 'agent');
    assert.equal(resolveShape({ type: 'sub' }, 'node'), 'sub');
    assert.equal(resolveShape({ type: 'note' }, 'node'), 'note');
    assert.equal(resolveShape({ type: 'sticky' }, 'node'), 'note');
    assert.equal(resolveShape({ type: 'step' }, 'card'), 'card');
    assert.equal(resolveShape({ type: 'step', data: { shape: 'blob' } }, 'node'), 'node');
});

test('resolveVariant: data.variant wins when known, else the fallback', () => {
    assert.equal(resolveVariant({ data: { variant: 'soft' } }, 'elevated'), 'soft');
    assert.equal(resolveVariant({ data: { variant: 'neon' } }, 'outline'), 'outline');
    assert.equal(resolveVariant({}, 'solid'), 'solid');
});

test('resolveStatus maps the usual words onto four states', () => {
    assert.equal(resolveStatus('ok'), 'success');
    assert.equal(resolveStatus('success'), 'success');
    assert.equal(resolveStatus('fail'), 'error');
    assert.equal(resolveStatus('error'), 'error');
    assert.equal(resolveStatus('running'), 'running');
    assert.equal(resolveStatus('pending'), 'waiting');
    assert.equal(resolveStatus('draft'), null);
    assert.equal(resolveStatus(undefined), null);
});

test('portsFor: which connectors each shape has', () => {
    assert.deepEqual(portsFor('node'), { mainIn: true, mainOut: true, aiIn: false, aiOut: false });
    assert.deepEqual(portsFor('trigger'), { mainIn: false, mainOut: true, aiIn: false, aiOut: false });
    assert.deepEqual(portsFor('agent'), { mainIn: true, mainOut: true, aiIn: true, aiOut: false });
    assert.deepEqual(portsFor('sub'), { mainIn: false, mainOut: false, aiIn: false, aiOut: true });
    assert.deepEqual(portsFor('note'), { mainIn: false, mainOut: false, aiIn: false, aiOut: false });
    assert.deepEqual(portsFor('card'), { mainIn: true, mainOut: true, aiIn: false, aiOut: false });
});

test('agentInputs: model, memory and tool by default, or the node’s own list', () => {
    assert.deepEqual(agentInputs({ data: {} }), [
        { id: 'model', label: 'Chat Model', required: true },
        { id: 'memory', label: 'Memory', required: false },
        { id: 'tool', label: 'Tool', required: false },
    ]);
    assert.deepEqual(agentInputs({ data: { inputs: ['model', { id: 'parser', label: 'Output Parser' }] } }), [
        { id: 'model', label: 'model', required: false },
        { id: 'parser', label: 'Output Parser', required: false },
    ]);
});

test('canConnect only joins connectors of the same kind', () => {
    assert.equal(canConnect('main', 'main'), true);
    assert.equal(canConnect('ai', 'ai'), true);
    assert.equal(canConnect('main', 'ai'), false);
    assert.equal(canConnect('ai', 'main'), false);
});

test('verticalPath curves from a sub-node up into the agent', () => {
    assert.equal(verticalPath(100, 300, 160, 200), 'M 100 300 C 100 250, 160 250, 160 200');
    assert.equal(verticalPath(0, 100, 0, 90), 'M 0 100 C 0 70, 0 120, 0 90');
});

test('isIconName tells an icon name from a glyph', () => {
    assert.equal(isIconName('bolt'), true);
    assert.equal(isIconName('arrow-path'), true);
    assert.equal(isIconName('ps:robot'), true);
    assert.equal(isIconName('▶'), false);
    assert.equal(isIconName('A'), false);
    assert.equal(isIconName('Send email'), false);
    assert.equal(isIconName(undefined), false);
});

test('cssColor turns a design-system name into its variable and keeps anything else', () => {
    assert.equal(cssColor('warning'), 'var(--color-warning-500, var(--color-warning))');
    assert.equal(cssColor('violet-600'), 'var(--color-violet-600)');
    assert.equal(cssColor('#ff6d5a'), '#ff6d5a');
    assert.equal(cssColor(undefined), null);
});

test('resolveVariant accepts the styles the config adds', () => {
    assert.equal(resolveVariant({ data: { variant: 'glass' } }, 'elevated', ['elevated', 'glass']), 'glass');
    assert.equal(resolveVariant({ data: { variant: 'soft' } }, 'elevated', ['elevated', 'glass']), 'elevated');
});

test('labelPlacement: signal keeps names inside its capsules, classic puts them under tiles', () => {
    assert.equal(labelPlacement('node', 'classic'), 'below');
    assert.equal(labelPlacement('trigger', 'classic'), 'below');
    assert.equal(labelPlacement('sub', 'classic'), 'below');
    assert.equal(labelPlacement('agent', 'classic'), 'inside');
    assert.equal(labelPlacement('card', 'classic'), 'inside');
    assert.equal(labelPlacement('note', 'classic'), 'none');
    assert.equal(labelPlacement('node', 'signal'), 'inside');
    assert.equal(labelPlacement('trigger', 'signal'), 'inside');
    assert.equal(labelPlacement('sub', 'signal'), 'below');
    assert.equal(labelPlacement('note', 'signal'), 'none');
});

test('particleCount: data dots only in signal, on connections that are running', () => {
    assert.equal(particleCount('signal', { animated: true }), 2);
    assert.equal(particleCount('signal', { status: 'running' }), 2);
    assert.equal(particleCount('signal', { status: 'success' }), 0);
    assert.equal(particleCount('signal', {}), 0);
    assert.equal(particleCount('classic', { animated: true }), 0);
});

test('a table node: its type, connectors and name inside', () => {
    assert.equal(resolveShape({ type: 'table' }, 'node'), 'table');
    assert.deepEqual(portsFor('table'), { mainIn: true, mainOut: true, aiIn: false, aiOut: false });
    assert.equal(labelPlacement('table', 'classic'), 'inside');
    assert.equal(labelPlacement('table', 'signal'), 'inside');
});

test('tableRows reads names or row objects', () => {
    assert.deepEqual(tableRows({ data: { rows: ['email', { id: 'id', type: 'uuid', key: 'pk' }, { id: 'customer_id', label: 'Customer', key: 'fk' }] } }), [
        { id: 'email', label: 'email', type: null, key: null },
        { id: 'id', label: 'id', type: 'uuid', key: 'pk' },
        { id: 'customer_id', label: 'Customer', type: null, key: 'fk' },
    ]);
    assert.deepEqual(tableRows({ data: {} }), []);
});

test('a table grows with its rows; each row has its connectors at its middle', () => {
    assert.equal(tableHeight(0), 50);
    assert.equal(tableHeight(4), 162);
    assert.equal(rowPortTop(0), 59);
    assert.equal(rowPortTop(2), 115);
});

test('rowLineage follows a row upstream and downstream, row to row', () => {
    const edges = [
        { id: 'a', source: 'orders', sourceHandle: 'total', target: 'stage', targetHandle: 'total' },
        { id: 'b', source: 'stage', sourceHandle: 'total', target: 'mart', targetHandle: 'revenue' },
        { id: 'c', source: 'customers', sourceHandle: 'country', target: 'stage', targetHandle: 'country' },
        { id: 'd', source: 'stage', sourceHandle: 'out', target: 'report', targetHandle: 'in' },
    ];

    const fromMart = rowLineage(edges, 'mart', 'revenue');
    assert.deepEqual([...fromMart.rows].sort(), ['mart:revenue', 'orders:total', 'stage:total']);
    assert.deepEqual([...fromMart.edges].sort(), ['a', 'b']);

    const fromCountry = rowLineage(edges, 'customers', 'country');
    assert.deepEqual([...fromCountry.rows].sort(), ['customers:country', 'stage:country']);
    assert.deepEqual([...fromCountry.edges], ['c']);
});

test('with cards as the default, a trigger stays a card', () => {
    assert.equal(resolveShape({ type: 'trigger' }, 'card'), 'card');
    assert.equal(resolveShape({ type: 'step', data: { trigger: true } }, 'card'), 'card');
    assert.equal(resolveShape({ type: 'trigger', data: { shape: 'trigger' } }, 'card'), 'trigger');
    assert.equal(resolveShape({ type: 'agent' }, 'card'), 'agent');
});

test('nodeLineage: everything upstream and downstream of a node, not its siblings', () => {
    const edges = [
        { id: 'ab', source: 'a', target: 'b' },
        { id: 'db', source: 'd', target: 'b' },
        { id: 'bc', source: 'b', target: 'c' },
        { id: 'be', source: 'b', target: 'e' },
    ];

    const fromB = nodeLineage(edges, 'b');
    assert.deepEqual([...fromB.nodes].sort(), ['a', 'b', 'c', 'd', 'e']);
    assert.deepEqual([...fromB.edges].sort(), ['ab', 'bc', 'be', 'db']);

    const fromC = nodeLineage(edges, 'c');
    assert.deepEqual([...fromC.nodes].sort(), ['a', 'b', 'c', 'd']);
    assert.deepEqual([...fromC.edges].sort(), ['ab', 'bc', 'db']);
});

test('nodeLineage survives a loop', () => {
    const lineage = nodeLineage([{ id: 'xy', source: 'x', target: 'y' }, { id: 'yx', source: 'y', target: 'x' }], 'x');
    assert.deepEqual([...lineage.nodes].sort(), ['x', 'y']);
});

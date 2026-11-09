/**
 * Flow node shapes, card styles and connection rules, n8n-style.
 *
 *   node     square tile, icon inside, name below: the everyday step
 *   trigger  the same tile rounded on its left: where a flow starts, no input
 *   agent    a wide tile with sub-connectors underneath (chat model, memory, tool)
 *   sub      a round tile that plugs into an agent from below
 *   note     a sticky note on the canvas, never connected
 *   card     the original horizontal card: icon, name and description side by side
 *   table    a header and a list of rows (columns, fields), each row with its own connectors,
 *            so a pipeline links row to row and hovering one traces its lineage
 *
 * Main connectors run left to right between steps; `ai` connectors run from a sub-node up
 * into an agent. A connection only joins connectors of the same kind.
 *
 * No imports: the tests load this file with Node directly.
 */

export const SHAPES = ['node', 'trigger', 'agent', 'sub', 'note', 'card', 'table'] as const;
export type FlowShape = (typeof SHAPES)[number];

export const VARIANTS = ['elevated', 'outline', 'soft', 'solid'] as const;
export type FlowVariant = (typeof VARIANTS)[number];

export type FlowStatus = 'success' | 'error' | 'running' | 'waiting';
export type FlowChannel = 'main' | 'ai';

export type AgentInput = { id: string; label: string; required: boolean };

type NodeLike = { type?: string; data?: Record<string, unknown> };

const TYPE_SHAPES: Record<string, FlowShape> = {
    trigger: 'trigger',
    agent: 'agent',
    sub: 'sub',
    note: 'note',
    sticky: 'note',
    card: 'card',
    table: 'table',
};

const STATUS_WORDS: Record<string, FlowStatus> = {
    success: 'success',
    ok: 'success',
    done: 'success',
    error: 'error',
    fail: 'error',
    failed: 'error',
    running: 'running',
    waiting: 'waiting',
    pending: 'waiting',
};

function isShape(value: unknown): value is FlowShape {
    return typeof value === 'string' && (SHAPES as readonly string[]).includes(value);
}

/**
 * `data.shape`, then the node type (a trigger flag counts), then the flow's default. Where
 * cards are the default, a trigger stays a card (a bolt marks it) so a flow keeps one look.
 */
export function resolveShape(node: NodeLike, fallback: FlowShape): FlowShape {
    const data = node.data ?? {};
    if (isShape(data.shape)) return data.shape;
    const trigger = node.type === 'trigger' || data.trigger === true || data.kind === 'trigger';
    if (trigger) return fallback === 'card' ? 'card' : 'trigger';
    if (node.type && TYPE_SHAPES[node.type]) return TYPE_SHAPES[node.type];
    return fallback;
}

/** `data.variant` when it is a known style (the pack's, which the config may extend), else the fallback. */
export function resolveVariant(node: NodeLike, fallback: string, allowed: readonly string[] = VARIANTS): string {
    const variant = node.data?.variant;
    return typeof variant === 'string' && allowed.includes(variant) ? variant : fallback;
}

export function resolveStatus(status: unknown): FlowStatus | null {
    return typeof status === 'string' ? (STATUS_WORDS[status.toLowerCase()] ?? null) : null;
}

export function portsFor(shape: FlowShape): { mainIn: boolean; mainOut: boolean; aiIn: boolean; aiOut: boolean } {
    return {
        mainIn: shape === 'node' || shape === 'agent' || shape === 'card' || shape === 'table',
        mainOut: shape === 'node' || shape === 'trigger' || shape === 'agent' || shape === 'card' || shape === 'table',
        aiIn: shape === 'agent',
        aiOut: shape === 'sub',
    };
}

/** An agent's connectors underneath: `data.inputs`, or chat model, memory and tool. */
export function agentInputs(node: NodeLike): AgentInput[] {
    const inputs = node.data?.inputs;
    if (!Array.isArray(inputs) || !inputs.length) {
        return [
            { id: 'model', label: 'Chat Model', required: true },
            { id: 'memory', label: 'Memory', required: false },
            { id: 'tool', label: 'Tool', required: false },
        ];
    }

    return inputs.map((input) => {
        if (input && typeof input === 'object') {
            const item = input as Record<string, unknown>;
            return { id: String(item.id), label: String(item.label ?? item.id), required: item.required === true };
        }
        return { id: String(input), label: String(input), required: false };
    });
}

export function canConnect(source: FlowChannel, target: FlowChannel): boolean {
    return source === target;
}

/** From a sub-node's top connector up into an agent's bottom one. */
export function verticalPath(sx: number, sy: number, tx: number, ty: number): string {
    const c = Math.max(30, Math.abs(sy - ty) * 0.5);
    return `M ${sx} ${sy} C ${sx} ${sy - c}, ${tx} ${ty + c}, ${tx} ${ty}`;
}

/** `bolt`, `arrow-path`, `ps:robot` name an icon; `▶`, `A` or a phrase are drawn as text. */
export function isIconName(value: unknown): value is string {
    return typeof value === 'string' && /^(?:(?:ps|phosphor):)?[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value) && value.length > 1;
}

/** `warning` or `violet-600` as the design system's variable; a hex or any CSS colour as given. */
export function cssColor(color: unknown): string | null {
    if (typeof color !== 'string' || color.trim() === '') return null;
    const match = /^([a-z]+)(?:-(\d{2,3}))?$/.exec(color.trim());
    if (!match || ['transparent', 'currentcolor', 'inherit', 'none'].includes(match[1])) return color;
    return match[2] ? `var(--color-${match[1]}-${match[2]})` : `var(--color-${match[1]}-500, var(--color-${match[1]}))`;
}

/**
 * Where a node's name goes. Classic tiles carry it below, n8n-style; Signal's capsules
 * carry it inside. Agents and cards always carry it inside, sub-nodes below, notes none.
 */
export function labelPlacement(shape: FlowShape, theme: string): 'inside' | 'below' | 'none' {
    if (shape === 'note') return 'none';
    if (shape === 'agent' || shape === 'card' || shape === 'table') return 'inside';
    if (shape === 'sub') return 'below';
    return theme === 'signal' ? 'inside' : 'below';
}

/** Signal draws data moving along a connection while it runs: two dots, staggered. */
export function particleCount(theme: string, edge: { animated?: boolean; status?: unknown }): number {
    if (theme !== 'signal') return 0;
    return edge.animated === true || resolveStatus(edge.status) === 'running' ? 2 : 0;
}

export type TableRow = { id: string; label: string; type: string | null; key: string | null };

/** Table geometry, in canvas pixels: a 44 px header, 28 px rows, a hairline border. */
const TABLE_HEADER = 44;
const TABLE_ROW = 28;

/** `data.rows`: names, or `{ id, label?, type?, key? }` (key: pk, fk…). */
export function tableRows(node: NodeLike): TableRow[] {
    const rows = node.data?.rows;
    if (!Array.isArray(rows)) return [];

    return rows.map((row) => {
        if (row && typeof row === 'object') {
            const item = row as Record<string, unknown>;
            return {
                id: String(item.id),
                label: String(item.label ?? item.id),
                type: item.type == null ? null : String(item.type),
                key: item.key == null ? null : String(item.key),
            };
        }
        return { id: String(row), label: String(row), type: null, key: null };
    });
}

export function tableHeight(rows: number): number {
    return 2 + TABLE_HEADER + rows * TABLE_ROW + 4;
}

/** A row's connectors sit at its middle, measured from the top of the node. */
export function rowPortTop(index: number): number {
    return 1 + TABLE_HEADER + index * TABLE_ROW + TABLE_ROW / 2;
}

type RowEdge = { id: string; source: string; target: string; sourceHandle?: string | null; targetHandle?: string | null };

/**
 * Everything a row feeds and is fed by: follow the connections that start or end on it,
 * row to row, upstream and downstream. A connection to a node's own connector (in / out)
 * is not a row, so the trace stops there.
 */
export function rowLineage(edges: RowEdge[], nodeId: string, rowId: string): { rows: Set<string>; edges: Set<string> } {
    const isRow = (handle: string | null | undefined): handle is string => !!handle && handle !== 'in' && handle !== 'out' && handle !== 'ai';
    const rows = new Set<string>([`${nodeId}:${rowId}`]);
    const linked = new Set<string>();
    const queue = [`${nodeId}:${rowId}`];

    while (queue.length) {
        const current = queue.shift() as string;
        for (const edge of edges) {
            const from = isRow(edge.sourceHandle) ? `${edge.source}:${edge.sourceHandle}` : null;
            const to = isRow(edge.targetHandle) ? `${edge.target}:${edge.targetHandle}` : null;
            if (from !== current && to !== current) continue;

            linked.add(edge.id);
            for (const next of [from, to]) {
                if (next && !rows.has(next)) {
                    rows.add(next);
                    queue.push(next);
                }
            }
        }
    }

    return { rows, edges: linked };
}

/**
 * A node's pipeline: every node it is fed by (upstream) and feeds (downstream), with the
 * connections between them. Siblings (another output of an upstream node) stay out.
 */
export function nodeLineage(edges: RowEdge[], nodeId: string): { nodes: Set<string>; edges: Set<string> } {
    const nodes = new Set<string>([nodeId]);
    const linked = new Set<string>();

    const walk = (direction: 'up' | 'down') => {
        const seen = new Set<string>([nodeId]);
        const queue = [nodeId];
        while (queue.length) {
            const current = queue.shift() as string;
            for (const edge of edges) {
                const from = direction === 'down' ? edge.source : edge.target;
                const to = direction === 'down' ? edge.target : edge.source;
                if (from !== current) continue;
                linked.add(edge.id);
                nodes.add(to);
                if (!seen.has(to)) {
                    seen.add(to);
                    queue.push(to);
                }
            }
        }
    };

    walk('up');
    walk('down');

    return { nodes, edges: linked };
}

import { onAlpineInit } from '../../runtime/alpine';
/**
 * NeuraKit Flow — native n8n-style node editor (no third-party graph libs).
 */

export type FlowPoint = { x: number; y: number };
export type FlowViewport = { x: number; y: number; zoom: number };

export type FlowNodeData = {
    label?: string;
    description?: string;
    icon?: string;
    color?: string;
    status?: string;
    outputs?: Array<{ id: string; label?: string }>;
    [key: string]: unknown;
};

export type FlowNode = {
    id: string;
    type?: string;
    position: FlowPoint;
    data?: FlowNodeData;
    width?: number;
    height?: number;
    selected?: boolean;
    draggable?: boolean;
    selectable?: boolean;
    connectable?: boolean;
};

export type FlowEdge = {
    id: string;
    source: string;
    target: string;
    sourceHandle?: string | null;
    targetHandle?: string | null;
    /** Vertical px offset from the source port center (negative = up). */
    sourceOffset?: number;
    /** Vertical px offset from the target port center (negative = up). */
    targetOffset?: number;
    type?: 'bezier' | 'smoothstep';
    animated?: boolean;
    selected?: boolean;
    label?: string;
};

export type FlowConfig = {
    nodes?: FlowNode[];
    edges?: FlowEdge[];
    minZoom?: number;
    maxZoom?: number;
    panOnScroll?: boolean;
    panOnDrag?: boolean;
    zoomOnWheelScroll?: boolean;
    zoomOnPinch?: boolean;
    fitViewOnInit?: boolean;
    autoCenter?: boolean;
    snapToGrid?: boolean;
    gridSize?: number;
    showMinimap?: boolean;
    showToolbar?: boolean;
    edgeType?: 'bezier' | 'smoothstep';
    defaultEdgeAnimated?: boolean;
    nodesDraggable?: boolean;
    nodesConnectable?: boolean;
    elementsSelectable?: boolean;
    direction?: 'lr' | 'tb';
    toolbarPosition?: string;
    background?: string;
    nodeWidth?: number;
    nodeHeight?: number;
    markerId?: string;
};

type ConnectingState = {
    active: boolean;
    sourceId: string | null;
    sourceHandle: string | null;
    hoverTargetId: string | null;
    cursor: FlowPoint;
};

type ReconnectState = {
    active: boolean;
    edgeId: string | null;
    end: 'source' | 'target' | null;
    cursor: FlowPoint;
    hoverNodeId: string | null;
    /** True while sliding the attachment on the same node (live offset). */
    sliding: boolean;
};

type DragNodeState = {
    active: boolean;
    ids: string[];
    start: FlowPoint;
    origins: Record<string, FlowPoint>;
    moved: boolean;
};

type PanState = {
    active: boolean;
    start: FlowPoint;
    origin: FlowPoint;
};

type PinchState = {
    active: boolean;
    startDistance: number;
    startZoom: number;
};

const DEFAULT_NODE_W = 92;
const DEFAULT_NODE_H = 92;
const SVG_NS = 'http://www.w3.org/2000/svg';

function uid(prefix = 'e'): string {
    return `${prefix}_${Math.random().toString(36).slice(2, 10)}`;
}

function clamp(n: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, n));
}

function snap(value: number, size: number): number {
    return Math.round(value / size) * size;
}

function normalizeNode(
    raw: Partial<FlowNode> & { id: string },
    index: number,
    defaults: { w: number; h: number; colGap: number; rowGap: number },
): FlowNode {
    const col = index % 3;
    const row = Math.floor(index / 3);
    const position = raw.position ?? {
        x: 80 + col * defaults.colGap,
        y: 80 + row * defaults.rowGap,
    };

    return {
        id: String(raw.id),
        type: raw.type ?? 'step',
        position: { x: position.x, y: position.y },
        data: { ...(raw.data ?? {}) },
        width: raw.width ?? defaults.w,
        height: raw.height ?? defaults.h,
        selected: !!raw.selected,
        draggable: raw.draggable,
        selectable: raw.selectable,
        connectable: raw.connectable,
    };
}

function normalizeEdge(
    raw: Partial<FlowEdge> & { source: string; target: string },
    edgeType: 'bezier' | 'smoothstep',
): FlowEdge {
    return {
        id: raw.id ? String(raw.id) : uid('edge'),
        source: String(raw.source),
        target: String(raw.target),
        sourceHandle: raw.sourceHandle ?? 'out',
        targetHandle: raw.targetHandle ?? 'in',
        sourceOffset: typeof raw.sourceOffset === 'number' ? raw.sourceOffset : 0,
        targetOffset: typeof raw.targetOffset === 'number' ? raw.targetOffset : 0,
        type: raw.type ?? edgeType,
        animated: !!raw.animated,
        selected: !!raw.selected,
        label: raw.label,
    };
}

function offsetPoint(
    from: { x: number; y: number },
    to: { x: number; y: number },
    amount: number,
): { x: number; y: number } {
    const dx = to.x - from.x;
    const dy = to.y - from.y;
    const len = Math.hypot(dx, dy) || 1;
    return {
        x: from.x + (dx / len) * amount,
        y: from.y + (dy / len) * amount,
    };
}

/** Self-loop on one node: out → up → over → down → in (orthogonal). */
function selfLoopPath(sx: number, sy: number, tx: number, ty: number): string {
    const exit = 28;
    const r = 10;
    const lift = 52;
    const top = Math.min(sy, ty) - lift;
    return [
        `M ${sx} ${sy}`,
        `L ${sx + exit - r} ${sy}`,
        `Q ${sx + exit} ${sy} ${sx + exit} ${sy - r}`,
        `L ${sx + exit} ${top + r}`,
        `Q ${sx + exit} ${top} ${sx + exit - r} ${top}`,
        `L ${tx - exit + r} ${top}`,
        `Q ${tx - exit} ${top} ${tx - exit} ${top + r}`,
        `L ${tx - exit} ${ty - r}`,
        `Q ${tx - exit} ${ty} ${tx - exit + r} ${ty}`,
        `L ${tx} ${ty}`,
    ].join(' ');
}

function bezierPath(sx: number, sy: number, tx: number, ty: number): string {
    // Forward only — reverse edges use loopBackPath via edgePath().
    const dist = Math.abs(tx - sx);
    const c = Math.max(60, dist * 0.45);
    return `M ${sx} ${sy} C ${sx + c} ${sy}, ${tx - c} ${ty}, ${tx} ${ty}`;
}

/** Orthogonal U-turn under the nodes (n8n-style), shared by bezier + smoothstep. */
function loopBackPath(sx: number, sy: number, tx: number, ty: number): string {
    const exit = 40;
    const r = 10;
    const around = Math.max(sy, ty) + 56;
    // Right → down → left → up → into target (rounded corners)
    return [
        `M ${sx} ${sy}`,
        `L ${sx + exit - r} ${sy}`,
        `Q ${sx + exit} ${sy} ${sx + exit} ${sy + r}`,
        `L ${sx + exit} ${around - r}`,
        `Q ${sx + exit} ${around} ${sx + exit - r} ${around}`,
        `L ${tx - exit + r} ${around}`,
        `Q ${tx - exit} ${around} ${tx - exit} ${around - r}`,
        `L ${tx - exit} ${ty + r}`,
        `Q ${tx - exit} ${ty} ${tx - exit + r} ${ty}`,
        `L ${tx} ${ty}`,
    ].join(' ');
}

function smoothstepPath(sx: number, sy: number, tx: number, ty: number): string {
    const r = 8;
    const mid = sx + (tx - sx) / 2;
    if (Math.abs(ty - sy) < r * 2) {
        return `M ${sx} ${sy} L ${tx} ${ty}`;
    }
    const dir = ty > sy ? 1 : -1;
    return [
        `M ${sx} ${sy}`,
        `L ${mid - r} ${sy}`,
        `Q ${mid} ${sy} ${mid} ${sy + dir * r}`,
        `L ${mid} ${ty - dir * r}`,
        `Q ${mid} ${ty} ${mid + r} ${ty}`,
        `L ${tx} ${ty}`,
    ].join(' ');
}

function edgePath(
    type: 'bezier' | 'smoothstep',
    sx: number,
    sy: number,
    tx: number,
    ty: number,
    options: { self?: boolean } = {},
): string {
    if (options.self) return selfLoopPath(sx, sy, tx, ty);
    // Reverse connections between different nodes: clean U-turn below.
    if (tx < sx + 16) return loopBackPath(sx, sy, tx, ty);
    return type === 'smoothstep' ? smoothstepPath(sx, sy, tx, ty) : bezierPath(sx, sy, tx, ty);
}

function createNeuraFlow(config: FlowConfig = {}) {
    const nodeW = config.nodeWidth ?? DEFAULT_NODE_W;
    const nodeH = config.nodeHeight ?? DEFAULT_NODE_H;
    const edgeType = config.edgeType ?? 'bezier';
    const fitOnInit = config.fitViewOnInit ?? config.autoCenter ?? true;

    const initialNodes = (config.nodes ?? []).map((n, i) =>
                normalizeNode(n, i, { w: nodeW, h: nodeH, colGap: 200, rowGap: 160 }),
    );
    const initialEdges = (config.edges ?? []).map((e) => normalizeEdge(e as any, edgeType));

    return {
        nodes: initialNodes as FlowNode[],
        edges: initialEdges as FlowEdge[],
        viewport: { x: 0, y: 0, zoom: 1 } as FlowViewport,

        minZoom: config.minZoom ?? 0.25,
        maxZoom: config.maxZoom ?? 2.5,
        panOnScroll: config.panOnScroll ?? true,
        panOnDrag: config.panOnDrag ?? true,
        zoomOnWheelScroll: config.zoomOnWheelScroll ?? true,
        zoomOnPinch: config.zoomOnPinch ?? true,
        snapToGrid: config.snapToGrid ?? true,
        gridSize: config.gridSize ?? 16,
        showMinimap: config.showMinimap ?? true,
        showToolbar: config.showToolbar ?? true,
        edgeType,
        defaultEdgeAnimated: config.defaultEdgeAnimated ?? false,
        nodesDraggable: config.nodesDraggable ?? true,
        nodesConnectable: config.nodesConnectable ?? true,
        elementsSelectable: config.elementsSelectable ?? true,
        direction: config.direction ?? 'lr',
        toolbarPosition: config.toolbarPosition ?? 'bottom left',
        background: config.background ?? 'dots',
        defaultNodeWidth: nodeW,
        defaultNodeHeight: nodeH,
        fitViewOnInit: fitOnInit,
        markerId: config.markerId || `flow-arrow-${Math.random().toString(36).slice(2, 9)}`,

        selectedNodeIds: [] as string[],
        selectedEdgeIds: [] as string[],
        edgesSvg: '',

        connecting: {
            active: false,
            sourceId: null,
            sourceHandle: null,
            hoverTargetId: null,
            cursor: { x: 0, y: 0 },
        } as ConnectingState,

        _reconnect: {
            active: false,
            edgeId: null,
            end: null,
            cursor: { x: 0, y: 0 },
            hoverNodeId: null,
            sliding: false,
        } as ReconnectState,

        _drag: { active: false, ids: [], start: { x: 0, y: 0 }, origins: {}, moved: false } as DragNodeState,
        _pan: { active: false, start: { x: 0, y: 0 }, origin: { x: 0, y: 0 } } as PanState,
        _pinch: { active: false, startDistance: 0, startZoom: 1 } as PinchState,
        _spaceDown: false,
        _raf: 0 as number,
        _ready: false,
        _pointerId: null as number | null,

        get transformStyle(): string {
            const { x, y, zoom } = this.viewport;
            return `translate(${x}px, ${y}px) scale(${zoom})`;
        },

        get backgroundStyle(): string {
            const { x, y, zoom } = this.viewport;
            const size = (this.background === 'grid' ? 24 : 18) * zoom;

            if (this.background === 'dots') {
                // Layer 1 = edge fade (fixed), layer 2 = dots (parallax)
                return [
                    `background-size: auto, ${size}px ${size}px`,
                    `background-position: center, ${x}px ${y}px`,
                ].join(';');
            }

            if (this.background === 'grid') {
                // Layer 1 = fade, layers 2–3 = grid lines
                return [
                    `background-size: auto, ${size}px ${size}px, ${size}px ${size}px`,
                    `background-position: center, ${x}px ${y}px, ${x}px ${y}px`,
                ].join(';');
            }

            return '';
        },

        get isConnecting(): boolean {
            return this.connecting.active || this._reconnect.active;
        },

        get connectionPreviewPath(): string {
            // Live slide keeps the real edge — no dashed preview
            if (this._reconnect.active && this._reconnect.sliding) return '';

            if (this._reconnect.active && this._reconnect.edgeId) {
                const edge = this.edges.find((e) => e.id === this._reconnect.edgeId);
                if (!edge) return '';
                const fixedEnd = this._reconnect.end === 'target' ? 'source' : 'target';
                const fixedNode = this.getNode(fixedEnd === 'source' ? edge.source : edge.target);
                if (!fixedNode) return '';
                const fixed = this.edgePortPoint(
                    fixedNode,
                    fixedEnd === 'source' ? (edge.sourceHandle ?? 'out') : (edge.targetHandle ?? 'in'),
                    fixedEnd,
                    fixedEnd === 'source' ? (edge.sourceOffset ?? 0) : (edge.targetOffset ?? 0),
                );
                const cur = this._reconnect.cursor;
                if (this._reconnect.end === 'target') {
                    const self = this._reconnect.hoverNodeId === edge.source;
                    if (self) {
                        const b = this.edgePortPoint(fixedNode, 'in', 'target', 0);
                        return edgePath(edge.type ?? this.edgeType, fixed.x, fixed.y, b.x, b.y, {
                            self: true,
                        });
                    }
                    return edgePath(edge.type ?? this.edgeType, fixed.x, fixed.y, cur.x, cur.y);
                }
                const self = this._reconnect.hoverNodeId === edge.target;
                if (self) {
                    const a = this.edgePortPoint(fixedNode, 'out', 'source', 0);
                    return edgePath(edge.type ?? this.edgeType, a.x, a.y, fixed.x, fixed.y, {
                        self: true,
                    });
                }
                return edgePath(edge.type ?? this.edgeType, cur.x, cur.y, fixed.x, fixed.y);
            }

            if (!this.connecting.active || !this.connecting.sourceId) return '';
            const src = this.getNode(this.connecting.sourceId);
            if (!src) return '';
            const p = this.handlePoint(src, this.connecting.sourceHandle ?? 'out', 'source');
            const self = this.connecting.hoverTargetId === this.connecting.sourceId;
            if (self) {
                const b = this.handlePoint(src, 'in', 'target');
                return edgePath(this.edgeType, p.x, p.y, b.x, b.y, { self: true });
            }
            return edgePath(this.edgeType, p.x, p.y, this.connecting.cursor.x, this.connecting.cursor.y);
        },

        get bounds() {
            if (!this.nodes.length) {
                return { minX: 0, minY: 0, maxX: 400, maxY: 300, width: 400, height: 300 };
            }
            let minX = Infinity;
            let minY = Infinity;
            let maxX = -Infinity;
            let maxY = -Infinity;
            for (const n of this.nodes) {
                const w = n.width ?? this.defaultNodeWidth;
                // Card + label stack under the tile
                const h = (n.height ?? this.defaultNodeHeight) + 48;
                minX = Math.min(minX, n.position.x);
                minY = Math.min(minY, n.position.y);
                maxX = Math.max(maxX, n.position.x + w);
                maxY = Math.max(maxY, n.position.y + h);
            }
            return { minX, minY, maxX, maxY, width: maxX - minX, height: maxY - minY };
        },

        get minimapNodes() {
            const b = this.bounds;
            const pad = 40;
            const bw = Math.max(b.width + pad * 2, 1);
            const bh = Math.max(b.height + pad * 2, 1);
            return this.nodes.map((n) => ({
                id: n.id,
                x: ((n.position.x - b.minX + pad) / bw) * 100,
                y: ((n.position.y - b.minY + pad) / bh) * 100,
                w: Math.max(((n.width ?? this.defaultNodeWidth) / bw) * 100, 4),
                h: Math.max(((n.height ?? this.defaultNodeHeight) / bh) * 100, 3),
                selected: this.selectedNodeIds.includes(n.id),
            }));
        },

        get minimapViewport() {
            const el = (this as any).$refs?.canvas as HTMLElement | undefined;
            if (!el) return { x: 0, y: 0, w: 100, h: 100 };
            const b = this.bounds;
            const pad = 40;
            const bw = Math.max(b.width + pad * 2, 1);
            const bh = Math.max(b.height + pad * 2, 1);
            const { zoom, x, y } = this.viewport;
            return {
                x: ((-x / zoom - b.minX + pad) / bw) * 100,
                y: ((-y / zoom - b.minY + pad) / bh) * 100,
                w: (el.clientWidth / zoom / bw) * 100,
                h: (el.clientHeight / zoom / bh) * 100,
            };
        },

        get toolbarClass(): string {
            const pos = this.toolbarPosition || 'bottom left';
            const parts: string[] = [];
            if (pos.includes('top')) parts.push('is-top');
            else parts.push('is-bottom');
            if (pos.includes('right')) parts.push('is-right');
            else parts.push('is-left');
            return parts.join(' ');
        },

        init() {
            this._onKeyDown = this._onKeyDown.bind(this);
            this._onKeyUp = this._onKeyUp.bind(this);
            window.addEventListener('keydown', this._onKeyDown);
            window.addEventListener('keyup', this._onKeyUp);

            const root = (this as any).$el as HTMLElement;
            root?.addEventListener(
                'alpine:destroying',
                () => {
                    window.removeEventListener('keydown', this._onKeyDown);
                    window.removeEventListener('keyup', this._onKeyUp);
                },
                { once: true },
            );

            // Wait until x-for nodes exist, fit the view, then draw edges after paint
            // (getBoundingClientRect was racing the CSS transform → arrows floated above).
            (this as any).$nextTick(() => {
                requestAnimationFrame(() => {
                    if (this.fitViewOnInit) {
                        this.fitView({ padding: 0.18 });
                    }
                    (this as any).$nextTick(() => {
                        requestAnimationFrame(() => {
                            this.refreshEdges();
                            this._ready = true;
                            this.emit('flow-init', {
                                nodes: this.nodes,
                                edges: this.edges,
                                viewport: this.viewport,
                            });
                        });
                    });
                });
            });
        },

        /** SVG namespace paths — innerHTML on <g> creates non-rendering HTML nodes. */
        refreshEdges() {
            const layer = (this as any).$refs?.edgeLayer as SVGGElement | undefined;
            if (!layer) return;

            this.syncNodeSizes();

            while (layer.firstChild) layer.removeChild(layer.firstChild);

            for (const edge of this.edges) {
                // Hide only while reconnecting to another node (not while sliding)
                if (
                    this._reconnect.active &&
                    this._reconnect.edgeId === edge.id &&
                    !this._reconnect.sliding
                ) {
                    continue;
                }

                const src = this.getNode(edge.source);
                const tgt = this.getNode(edge.target);
                if (!src || !tgt) continue;

                const a = this.edgePortPoint(
                    src,
                    edge.sourceHandle ?? 'out',
                    'source',
                    edge.sourceOffset ?? 0,
                );
                const b = this.edgePortPoint(
                    tgt,
                    edge.targetHandle ?? 'in',
                    'target',
                    edge.targetOffset ?? 0,
                );
                const isSelf = edge.source === edge.target;
                const start = isSelf ? a : offsetPoint(a, b, 4);
                const end = isSelf ? offsetPoint(b, { x: b.x - 1, y: b.y }, 8) : offsetPoint(b, a, 8);
                const d = edgePath(edge.type ?? this.edgeType, start.x, start.y, end.x, end.y, {
                    self: isSelf,
                });
                const selected = this.selectedEdgeIds.includes(edge.id);
                const dragging =
                    this._reconnect.active &&
                    this._reconnect.edgeId === edge.id &&
                    this._reconnect.sliding;

                const hit = document.createElementNS(SVG_NS, 'path');
                hit.setAttribute('class', 'flow-edge-hit');
                hit.setAttribute('d', d);
                hit.setAttribute('data-edge-id', edge.id);
                hit.setAttribute('fill', 'none');
                layer.appendChild(hit);

                const path = document.createElementNS(SVG_NS, 'path');
                const cls = ['flow-edge'];
                if (selected || dragging) cls.push('is-on');
                if (edge.animated) cls.push('is-run');
                path.setAttribute('class', cls.join(' '));
                path.setAttribute('d', d);
                path.setAttribute('data-edge-id', edge.id);
                path.setAttribute('fill', 'none');
                path.setAttribute(
                    'marker-end',
                    selected || dragging ? `url(#${this.markerId}-on)` : `url(#${this.markerId})`,
                );
                layer.appendChild(path);

                if ((selected || dragging) && this.nodesConnectable) {
                    this.appendEdgeEndpoint(layer, edge.id, 'source', a.x, a.y);
                    this.appendEdgeEndpoint(layer, edge.id, 'target', b.x, b.y);
                }
            }
        },

        appendEdgeEndpoint(
            layer: SVGGElement,
            edgeId: string,
            end: 'source' | 'target',
            x: number,
            y: number,
        ) {
            const g = document.createElementNS(SVG_NS, 'g');
            g.setAttribute('class', 'flow-edge-end');
            g.setAttribute('data-edge-id', edgeId);
            g.setAttribute('data-edge-end', end);
            g.setAttribute('transform', `translate(${x}, ${y})`);

            const hit = document.createElementNS(SVG_NS, 'circle');
            hit.setAttribute('r', '10');
            hit.setAttribute('class', 'flow-edge-end-hit');
            hit.setAttribute('data-edge-id', edgeId);
            hit.setAttribute('data-edge-end', end);
            g.appendChild(hit);

            const dot = document.createElementNS(SVG_NS, 'circle');
            dot.setAttribute('r', '4.5');
            dot.setAttribute('class', 'flow-edge-end-dot');
            dot.setAttribute('data-edge-id', edgeId);
            dot.setAttribute('data-edge-end', end);
            g.appendChild(dot);

            layer.appendChild(g);
        },

        /** Port point + vertical offset for an edge endpoint. */
        edgePortPoint(
            node: FlowNode,
            handleId: string,
            kind: 'source' | 'target',
            offset = 0,
        ): FlowPoint {
            const p = this.portPoint(node, handleId, kind);
            if (!offset) return p;
            const h = node.height ?? this.defaultNodeHeight;
            return {
                x: p.x,
                y: clamp(p.y + offset, node.position.y + 8, node.position.y + h - 8),
            };
        },

        /** Keep card size in sync (labels sit outside the connection box). */
        syncNodeSizes() {
            const root = (this as any).$el as HTMLElement | undefined;
            if (!root) return;
            for (const node of this.nodes) {
                const el = root.querySelector(
                    `.flow-node[data-node-id="${CSS.escape(node.id)}"] .flow-card`,
                ) as HTMLElement | null;
                if (!el) continue;
                node.width = el.offsetWidth;
                node.height = el.offsetHeight;
            }
        },

        /**
         * Port center in flow space using layout offsets (not getBoundingClientRect).
         * Avoids racing Alpine's CSS transform after fitView — that was why arrows
         * floated above nodes until the first drag refreshed them.
         */
        portPoint(node: FlowNode, handleId: string, kind: 'source' | 'target'): FlowPoint {
            const root = (this as any).$el as HTMLElement | undefined;
            const nodeEl = root?.querySelector(
                `.flow-node[data-node-id="${CSS.escape(node.id)}"]`,
            ) as HTMLElement | null;

            if (nodeEl) {
                const port =
                    kind === 'target'
                        ? (nodeEl.querySelector('.flow-port.in') as HTMLElement | null)
                        : ((nodeEl.querySelector(
                              `.flow-port.out[data-handle-id="${CSS.escape(handleId)}"]`,
                          ) as HTMLElement | null) ??
                          (nodeEl.querySelector('.flow-port.out') as HTMLElement | null));

                if (port) {
                    // Ports use transform: translateY(-50%), so visual center Y ≈ offsetTop.
                    return {
                        x: node.position.x + port.offsetLeft + port.offsetWidth / 2,
                        y: node.position.y + port.offsetTop,
                    };
                }
            }

            return this.handlePoint(node, handleId, kind);
        },

        scheduleEdgeRefresh() {
            if (this._raf) return;
            this._raf = requestAnimationFrame(() => {
                this._raf = 0;
                this.refreshEdges();
            }) as unknown as number;
        },

        emit(name: string, detail: unknown = {}) {
            ((this as any).$el as HTMLElement | undefined)?.dispatchEvent(
                new CustomEvent(name, { detail, bubbles: true }),
            );
        },

        emitChange() {
            this.emit('flow-change', this.toObject());
        },

        getNode(id: string): FlowNode | undefined {
            return this.nodes.find((n) => n.id === id);
        },

        nodeOutputs(node: FlowNode): Array<{ id: string; label?: string }> {
            const outs = node.data?.outputs;
            if (Array.isArray(outs) && outs.length) {
                return outs.map((o) =>
                    typeof o === 'string'
                        ? { id: o, label: o }
                        : { id: String(o.id), label: o.label ?? String(o.id) },
                );
            }
            return [{ id: 'out', label: '' }];
        },

        handleSourceStyle(node: FlowNode, handleId: string): string {
            const outputs = this.nodeOutputs(node);
            if (outputs.length <= 1) return '';
            const idx = Math.max(0, outputs.findIndex((o) => o.id === handleId));
            const cardH = node.height || this.defaultNodeHeight;
            const top = (cardH / (outputs.length + 1)) * (idx + 1);
            return `top: ${top}px; transform: translateY(-50%);`;
        },

        nodeStyle(node: FlowNode): string {
            return [
                `transform: translate3d(${node.position.x}px, ${node.position.y}px, 0)`,
                `--flow-accent: ${this.nodeAccent(node)}`,
            ].join(';');
        },

        nodeIcon(node: FlowNode): string {
            if (node.data?.icon) return String(node.data.icon);
            return String(node.data?.label || node.type || 'S').slice(0, 1).toUpperCase();
        },

        nodeAccent(node: FlowNode): string {
            return (node.data?.color as string) || '#ff6d5a';
        },

        isTrigger(node: FlowNode): boolean {
            return (
                node.type === 'trigger' ||
                !!(node.data && (node.data.trigger === true || node.data.kind === 'trigger'))
            );
        },

        hasOutgoing(nodeId: string, handleId: string): boolean {
            return this.edges.some(
                (e) => e.source === nodeId && (e.sourceHandle ?? 'out') === handleId,
            );
        },

        handlePoint(node: FlowNode, handleId: string, kind: 'source' | 'target'): FlowPoint {
            const w = node.width ?? this.defaultNodeWidth;
            const h = node.height ?? this.defaultNodeHeight;
            const outputs = this.nodeOutputs(node);

            if (kind === 'target' || handleId === 'in') {
                return { x: node.position.x, y: node.position.y + h / 2 };
            }

            const idx = Math.max(0, outputs.findIndex((o) => o.id === handleId));
            const count = outputs.length;
            const y =
                count === 1
                    ? node.position.y + h / 2
                    : node.position.y + (h / (count + 1)) * (idx + 1);

            return { x: node.position.x + w, y };
        },

        screenToFlow(clientX: number, clientY: number): FlowPoint {
            const el = (this as any).$refs?.canvas as HTMLElement;
            const rect = el.getBoundingClientRect();
            const { x, y, zoom } = this.viewport;
            return {
                x: (clientX - rect.left - x) / zoom,
                y: (clientY - rect.top - y) / zoom,
            };
        },

        setViewport(viewport: Partial<FlowViewport>, options: { emit?: boolean } = {}) {
            this.viewport = {
                x: viewport.x ?? this.viewport.x,
                y: viewport.y ?? this.viewport.y,
                zoom: clamp(viewport.zoom ?? this.viewport.zoom, this.minZoom, this.maxZoom),
            };
            if (options.emit !== false) this.emitChange();
        },

        zoomIn(factor = 1.15) {
            this.zoomAtCenter(this.viewport.zoom * factor);
        },

        zoomOut(factor = 1.15) {
            this.zoomAtCenter(this.viewport.zoom / factor);
        },

        zoomAtCenter(nextZoom: number) {
            const el = (this as any).$refs?.canvas as HTMLElement | undefined;
            if (!el) {
                this.setViewport({ zoom: nextZoom });
                return;
            }
            this.zoomAt(el.clientWidth / 2, el.clientHeight / 2, nextZoom);
        },

        zoomAt(localX: number, localY: number, nextZoom: number) {
            const z0 = this.viewport.zoom;
            const z1 = clamp(nextZoom, this.minZoom, this.maxZoom);
            if (z0 === z1) return;
            const wx = (localX - this.viewport.x) / z0;
            const wy = (localY - this.viewport.y) / z0;
            this.setViewport({
                zoom: z1,
                x: localX - wx * z1,
                y: localY - wy * z1,
            });
        },

        fitView(options: { padding?: number } = {}) {
            const el = (this as any).$refs?.canvas as HTMLElement | undefined;
            if (!el) return;
            const padding = options.padding ?? 0.15;
            const b = this.bounds;
            const zoom = clamp(
                Math.min(
                    (el.clientWidth * (1 - padding * 2)) / Math.max(b.width, 1),
                    (el.clientHeight * (1 - padding * 2)) / Math.max(b.height, 1),
                ),
                this.minZoom,
                this.maxZoom,
            );
            this.setViewport({
                zoom,
                x: el.clientWidth / 2 - (b.minX + b.width / 2) * zoom,
                y: el.clientHeight / 2 - (b.minY + b.height / 2) * zoom,
            });
        },

        addNode(input: Partial<FlowNode> & { id?: string; data?: FlowNodeData }) {
            const id = String(input.id ?? uid('node'));
            if (this.getNode(id)) return this.getNode(id)!;
            const node = normalizeNode(
                { ...input, id, position: input.position ?? { x: 120, y: 120 } },
                this.nodes.length,
                { w: this.defaultNodeWidth, h: this.defaultNodeHeight, colGap: 300, rowGap: 120 },
            );
            this.nodes.push(node);
            this.emit('flow-node-add', { node });
            this.emitChange();
            this.scheduleEdgeRefresh();
            return node;
        },

        removeNodes(ids: string[]) {
            if (!ids.length) return;
            const idSet = new Set(ids.map(String));
            this.nodes = this.nodes.filter((n) => !idSet.has(n.id));
            this.edges = this.edges.filter((e) => !idSet.has(e.source) && !idSet.has(e.target));
            this.selectedNodeIds = this.selectedNodeIds.filter((id) => !idSet.has(id));
            this.emit('flow-nodes-deleted', { ids: [...idSet] });
            this.emitChange();
            this.refreshEdges();
        },

        addEdge(input: Partial<FlowEdge> & { source: string; target: string }) {
            const sourceHandle = input.sourceHandle ?? 'out';
            const targetHandle = input.targetHandle ?? 'in';
            const exists = this.edges.some(
                (e) =>
                    e.source === input.source &&
                    e.target === input.target &&
                    (e.sourceHandle ?? 'out') === sourceHandle &&
                    (e.targetHandle ?? 'in') === targetHandle,
            );
            if (exists) return null;
            const edge = normalizeEdge(
                {
                    ...input,
                    sourceHandle,
                    targetHandle,
                    animated: input.animated ?? this.defaultEdgeAnimated,
                },
                this.edgeType,
            );
            this.edges.push(edge);
            this.emit('flow-connect', { edge });
            this.emitChange();
            this.refreshEdges();
            return edge;
        },

        removeEdges(ids: string[]) {
            const idSet = new Set(ids.map(String));
            this.edges = this.edges.filter((e) => !idSet.has(e.id));
            this.selectedEdgeIds = this.selectedEdgeIds.filter((id) => !idSet.has(id));
            this.emitChange();
            this.refreshEdges();
        },

        updateEdge(id: string, patch: Partial<Omit<FlowEdge, 'id'>>) {
            const edge = this.edges.find((e) => e.id === id);
            if (!edge) return null;
            if (patch.source !== undefined) edge.source = String(patch.source);
            if (patch.target !== undefined) edge.target = String(patch.target);
            if (patch.sourceHandle !== undefined) edge.sourceHandle = patch.sourceHandle;
            if (patch.targetHandle !== undefined) edge.targetHandle = patch.targetHandle;
            if (typeof patch.sourceOffset === 'number') edge.sourceOffset = patch.sourceOffset;
            if (typeof patch.targetOffset === 'number') edge.targetOffset = patch.targetOffset;
            if (patch.type !== undefined) edge.type = patch.type;
            if (patch.animated !== undefined) edge.animated = !!patch.animated;
            if (patch.label !== undefined) edge.label = patch.label;
            this.emit('flow-edge-update', { edge });
            this.emitChange();
            this.refreshEdges();
            return edge;
        },

        selectNodes(ids: string[], options: { additive?: boolean } = {}) {
            if (!this.elementsSelectable) return;
            const next = options.additive
                ? Array.from(new Set([...this.selectedNodeIds, ...ids]))
                : [...ids];
            this.selectedNodeIds = next;
            this.nodes.forEach((n) => {
                n.selected = next.includes(n.id);
            });
            if (!options.additive) {
                this.selectedEdgeIds = [];
                this.edges.forEach((e) => {
                    e.selected = false;
                });
                this.refreshEdges();
            }
            this.emit('flow-node-select', { ids: next });
        },

        selectEdge(id: string, options: { additive?: boolean } = {}) {
            if (!this.elementsSelectable) return;
            const next = options.additive
                ? Array.from(new Set([...this.selectedEdgeIds, id]))
                : [id];
            this.selectedEdgeIds = next;
            this.edges.forEach((e) => {
                e.selected = next.includes(e.id);
            });
            if (!options.additive) {
                this.selectedNodeIds = [];
                this.nodes.forEach((n) => {
                    n.selected = false;
                });
            }
            this.refreshEdges();
        },

        clearSelection() {
            this.selectedNodeIds = [];
            this.selectedEdgeIds = [];
            this.nodes.forEach((n) => {
                n.selected = false;
            });
            this.edges.forEach((e) => {
                e.selected = false;
            });
            this.refreshEdges();
            this.emit('flow-node-select', { ids: [] });
        },

        deleteSelection() {
            if (this.selectedNodeIds.length) this.removeNodes([...this.selectedNodeIds]);
            if (this.selectedEdgeIds.length) this.removeEdges([...this.selectedEdgeIds]);
        },

        toObject() {
            return {
                nodes: this.nodes.map((n) => ({
                    id: n.id,
                    type: n.type,
                    position: { ...n.position },
                    data: { ...(n.data ?? {}) },
                })),
                edges: this.edges.map((e) => ({
                    id: e.id,
                    source: e.source,
                    target: e.target,
                    sourceHandle: e.sourceHandle,
                    targetHandle: e.targetHandle,
                    sourceOffset: e.sourceOffset ?? 0,
                    targetOffset: e.targetOffset ?? 0,
                    type: e.type,
                    animated: e.animated,
                    label: e.label,
                })),
                viewport: { ...this.viewport },
            };
        },

        onWheel(e: WheelEvent) {
            const el = (this as any).$refs?.canvas as HTMLElement;
            if (!el) return;

            if (this.zoomOnWheelScroll || e.ctrlKey || e.metaKey) {
                e.preventDefault();
                const rect = el.getBoundingClientRect();
                this.zoomAt(
                    e.clientX - rect.left,
                    e.clientY - rect.top,
                    this.viewport.zoom * Math.exp(-e.deltaY * 0.0015),
                );
                return;
            }

            if (this.panOnScroll) {
                e.preventDefault();
                this.setViewport(
                    {
                        x: this.viewport.x - e.deltaX,
                        y: this.viewport.y - e.deltaY,
                    },
                    { emit: false },
                );
            }
        },

        onCanvasPointerDown(e: PointerEvent) {
            const target = e.target as HTMLElement;
            if (
                target.closest('.flow-node') ||
                target.closest('.flow-port') ||
                target.closest('.flow-edge-hit') ||
                target.closest('.flow-edge') ||
                target.closest('.flow-edge-end')
            ) {
                return;
            }

            if (this.connecting.active) this.cancelConnection();
            if (this._reconnect.active) this.cancelReconnect();
            if (!e.shiftKey) this.clearSelection();

            if (!(this.panOnDrag && (e.button === 1 || e.button === 0 || this._spaceDown))) return;

            e.preventDefault();
            this._pointerId = e.pointerId;
            (e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId);
            this._pan = {
                active: true,
                start: { x: e.clientX, y: e.clientY },
                origin: { x: this.viewport.x, y: this.viewport.y },
            };
        },

        onCanvasPointerMove(e: PointerEvent) {
            if (this._pan.active) {
                this.setViewport(
                    {
                        x: this._pan.origin.x + (e.clientX - this._pan.start.x),
                        y: this._pan.origin.y + (e.clientY - this._pan.start.y),
                    },
                    { emit: false },
                );
                return;
            }

            if (this._drag.active) {
                const flow = this.screenToFlow(e.clientX, e.clientY);
                const dx = flow.x - this._drag.start.x;
                const dy = flow.y - this._drag.start.y;
                if (!this._drag.moved && (Math.abs(dx) > 2 || Math.abs(dy) > 2)) {
                    this._drag.moved = true;
                }
                if (!this._drag.moved) return;

                for (const id of this._drag.ids) {
                    const node = this.getNode(id);
                    const origin = this._drag.origins[id];
                    if (!node || !origin) continue;
                    let x = origin.x + dx;
                    let y = origin.y + dy;
                    if (this.snapToGrid) {
                        x = snap(x, this.gridSize);
                        y = snap(y, this.gridSize);
                    }
                    node.position.x = x;
                    node.position.y = y;
                }
                this.scheduleEdgeRefresh();
                return;
            }

            if (this._reconnect.active) {
                this._reconnect.cursor = this.screenToFlow(e.clientX, e.clientY);
                this.applyReconnectDrag(e.clientX, e.clientY);
                return;
            }

            if (this.connecting.active) {
                this.connecting.cursor = this.screenToFlow(e.clientX, e.clientY);
                const hit = this.hitConnectTarget(e.clientX, e.clientY);
                // Self-loop only when hovering the input port of the same node
                if (!hit) {
                    this.connecting.hoverTargetId = null;
                } else if (hit.nodeId === this.connecting.sourceId) {
                    this.connecting.hoverTargetId = hit.viaPort ? hit.nodeId : null;
                } else {
                    this.connecting.hoverTargetId = hit.nodeId;
                }
            }
        },

        onCanvasPointerUp(e: PointerEvent) {
            if (this._pan.active) {
                this._pan.active = false;
                this.emitChange();
            }

            if (this._drag.active) {
                const moved = this._drag.moved;
                this._drag.active = false;
                this._drag.ids = [];
                if (moved) {
                    this.refreshEdges();
                    this.emitChange();
                }
            }

            if (this._reconnect.active) {
                this.finishReconnect(e.clientX, e.clientY);
            }

            if (this.connecting.active) {
                const hit = this.hitConnectTarget(e.clientX, e.clientY);
                const canConnect =
                    hit &&
                    (hit.nodeId !== this.connecting.sourceId || hit.viaPort);
                if (canConnect && hit) {
                    this.addEdge({
                        source: this.connecting.sourceId!,
                        target: hit.nodeId,
                        sourceHandle: this.connecting.sourceHandle,
                        targetHandle: hit.handleId,
                    });
                }
                this.cancelConnection();
            }

            this._pointerId = null;
        },

        /** Resolve drop target under cursor (ignores SVG edge hit-areas). */
        hitConnectTarget(
            clientX: number,
            clientY: number,
        ): { nodeId: string; handleId: string; viaPort: boolean } | null {
            const stack =
                typeof document.elementsFromPoint === 'function'
                    ? (document.elementsFromPoint(clientX, clientY) as Element[])
                    : ([document.elementFromPoint(clientX, clientY)].filter(Boolean) as Element[]);

            for (const el of stack) {
                if (!(el instanceof Element)) continue;
                if (el.closest?.('.flow-edge-hit') || el.closest?.('.flow-edge-end')) continue;
                const port = el.closest?.('.flow-port.in') as HTMLElement | null;
                const nodeEl = (port?.closest('.flow-node') || el.closest?.('.flow-node')) as HTMLElement | null;
                const nodeId = nodeEl?.dataset?.nodeId;
                if (!nodeId) continue;
                return {
                    nodeId,
                    handleId: port?.dataset?.handleId ?? 'in',
                    viaPort: !!port,
                };
            }
            return null;
        },

        hitReconnectTarget(
            clientX: number,
            clientY: number,
            end: 'source' | 'target',
        ): { nodeId: string; handleId: string; viaPort: boolean; offset: number } | null {
            const stack =
                typeof document.elementsFromPoint === 'function'
                    ? (document.elementsFromPoint(clientX, clientY) as Element[])
                    : ([document.elementFromPoint(clientX, clientY)].filter(Boolean) as Element[]);

            const portSel = end === 'source' ? '.flow-port.out' : '.flow-port.in';
            const flow = this.screenToFlow(clientX, clientY);

            for (const el of stack) {
                if (!(el instanceof Element)) continue;
                if (el.closest?.('.flow-edge-hit') || el.closest?.('.flow-edge-end')) continue;
                const port = el.closest?.(portSel) as HTMLElement | null;
                const nodeEl = (port?.closest('.flow-node') || el.closest?.('.flow-node')) as HTMLElement | null;
                const nodeId = nodeEl?.dataset?.nodeId;
                if (!nodeId) continue;
                const node = this.getNode(nodeId);
                if (!node) continue;
                const handleId =
                    port?.dataset?.handleId ?? (end === 'source' ? 'out' : 'in');
                return {
                    nodeId,
                    handleId,
                    viaPort: !!port,
                    offset: this.offsetAlongPort(node, handleId, end, flow.y),
                };
            }
            return null;
        },

        /** Vertical offset from the natural port center for this Y in flow space. */
        offsetAlongPort(
            node: FlowNode,
            handleId: string,
            kind: 'source' | 'target',
            flowY: number,
        ): number {
            const base = this.portPoint(node, handleId, kind);
            const h = node.height ?? this.defaultNodeHeight;
            return clamp(flowY - base.y, -(h / 2 - 8), h / 2 - 8);
        },

        /**
         * While dragging an endpoint: slide offset on the same node (live),
         * or preview a reconnect to another node.
         */
        applyReconnectDrag(clientX: number, clientY: number) {
            const { edgeId, end } = this._reconnect;
            if (!edgeId || !end) return;
            const edge = this.edges.find((e) => e.id === edgeId);
            if (!edge) return;

            const originalId = end === 'source' ? edge.source : edge.target;
            const handleId =
                end === 'source' ? (edge.sourceHandle ?? 'out') : (edge.targetHandle ?? 'in');
            const slide = this.hitSlideOnNode(clientX, clientY, originalId, handleId, end);
            const hit = slide ?? this.hitReconnectTarget(clientX, clientY, end);

            if (slide || (hit && hit.nodeId === originalId)) {
                const offset = (slide ?? hit)!.offset;
                const wasSliding = this._reconnect.sliding;
                this._reconnect.sliding = true;
                this._reconnect.hoverNodeId = originalId;
                if (end === 'source') edge.sourceOffset = offset;
                else edge.targetOffset = offset;
                this.scheduleEdgeRefresh();
                if (!wasSliding) this.refreshEdges();
                return;
            }

            const wasSliding = this._reconnect.sliding;
            this._reconnect.sliding = false;
            this._reconnect.hoverNodeId = hit?.nodeId ?? null;
            if (wasSliding) this.refreshEdges();
        },

        /** Cursor near the left/right side of a node → treat as sliding that attachment. */
        hitSlideOnNode(
            clientX: number,
            clientY: number,
            nodeId: string,
            handleId: string,
            end: 'source' | 'target',
        ): { nodeId: string; handleId: string; viaPort: boolean; offset: number } | null {
            const node = this.getNode(nodeId);
            if (!node) return null;
            const flow = this.screenToFlow(clientX, clientY);
            const w = node.width ?? this.defaultNodeWidth;
            const h = node.height ?? this.defaultNodeHeight;
            const sideX = end === 'source' ? node.position.x + w : node.position.x;
            const pad = 56;
            if (Math.abs(flow.x - sideX) > pad) return null;
            if (flow.y < node.position.y - 24 || flow.y > node.position.y + h + 24) return null;
            return {
                nodeId,
                handleId,
                viaPort: true,
                offset: this.offsetAlongPort(node, handleId, end, flow.y),
            };
        },

        startReconnect(edgeId: string, end: 'source' | 'target', e: PointerEvent) {
            if (!this.nodesConnectable) return;
            const edge = this.edges.find((ed) => ed.id === edgeId);
            if (!edge) return;
            e.stopPropagation();
            e.preventDefault();
            this.selectEdge(edgeId);
            const cursor = this.screenToFlow(e.clientX, e.clientY);
            this._reconnect = {
                active: true,
                edgeId,
                end,
                cursor,
                hoverNodeId: end === 'source' ? edge.source : edge.target,
                sliding: true,
            };
            this._pointerId = e.pointerId;
            (this as any).$refs?.canvas?.setPointerCapture?.(e.pointerId);
            this.applyReconnectDrag(e.clientX, e.clientY);
        },

        finishReconnect(clientX: number, clientY: number) {
            const { edgeId, end, sliding } = this._reconnect;
            if (!edgeId || !end) {
                this.cancelReconnect();
                return;
            }
            const edge = this.edges.find((e) => e.id === edgeId);
            if (!edge) {
                this.cancelReconnect();
                return;
            }

            if (sliding) {
                this.emit('flow-edge-update', { edge });
                this.emitChange();
                this.cancelReconnect();
                return;
            }

            const hit = this.hitReconnectTarget(clientX, clientY, end);
            if (hit) {
                const otherId = end === 'source' ? edge.target : edge.source;
                const isSelf = hit.nodeId === otherId;
                if (!isSelf || hit.viaPort) {
                    if (end === 'source') {
                        this.updateEdge(edgeId, {
                            source: hit.nodeId,
                            sourceHandle: hit.handleId,
                            sourceOffset: hit.offset,
                        });
                    } else {
                        this.updateEdge(edgeId, {
                            target: hit.nodeId,
                            targetHandle: hit.handleId,
                            targetOffset: hit.offset,
                        });
                    }
                }
            }
            this.cancelReconnect();
        },

        cancelReconnect() {
            this._reconnect = {
                active: false,
                edgeId: null,
                end: null,
                cursor: { x: 0, y: 0 },
                hoverNodeId: null,
                sliding: false,
            };
            this.refreshEdges();
        },

        onNodePointerDown(e: PointerEvent, nodeId: string) {
            if (e.button !== 0) return;
            if ((e.target as HTMLElement).closest('.flow-port')) return;

            e.stopPropagation();
            const node = this.getNode(nodeId);
            if (!node) return;

            const additive = e.shiftKey;
            if (!this.selectedNodeIds.includes(nodeId)) {
                this.selectNodes([nodeId], { additive });
            } else if (additive) {
                this.selectNodes(this.selectedNodeIds.filter((id) => id !== nodeId));
                return;
            }

            if (!(node.draggable ?? this.nodesDraggable)) return;

            const flow = this.screenToFlow(e.clientX, e.clientY);
            const ids = this.selectedNodeIds.includes(nodeId) ? [...this.selectedNodeIds] : [nodeId];
            const origins: Record<string, FlowPoint> = {};
            for (const id of ids) {
                const n = this.getNode(id);
                if (n) origins[id] = { ...n.position };
            }

            this._drag = { active: true, ids, start: flow, origins, moved: false };
            (e.currentTarget as HTMLElement).setPointerCapture?.(e.pointerId);
        },

        onHandlePointerDown(e: PointerEvent, nodeId: string, handleId: string, kind: 'source' | 'target') {
            if (!this.nodesConnectable || e.button !== 0 || kind !== 'source') return;
            e.stopPropagation();
            e.preventDefault();

            const node = this.getNode(nodeId);
            if (!node || node.connectable === false) return;

            const point = this.handlePoint(node, handleId, 'source');
            this.connecting = {
                active: true,
                sourceId: nodeId,
                sourceHandle: handleId,
                hoverTargetId: null,
                cursor: { ...point },
            };
            this._pointerId = e.pointerId;
            (this as any).$refs?.canvas?.setPointerCapture?.(e.pointerId);
        },

        onEdgesSvgPointerDown(e: PointerEvent) {
            const el = e.target as Element | null;
            const end = el?.getAttribute?.('data-edge-end') as 'source' | 'target' | null;
            const id = el?.getAttribute?.('data-edge-id');
            if (end && id && (end === 'source' || end === 'target')) {
                this.startReconnect(id, end, e);
            }
        },

        onEdgesSvgClick(e: MouseEvent) {
            const el = e.target as Element | null;
            if (el?.getAttribute?.('data-edge-end')) return;
            const id = el?.getAttribute?.('data-edge-id');
            if (!id) return;
            e.stopPropagation();
            this.selectEdge(id, { additive: e.shiftKey });
        },

        cancelConnection() {
            this.connecting = {
                active: false,
                sourceId: null,
                sourceHandle: null,
                hoverTargetId: null,
                cursor: { x: 0, y: 0 },
            };
        },

        onTouchStart(e: TouchEvent) {
            if (!this.zoomOnPinch || e.touches.length !== 2) return;
            const [a, b] = [e.touches[0], e.touches[1]];
            this._pinch = {
                active: true,
                startDistance: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY),
                startZoom: this.viewport.zoom,
            };
        },

        onTouchMove(e: TouchEvent) {
            if (!this._pinch.active || e.touches.length !== 2) return;
            e.preventDefault();
            const [a, b] = [e.touches[0], e.touches[1]];
            const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
            this.zoomAtCenter(this._pinch.startZoom * (dist / Math.max(this._pinch.startDistance, 1)));
        },

        onTouchEnd() {
            this._pinch.active = false;
        },

        _onKeyDown(e: KeyboardEvent) {
            const tag = (e.target as HTMLElement)?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || (e.target as HTMLElement)?.isContentEditable) return;

            if (e.code === 'Space') this._spaceDown = true;

            if (
                (e.key === 'Delete' || e.key === 'Backspace') &&
                (this.selectedNodeIds.length || this.selectedEdgeIds.length)
            ) {
                e.preventDefault();
                this.deleteSelection();
            }

            if (e.key === 'Escape') {
                if (this.connecting.active) this.cancelConnection();
                else if (this._reconnect.active) this.cancelReconnect();
                else this.clearSelection();
            }

            if ((e.metaKey || e.ctrlKey) && e.key === 'a') {
                e.preventDefault();
                this.selectNodes(this.nodes.map((n) => n.id));
            }
            if ((e.metaKey || e.ctrlKey) && (e.key === '=' || e.key === '+')) {
                e.preventDefault();
                this.zoomIn();
            }
            if ((e.metaKey || e.ctrlKey) && e.key === '-') {
                e.preventDefault();
                this.zoomOut();
            }
            if ((e.metaKey || e.ctrlKey) && e.key === '0') {
                e.preventDefault();
                this.fitView();
            }
        },

        _onKeyUp(e: KeyboardEvent) {
            if (e.code === 'Space') this._spaceDown = false;
        },

        isNodeSelected(id: string): boolean {
            return this.selectedNodeIds.includes(id);
        },

        isEdgeSelected(id: string): boolean {
            return this.selectedEdgeIds.includes(id);
        },

        isSourceActive(id: string): boolean {
            return this.connecting.active && this.connecting.sourceId === id;
        },

        isDropTarget(id: string): boolean {
            if (this.connecting.active && this.connecting.hoverTargetId === id) return true;
            if (this._reconnect.active && this._reconnect.hoverNodeId === id) return true;
            return false;
        },
    };
}

export type NeuraFlowInstance = ReturnType<typeof createNeuraFlow>;

if (typeof window !== 'undefined') {
    const win = window as any;
    const NK_FLOW_BOOT = (win.__NK_FLOW_BOOT__ ??= { booted: false });

    if (!NK_FLOW_BOOT.booted) {
        NK_FLOW_BOOT.booted = true;

        const register = () => {
            const Alpine = win.Alpine;
            if (!Alpine) {
                console.warn('[neurakit/flow] Alpine not found on window.');
                return;
            }
            Alpine.data('neuraFlow', createNeuraFlow);
            Alpine.data('flowEditor', createNeuraFlow);
        };

        onAlpineInit(register);
        if (win.Alpine?.version) register();
    }

    win.neuraFlow = createNeuraFlow;
}

export { createNeuraFlow as neuraFlow };

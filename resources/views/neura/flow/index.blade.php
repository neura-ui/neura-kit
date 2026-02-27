@props([
    'nodes' => [],
    'edges' => [],
    'height' => '500px',
    'minZoom' => 0.5,
    'maxZoom' => 2,
    'panOnScroll' => true,
    'panOnDrag' => true,
    'zoomOnWheelScroll' => false,
    'zoomOnPinch' => true,
    'autoCenter' => true,
    'toolbarClasses' => 'bottom left',
    'backgroundClasses' => 'dots',
    'connectable' => true,
])

@php
    $nodes = is_array($nodes) ? $nodes : [];
    $edges = is_array($edges) ? $edges : [];

    $nodes = array_map(function ($node, $index) {
        if (!isset($node['position'])) {
            $node['position'] = [
                'x' => 100 + ($index % 4) * 220,
                'y' => 100 + floor($index / 4) * 160,
            ];
        }
        return $node;
    }, $nodes, array_keys($nodes));
@endphp

<div
    data-nk-flow
    {{ $attributes->merge(['class' => 'w-full']) }}
    x-data="{
        {{-- Connection state --}}
        _connState: 'idle',
        _connSource: null,

        {{-- Drag state --}}
        _drag: null,

        _getNodeId(el) {
            const node = el.closest('.flow__node');
            if (!node) return null;
            return node.id.replace('flow__node_id-', '');
        },

        _getEditor() {
            const editorEl = this.$el.querySelector('[x-data*=flowEditor]');
            return editorEl ? Alpine.$data(editorEl) : null;
        },

        _getZoom() {
            const editor = this._getEditor();
            return editor?.zoom ?? 1;
        },

        {{-- Drag handling --}}
        onNodePointerDown(e) {
            if (e.target.closest('[data-handle]')) return;
            if (e.button !== 0) return;

            const nodeEl = e.target.closest('.flow__node');
            if (!nodeEl) return;

            const nodeId = this._getNodeId(nodeEl);
            if (!nodeId) return;

            const editor = this._getEditor();
            if (!editor) return;

            const nodeData = editor.nodes.find(n => n.id === nodeId);
            if (!nodeData) return;

            e.preventDefault();
            e.stopPropagation();

            this._drag = {
                nodeId,
                nodeData,
                startX: e.clientX,
                startY: e.clientY,
                origX: nodeData.position.x,
                origY: nodeData.position.y,
                moved: false,
            };

            nodeEl.style.zIndex = '100';
            nodeEl.style.cursor = 'grabbing';
        },

        onPointerMove(e) {
            if (!this._drag) return;

            const zoom = this._getZoom();
            const dx = (e.clientX - this._drag.startX) / zoom;
            const dy = (e.clientY - this._drag.startY) / zoom;

            if (!this._drag.moved && (Math.abs(dx) > 2 || Math.abs(dy) > 2)) {
                this._drag.moved = true;
            }

            if (this._drag.moved) {
                const nd = this._drag.nodeData;
                nd.position.x = this._drag.origX + dx;
                nd.position.y = this._drag.origY + dy;
                nd.x = nd.position.x + nd.width / 2;
                nd.y = nd.position.y + nd.height / 2;

                this._recalcEdges();
            }
        },

        onPointerUp(e) {
            if (!this._drag) return;

            const nodeEl = this.$el.querySelector('#flow__node_id-' + CSS.escape(this._drag.nodeId));
            if (nodeEl) {
                nodeEl.style.zIndex = '';
                nodeEl.style.cursor = '';
            }

            this._drag = null;
        },

        _recalcEdges() {
            const editor = this._getEditor();
            if (!editor || !editor.edges?.length) return;

            const rankdir = editor.dagreConfig?.rankdir || 'TB';
            editor.edgesWithPath = editor.edges.map(edge => {
                const src = editor.getNodeById(edge.source);
                const tgt = editor.getNodeById(edge.target);
                if (!src || !tgt) return { edge, path: '' };

                let sx, sy, tx, ty;
                if (rankdir === 'TB') {
                    sx = src.x; sy = src.y + src.height / 2;
                    tx = tgt.x; ty = tgt.y - tgt.height / 2;
                } else if (rankdir === 'BT') {
                    sx = src.x; sy = src.y - src.height / 2;
                    tx = tgt.x; ty = tgt.y + tgt.height / 2;
                } else if (rankdir === 'LR') {
                    sx = src.x + src.width / 2; sy = src.y;
                    tx = tgt.x - tgt.width / 2; ty = tgt.y;
                } else {
                    sx = src.x - src.width / 2; sy = src.y;
                    tx = tgt.x + tgt.width / 2; ty = tgt.y;
                }

                const midX = (sx + tx) / 2;
                const midY = (sy + ty) / 2;
                const path = (rankdir === 'LR' || rankdir === 'RL')
                    ? `M ${sx} ${sy} C ${midX} ${sy} ${midX} ${ty} ${tx} ${ty}`
                    : `M ${sx} ${sy} C ${sx} ${midY} ${tx} ${midY} ${tx} ${ty}`;

                return { edge, path };
            });
        },

        @if($connectable)
        {{-- Connection handling --}}
        handleClick(e) {
            if (this._drag?.moved) return;

            const handle = e.target.closest('[data-handle]');
            if (!handle) {
                if (this._connState === 'connecting') this.cancelConnection();
                return;
            }
            e.stopPropagation();

            const nodeId = this._getNodeId(handle);
            if (!nodeId) return;
            const type = handle.dataset.handle;

            if (this._connState === 'idle' && type === 'source') {
                this.startConnection(nodeId);
            } else if (this._connState === 'connecting' && type === 'target' && nodeId !== this._connSource) {
                this.completeConnection(nodeId);
            } else if (this._connState === 'connecting') {
                this.cancelConnection();
            }
        },

        startConnection(nodeId) {
            this._connSource = nodeId;
            this._connState = 'connecting';
            this.$el.classList.add('nk-flow--connecting');
            const srcNode = this.$el.querySelector('#flow__node_id-' + CSS.escape(nodeId));
            srcNode?.classList.add('nk-flow-node--source-active');
        },

        completeConnection(targetId) {
            const editor = this._getEditor();
            if (editor) {
                const sourceId = this._connSource;
                editor.enqueueTransformation(state => {
                    const exists = state.edges.some(e => e.source === sourceId && e.target === targetId);
                    if (!exists) {
                        state.edges.push(editor.createEdge({ source: sourceId, target: targetId }));
                    }
                });
            }
            this.cancelConnection();
        },

        cancelConnection() {
            this.$el.querySelector('.nk-flow-node--source-active')?.classList.remove('nk-flow-node--source-active');
            this.$el.classList.remove('nk-flow--connecting');
            this._connSource = null;
            this._connState = 'idle';
        },
        @endif
    }"
    x-init="
        $el.addEventListener('pointerdown', (e) => onNodePointerDown(e), true);
        document.addEventListener('pointermove', (e) => onPointerMove(e));
        document.addEventListener('pointerup', (e) => onPointerUp(e));
    "
    @if($connectable)
        @click="handleClick($event)"
        @keydown.escape.window="if (_connState === 'connecting') cancelConnection()"
    @endif
>
    @if(isset($nodeDefinitions))
        <div style="display: none;" aria-hidden="true">
            {{ $nodeDefinitions }}
        </div>
    @else
        <div
            x-node="{ type: 'step', deletable: true, allowBranching: true, allowChildren: true }"
            x-data="{ props: { label: 'Step', description: '' } }"
            style="display: none;"
            aria-hidden="true"
        >
            <div x-ignore>
                <div class="nk-flow-card relative">
                    @if($connectable)
                        <div data-handle="target" class="nk-flow-handle nk-flow-handle--target"></div>
                    @endif

                    <div class="rounded-lg border border-edge bg-surface shadow-sm px-4 py-3 min-w-[160px]">
                        <p class="font-medium text-fg" x-text="props.label || 'Step'"></p>
                        <p class="text-xs text-fg-muted mt-0.5" x-text="props.description || ''"></p>
                    </div>

                    @if($connectable)
                        <div data-handle="source" class="nk-flow-handle nk-flow-handle--source"></div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div
        x-data="flowEditor({
            nodes: @js($nodes),
            edges: @js($edges),
            minZoom: {{ $minZoom }},
            maxZoom: {{ $maxZoom }},
            panOnScroll: {{ $panOnScroll ? 'true' : 'false' }},
            panOnDrag: {{ $panOnDrag ? 'true' : 'false' }},
            zoomOnWheelScroll: {{ $zoomOnWheelScroll ? 'true' : 'false' }},
            zoomOnPinch: {{ $zoomOnPinch ? 'true' : 'false' }},
            autoCenter: {{ $autoCenter ? 'true' : 'false' }},
            toolbarClasses: @js($toolbarClasses),
            backgroundClasses: @js($backgroundClasses),
        })"
        style="width: 100%; height: {{ $height }}; min-height: 200px;"
    >
    </div>
</div>

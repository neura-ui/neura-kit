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
    {{ $attributes->class('w-full') }}
    @if($connectable)
        x-data="{
            _connState: 'idle',
            _connSource: null,

            _getNodeId(el) {
                const node = el.closest('.flow__node');
                if (!node) return null;
                return node.id.replace('flow__node_id-', '');
            },

            _getEditor() {
                const editorEl = this.$el.querySelector('[x-data*=flowEditor]');
                return editorEl ? Alpine.$data(editorEl) : null;
            },

            handleClick(e) {
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
            }
        }"
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

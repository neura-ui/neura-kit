@props([
    'nodes' => [],
    'edges' => [],
    'height' => '500px',
    'minZoom' => 0.25,
    'maxZoom' => 2.5,
    'panOnScroll' => true,
    'panOnDrag' => true,
    'zoomOnWheelScroll' => true,
    'zoomOnPinch' => true,
    'autoCenter' => true,
    'fitViewOnInit' => null,
    'toolbarClasses' => 'bottom left',
    'backgroundClasses' => 'dots',
    'connectable' => true,
    'direction' => 'lr',
    'snapToGrid' => true,
    'gridSize' => 16,
    'showMinimap' => true,
    'showToolbar' => true,
    'edgeType' => 'bezier',
    'defaultEdgeAnimated' => false,
    'nodesDraggable' => true,
    'nodesConnectable' => null,
    'elementsSelectable' => true,
])

@php
    use Neura\Kit\Support\PackResolver;

    $nodes = is_array($nodes) ? $nodes : [];
    $edges = is_array($edges) ? $edges : [];
    $fitViewOnInit = $fitViewOnInit ?? $autoCenter;
    $nodesConnectable = $nodesConnectable ?? $connectable;
    $flowRoundedClass = PackResolver::rounded(neura_config('flow', 'rounded'));
    $markerId = 'flow-arrow-'.str_replace('.', '', uniqid('', true));
@endphp

<div
    data-nk-flow
    {{ $attributes->class(['flow', $flowRoundedClass]) }}
    style="height: {{ $height }}; min-height: 200px;"
    x-data="neuraFlow({
        nodes: @js($nodes),
        edges: @js($edges),
        minZoom: {{ (float) $minZoom }},
        maxZoom: {{ (float) $maxZoom }},
        panOnScroll: {{ $panOnScroll ? 'true' : 'false' }},
        panOnDrag: {{ $panOnDrag ? 'true' : 'false' }},
        zoomOnWheelScroll: {{ $zoomOnWheelScroll ? 'true' : 'false' }},
        zoomOnPinch: {{ $zoomOnPinch ? 'true' : 'false' }},
        fitViewOnInit: {{ $fitViewOnInit ? 'true' : 'false' }},
        snapToGrid: {{ $snapToGrid ? 'true' : 'false' }},
        gridSize: {{ (int) $gridSize }},
        showMinimap: {{ $showMinimap ? 'true' : 'false' }},
        showToolbar: {{ $showToolbar ? 'true' : 'false' }},
        edgeType: @js($edgeType),
        defaultEdgeAnimated: {{ $defaultEdgeAnimated ? 'true' : 'false' }},
        nodesDraggable: {{ $nodesDraggable ? 'true' : 'false' }},
        nodesConnectable: {{ $nodesConnectable ? 'true' : 'false' }},
        elementsSelectable: {{ $elementsSelectable ? 'true' : 'false' }},
        direction: @js($direction),
        toolbarPosition: @js($toolbarClasses),
        background: @js($backgroundClasses),
        markerId: @js($markerId),
    })"
    :class="{ 'is-connecting': isConnecting, 'is-panning': _pan.active || _spaceDown }"
>
    <div
        class="flow-canvas"
        x-ref="canvas"
        tabindex="0"
        role="application"
        aria-label="{{ __('Flow editor') }}"
        :class="background"
        :style="backgroundStyle"
        @wheel.prevent="onWheel($event)"
        @pointerdown="onCanvasPointerDown($event)"
        @pointermove.window="onCanvasPointerMove($event)"
        @pointerup.window="onCanvasPointerUp($event)"
        @pointercancel.window="onCanvasPointerUp($event)"
        @touchstart="onTouchStart($event)"
        @touchmove="onTouchMove($event)"
        @touchend="onTouchEnd()"
        @touchcancel="onTouchEnd()"
    >
        <div class="flow-world" :style="'transform:' + transformStyle">
            {{-- Single SVG: Alpine x-for inside <svg> is unreliable --}}
            <svg
                class="flow-edges"
                width="5000"
                height="5000"
                aria-hidden="true"
                @pointerdown="onEdgesSvgPointerDown($event)"
                @click="onEdgesSvgClick($event)"
            >
                <defs>
                    {{-- Static IDs (Alpine :id breaks marker-end URLs) --}}
                    <marker
                        id="{{ $markerId }}"
                        viewBox="0 0 10 10"
                        refX="8"
                        refY="5"
                        markerWidth="10"
                        markerHeight="10"
                        markerUnits="userSpaceOnUse"
                        orient="auto"
                    >
                        <path d="M 0 1.2 L 8 5 L 0 8.8 Z" class="flow-arrow"></path>
                    </marker>
                    <marker
                        id="{{ $markerId }}-on"
                        viewBox="0 0 10 10"
                        refX="8"
                        refY="5"
                        markerWidth="10"
                        markerHeight="10"
                        markerUnits="userSpaceOnUse"
                        orient="auto"
                    >
                        <path d="M 0 1.2 L 8 5 L 0 8.8 Z" class="flow-arrow is-on"></path>
                    </marker>
                </defs>
                <g x-ref="edgeLayer"></g>
                <path
                    class="flow-preview"
                    x-show="isConnecting"
                    x-cloak
                    :d="connectionPreviewPath"
                    fill="none"
                ></path>
            </svg>

            <div class="flow-nodes">
                <template x-for="node in nodes" :key="node.id">
                    <div
                        class="flow-node"
                        :class="{ 'is-on': isNodeSelected(node.id), 'is-from': isSourceActive(node.id), 'is-drop': isDropTarget(node.id) }"
                        :data-node-id="node.id"
                        :style="nodeStyle(node)"
                        @pointerdown="onNodePointerDown($event, node.id)"
                    >
                        <button
                            type="button"
                            class="flow-port in"
                            data-handle="target"
                            data-handle-id="in"
                            title="{{ __('Input') }}"
                            x-show="nodesConnectable"
                            @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, 'in', 'target')"
                        ></button>

                        <div class="flow-card">
                            <div class="flow-icon" x-text="nodeIcon(node)"></div>
                        </div>

                        <div class="flow-meta">
                            <p class="flow-title" x-text="(node.data && node.data.label) || node.type || 'Step'"></p>
                            <p
                                class="flow-sub"
                                x-show="node.data && node.data.description"
                                x-text="node.data && node.data.description"
                            ></p>
                            <span
                                class="flow-badge"
                                x-show="node.data && node.data.status"
                                x-text="node.data && node.data.status"
                            ></span>
                        </div>

                        <template x-for="out in nodeOutputs(node)" :key="out.id">
                            <button
                                type="button"
                                class="flow-port out"
                                data-handle="source"
                                :data-handle-id="out.id"
                                :title="out.label || '{{ __('Output') }}'"
                                x-show="nodesConnectable"
                                :style="handleSourceStyle(node, out.id)"
                                @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, out.id, 'source')"
                            >
                                <span
                                    class="flow-port-plus"
                                    x-show="!hasOutgoing(node.id, out.id)"
                                    aria-hidden="true"
                                >+</span>
                                <span class="flow-port-label" x-show="out.label" x-text="out.label"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="flow-empty" x-show="!nodes.length" x-cloak>
        <strong>{{ __('Empty flow') }}</strong>
        <span>{{ __('Add nodes to start building your workflow.') }}</span>
    </div>

    <div class="flow-tools" :class="toolbarClass" x-show="showToolbar" x-cloak>
        <div class="flow-bar" role="toolbar" aria-label="{{ __('Flow controls') }}">
            <button type="button" class="flow-btn" @click="zoomIn()" title="{{ __('Zoom in') }}">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>
            <button type="button" class="flow-btn" @click="zoomOut()" title="{{ __('Zoom out') }}">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>
            <button type="button" class="flow-btn" @click="fitView()" title="{{ __('Fit view') }}">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 7V3h4M13 3h4v4M17 13v4h-4M7 17H3v-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="flow-sep" aria-hidden="true"></span>
            <button
                type="button"
                class="flow-btn"
                @click="deleteSelection()"
                title="{{ __('Delete selection') }}"
                :disabled="!selectedNodeIds.length && !selectedEdgeIds.length"
            >
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 6h10M8 6V4h4v2M7 6v9a1 1 0 001 1h4a1 1 0 001-1V6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="flow-zoom" x-text="Math.round(viewport.zoom * 100) + '%'"></span>
        </div>
    </div>

    <div class="flow-map" x-show="showMinimap && nodes.length" x-cloak aria-hidden="true">
        <div class="flow-map-canvas">
            <template x-for="mn in minimapNodes" :key="mn.id">
                <div
                    class="flow-map-node"
                    :class="{ 'is-on': mn.selected }"
                    :style="`left:${mn.x}%;top:${mn.y}%;width:${mn.w}%;height:${mn.h}%;`"
                ></div>
            </template>
            <div
                class="flow-map-view"
                :style="`left:${minimapViewport.x}%;top:${minimapViewport.y}%;width:${minimapViewport.w}%;height:${minimapViewport.h}%;`"
            ></div>
        </div>
    </div>
</div>

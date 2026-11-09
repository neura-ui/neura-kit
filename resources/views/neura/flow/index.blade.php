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
    'edgeType' => null,
    'defaultEdgeAnimated' => false,
    'nodesDraggable' => true,
    'nodesConnectable' => null,
    'elementsSelectable' => true,
    'theme' => null,
    'shape' => null,
    'variant' => null,
    'icons' => [],
    'nodeActions' => true,
    'trace' => true,
])

@php
    use Neura\Kit\Support\PackResolver;

    $nodes = is_array($nodes) ? $nodes : [];
    $edges = is_array($edges) ? $edges : [];
    $fitViewOnInit = $fitViewOnInit ?? $autoCenter;
    $nodesConnectable = $nodesConnectable ?? $connectable;
    $flowRoundedClass = PackResolver::rounded(neura_config('flow', 'rounded'));

    // The theme (Flow\Theme pack) sets the look, the connection drawing when none is given,
    // and the size of the shapes it draws differently.
    $themeName = PackResolver::flowTheme($theme);
    $themeSpec = PackResolver::flowThemes()[$themeName] ?? [];
    $edgeType = $edgeType ?? ($themeSpec['edgeType'] ?? 'bezier');
    $themeClass = $themeSpec['class'] ?? 'flow-t-'.$themeName;
    $markerId = 'flow-arrow-'.str_replace('.', '', uniqid('', true));

    // Every icon a node names (plus `icons`, for nodes added later) is rendered once by the kit
    // and copied into the nodes by the script; glyphs such as ▶ are drawn as text.
    $iconMarkup = [];
    $iconNames = collect($nodes)
        ->map(fn ($node) => is_array($node) ? data_get($node, 'data.icon') : null)
        ->merge(is_array($icons) ? $icons : [])
        ->filter(fn ($name) => is_string($name) && strlen($name) > 1 && preg_match('/^(?:(?:ps|phosphor):)?[a-z0-9]+(?:-[a-z0-9]+)*$/', $name))
        ->unique();
    foreach ($iconNames as $iconName) {
        try {
            $svg = trim(\Illuminate\Support\Facades\Blade::render('<x-neura::icon :name="$name" />', ['name' => $iconName]));
        } catch (\Throwable) {
            continue;
        }
        if (str_contains($svg, '<svg')) {
            $iconMarkup[$iconName] = $svg;
        }
    }

    $config = [
        'nodes' => $nodes,
        'edges' => $edges,
        'minZoom' => (float) $minZoom,
        'maxZoom' => (float) $maxZoom,
        'panOnScroll' => (bool) $panOnScroll,
        'panOnDrag' => (bool) $panOnDrag,
        'zoomOnWheelScroll' => (bool) $zoomOnWheelScroll,
        'zoomOnPinch' => (bool) $zoomOnPinch,
        'fitViewOnInit' => (bool) $fitViewOnInit,
        'snapToGrid' => (bool) $snapToGrid,
        'gridSize' => (int) $gridSize,
        'showMinimap' => (bool) $showMinimap,
        'showToolbar' => (bool) $showToolbar,
        'edgeType' => $edgeType,
        'defaultEdgeAnimated' => (bool) $defaultEdgeAnimated,
        'nodesDraggable' => (bool) $nodesDraggable,
        'nodesConnectable' => (bool) $nodesConnectable,
        'elementsSelectable' => (bool) $elementsSelectable,
        'direction' => $direction,
        'toolbarPosition' => $toolbarClasses,
        'background' => $backgroundClasses,
        'markerId' => $markerId,
        // Node shape and card style when a node names none, and what each looks like (packs).
        'shape' => PackResolver::flowShape($shape ?? ($themeSpec['shape'] ?? null)),
        'variant' => PackResolver::flowVariant($variant),
        'theme' => $themeName,
        'shapes' => array_replace(PackResolver::flowShapes(), $themeSpec['shapes'] ?? []),
        'variants' => PackResolver::flowVariants(),
        'nodeActions' => (bool) $nodeActions,
        // Hovering a node (or a table row) lights its pipeline and dims the rest.
        'trace' => (bool) $trace,
    ];
@endphp

<div
    data-nk-flow
    {{ $attributes->class(['flow', $themeClass, $flowRoundedClass]) }}
    style="height: {{ $height }}; min-height: 200px;"
    x-data="neuraFlow({{ \Illuminate\Support\Js::from($config) }})"
    :class="{ 'is-connecting': isConnecting, 'is-panning': _pan.active || _spaceDown, 'is-tracing': tracing }"
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
                    @foreach (['success', 'error'] as $state)
                        <marker
                            id="{{ $markerId }}-{{ $state }}"
                            viewBox="0 0 10 10"
                            refX="8"
                            refY="5"
                            markerWidth="10"
                            markerHeight="10"
                            markerUnits="userSpaceOnUse"
                            orient="auto"
                        >
                            <path d="M 0 1.2 L 8 5 L 0 8.8 Z" class="flow-arrow is-{{ $state }}"></path>
                        </marker>
                    @endforeach
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
                        :class="nodeClasses(node)"
                        :data-node-id="node.id"
                        :data-shape="shapeOf(node)"
                        :data-status="statusOf(node)"
                        :style="nodeStyle(node)"
                        @pointerdown="onNodePointerDown($event, node.id)"
                        @pointerenter="traceNode(node.id)"
                        @pointerleave="traceNode(null)"
                        @dblclick="openNode(node.id)"
                    >
                        {{-- Main input, on the left (steps, agents, cards) --}}
                        <button
                            type="button"
                            class="flow-port in"
                            data-handle="target"
                            data-handle-id="in"
                            data-channel="main"
                            title="{{ __('Input') }}"
                            x-show="nodesConnectable && portsOf(node).mainIn"
                            @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, 'in', 'target')"
                        ></button>

                        {{-- A sub-node plugs into an agent from its top --}}
                        <button
                            type="button"
                            class="flow-port out is-top"
                            data-handle="source"
                            data-handle-id="ai"
                            data-channel="ai"
                            title="{{ __('Connect to an agent') }}"
                            x-show="portsOf(node).aiOut"
                            @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, 'ai', 'source')"
                        ></button>

                        <div class="flow-card">
                            <div class="flow-icon" x-show="shapeOf(node) !== 'note'">
                                <img
                                    x-show="node.data && node.data.image"
                                    :src="(node.data && node.data.image) || ''"
                                    alt=""
                                    draggable="false"
                                >
                                <span class="flow-glyph" x-show="!(node.data && node.data.image)" x-html="iconHtml(node)"></span>
                            </div>

                            {{-- Agents and cards carry their name inside --}}
                            <div class="flow-meta" x-show="labelInside(node)">
                                <p class="flow-title" x-text="nodeLabel(node)"></p>
                                <p
                                    class="flow-sub"
                                    x-show="node.data && node.data.description"
                                    x-text="node.data && node.data.description"
                                ></p>
                                {{-- An agent's steps, shown as chips (Signal) --}}
                                <div class="flow-steps" x-show="nodeSteps(node).length">
                                    <template x-for="step in nodeSteps(node)" :key="step">
                                        <span class="flow-step" x-text="step"></span>
                                    </template>
                                </div>
                            </div>

                            {{-- A table's rows (columns, fields); hovering one traces its lineage --}}
                            <div class="flow-rows" x-show="shapeOf(node) === 'table'">
                                <template x-for="row in tableRows(node)" :key="row.id">
                                    <div
                                        class="flow-row"
                                        :class="rowClasses(node, row)"
                                        @pointerenter="traceRow(node.id, row.id)"
                                        @pointerleave="traceNode(node.id)"
                                    >
                                        <span class="flow-row-key" aria-hidden="true">
                                            <svg x-show="row.key === 'pk'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="10.5" r="3"/><path d="m7.6 8.4 5.9-5.9M11.5 4.5l1.5 1.5"/></svg>
                                            <svg x-show="row.key === 'fk'" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 9.5 9.5 6.5M7 4.5l1-1a2.8 2.8 0 0 1 4 4l-1 1M9 11.5l-1 1a2.8 2.8 0 0 1-4-4l1-1"/></svg>
                                        </span>
                                        <span class="flow-row-label" x-text="row.label"></span>
                                        <span class="flow-row-type" x-show="row.type" x-text="row.type"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Status light (Signal) --}}
                            <span class="flow-led" aria-hidden="true" x-show="shapeOf(node) !== 'note' && shapeOf(node) !== 'sub'"></span>

                            <span
                                class="flow-badge"
                                x-show="shapeOf(node) === 'card' && node.data && node.data.status"
                                :data-status="statusOf(node)"
                                x-text="node.data && node.data.status"
                            ></span>

                            <div class="flow-note" x-show="shapeOf(node) === 'note'">
                                <p class="flow-note-title" x-show="node.data && node.data.label" x-text="node.data && node.data.label"></p>
                                <p class="flow-note-text" x-text="(node.data && (node.data.text || node.data.description)) || ''"></p>
                            </div>

                            {{-- Execution state in the corner, n8n-style --}}
                            <span class="flow-mark" aria-hidden="true" x-show="statusOf(node) && shapeOf(node) !== 'card' && shapeOf(node) !== 'note'">
                                <svg class="flow-mark-success" viewBox="0 0 16 16" fill="none"><path d="M4 8.5l2.5 2.5L12 5.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <svg class="flow-mark-error" viewBox="0 0 16 16" fill="none"><path d="M8 4.5v4M8 11.2v.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <svg class="flow-mark-running" viewBox="0 0 16 16" fill="none"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <svg class="flow-mark-waiting" viewBox="0 0 16 16" fill="none"><path d="M8 4.5V8l2.2 1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </div>

                        {{-- A trigger shows where the flow starts (a tile or a card) --}}
                        <span class="flow-bolt" aria-hidden="true" x-show="isTrigger(node) && (shapeOf(node) === 'trigger' || shapeOf(node) === 'card')">
                            <svg viewBox="0 0 16 16" fill="currentColor"><path d="M9.2 1.5 3.5 9h3.8l-.9 5.5L12.5 7H8.6l.6-5.5Z"/></svg>
                        </span>

                        {{-- Tiles carry their name below --}}
                        <div class="flow-caption" x-show="labelBelow(node)">
                            <p class="flow-title" x-text="nodeLabel(node)"></p>
                            <p
                                class="flow-sub"
                                x-show="node.data && node.data.description"
                                x-text="node.data && node.data.description"
                            ></p>
                        </div>

                        <template x-for="out in mainOutputs(node)" :key="out.id">
                            <button
                                type="button"
                                class="flow-port out"
                                data-handle="source"
                                data-channel="main"
                                :data-handle-id="out.id"
                                :title="out.label || @js(__('Output'))"
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

                        {{-- A table row's own connectors, at the row's middle on either side --}}
                        <template x-for="(row, index) in tableRows(node)" :key="'row-' + row.id">
                            <span class="contents">
                                <button
                                    type="button"
                                    class="flow-port in is-row"
                                    data-handle="target"
                                    data-channel="main"
                                    :data-handle-id="row.id"
                                    :title="row.label"
                                    x-show="nodesConnectable"
                                    :style="rowPortStyle(index)"
                                    @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, row.id, 'target')"
                                ></button>
                                <button
                                    type="button"
                                    class="flow-port out is-row"
                                    data-handle="source"
                                    data-channel="main"
                                    :data-handle-id="row.id"
                                    :title="row.label"
                                    x-show="nodesConnectable"
                                    :style="rowPortStyle(index)"
                                    @pointerdown.stop.prevent="onHandlePointerDown($event, node.id, row.id, 'source')"
                                ></button>
                            </span>
                        </template>

                        {{-- An agent's sub-connectors underneath: chat model, memory, tool… --}}
                        <template x-for="(input, index) in aiInputs(node)" :key="input.id">
                            <button
                                type="button"
                                class="flow-port in is-bottom"
                                data-handle="target"
                                data-channel="ai"
                                :data-handle-id="input.id"
                                :class="{ 'is-required': input.required }"
                                :title="input.label"
                                :style="aiInputStyle(node, index)"
                                @pointerdown.stop.prevent
                            >
                                <span class="flow-port-label" x-text="input.label + (input.required ? ' *' : '')"></span>
                            </button>
                        </template>

                        {{-- Hover actions, n8n-style --}}
                        <div class="flow-actions" x-show="hasActions(node)" @pointerdown.stop>
                            <button type="button" class="flow-action" title="{{ __('Run step') }}" @click.stop="runNode(node.id)">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5 3.6v8.8a.6.6 0 0 0 .9.5l7-4.4a.6.6 0 0 0 0-1L5.9 3.1a.6.6 0 0 0-.9.5Z"/></svg>
                            </button>
                            <button type="button" class="flow-action" :title="isDisabled(node) ? @js(__('Activate')) : @js(__('Deactivate'))" @click.stop="toggleNode(node.id)">
                                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 2.5v5M4.6 4.4a5 5 0 1 0 6.8 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </button>
                            <button type="button" class="flow-action" title="{{ __('Delete') }}" @click.stop="removeNode(node.id)">
                                <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M3.5 4.5h9M6.5 4.5V3h3v1.5M5 4.5l.5 8h5l.5-8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Icons named by the nodes, rendered once by the kit and copied into the nodes --}}
    <div hidden data-flow-icons>
        @foreach ($iconMarkup as $iconName => $svg)
            <span data-flow-icon="{{ $iconName }}">{!! $svg !!}</span>
        @endforeach
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

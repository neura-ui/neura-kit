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

<div {{ $attributes->class('w-full') }}>
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
                <div class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 shadow-sm px-4 py-3 min-w-[160px]">
                    <p class="font-medium text-neutral-900 dark:text-neutral-100" x-text="props.label || 'Step'"></p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5" x-text="props.description || ''"></p>
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
@props([
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => '400px',
    'variant' => 'default',
])

@php
    $chartId = 'chart-' . uniqid();
    $data = is_array($data) ? $data : json_decode($data, true) ?? [];
    $options = is_array($options) ? $options : json_decode($options, true) ?? [];
    
    $defaultOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'top',
                'labels' => [
                    'usePointStyle' => true,
                    'padding' => 15,
                    'font' => [
                        'size' => 12,
                    ],
                ],
            ],
            'tooltip' => [
                'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                'padding' => 12,
                'titleFont' => [
                    'size' => 14,
                    'weight' => 'bold',
                ],
                'bodyFont' => [
                    'size' => 13,
                ],
                'cornerRadius' => 8,
                'displayColors' => true,
            ],
        ],
        'elements' => [
            'line' => [
                'tension' => 0.4,
                'borderWidth' => 2,
            ],
            'point' => [
                'radius' => 4,
                'hoverRadius' => 6,
                'borderWidth' => 2,
            ],
            'bar' => [
                'borderRadius' => 6,
                'borderSkipped' => false,
            ],
        ],
        'scales' => [
            'x' => [
                'grid' => [
                    'display' => false,
                ],
                'ticks' => [
                    'font' => [
                        'size' => 11,
                    ],
                ],
            ],
            'y' => [
                'grid' => [
                    'color' => 'rgba(0, 0, 0, 0.05)',
                ],
                'ticks' => [
                    'font' => [
                        'size' => 11,
                    ],
                ],
            ],
        ],
    ];
    
    $mergedOptions = array_merge_recursive($defaultOptions, $options);
    
    $variantClasses = match($variant) {
        'card' => 'bg-surface-raised backdrop-blur-xl rounded-box border border-edge p-6 shadow-sm',
        'minimal' => 'bg-transparent',
        default => 'bg-surface-raised backdrop-blur-xl rounded-box border border-edge p-6 shadow-sm',
    };
@endphp

<div
    x-data="chartComponent('{{ $chartId }}', {{ json_encode($type) }}, {{ json_encode($data) }}, {{ json_encode($mergedOptions) }})"
    class="relative {{ $variantClasses }}"
    style="height: {{ $height }};"
    {{ $attributes }}
>
    <canvas x-ref="chartCanvas" id="{{ $chartId }}"></canvas>
</div>


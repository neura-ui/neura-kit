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

    $isRadial = in_array($type, ['pie', 'doughnut', 'polarArea', 'radar']);

    $defaultOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'animation' => [
            'duration' => 600,
            'easing' => 'easeOutQuart',
        ],
        'interaction' => [
            'mode' => $isRadial ? 'nearest' : 'index',
            'intersect' => $isRadial,
        ],
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'top',
                'align' => 'start',
                'labels' => [
                    'usePointStyle' => true,
                    'pointStyle' => 'circle',
                    'padding' => 20,
                    'boxWidth' => 6,
                    'boxHeight' => 6,
                    'font' => [
                        'size' => 12,
                        'weight' => '500',
                        'family' => "Inter, system-ui, -apple-system, sans-serif",
                    ],
                ],
            ],
            'tooltip' => [
                'enabled' => true,
                'padding' => ['x' => 14, 'y' => 10],
                'cornerRadius' => 10,
                'displayColors' => true,
                'boxWidth' => 8,
                'boxHeight' => 8,
                'boxPadding' => 6,
                'usePointStyle' => true,
                'titleFont' => [
                    'size' => 13,
                    'weight' => '600',
                    'family' => "Inter, system-ui, -apple-system, sans-serif",
                ],
                'bodyFont' => [
                    'size' => 12,
                    'weight' => '400',
                    'family' => "Inter, system-ui, -apple-system, sans-serif",
                ],
                'titleMarginBottom' => 6,
                'caretSize' => 0,
                'borderWidth' => 1,
            ],
        ],
        'elements' => [
            'line' => [
                'tension' => 0.35,
                'borderWidth' => 2,
                'borderCapStyle' => 'round',
                'borderJoinStyle' => 'round',
            ],
            'point' => [
                'radius' => 0,
                'hoverRadius' => 5,
                'hoverBorderWidth' => 2,
                'hitRadius' => 20,
            ],
            'bar' => [
                'borderRadius' => 8,
                'borderSkipped' => false,
                'borderWidth' => 0,
            ],
            'arc' => [
                'borderWidth' => 2,
                'hoverOffset' => 6,
            ],
        ],
    ];

    if (!$isRadial) {
        $defaultOptions['scales'] = [
            'x' => [
                'border' => ['display' => false],
                'grid' => ['display' => false],
                'ticks' => [
                    'font' => [
                        'size' => 11,
                        'weight' => '500',
                        'family' => "Inter, system-ui, -apple-system, sans-serif",
                    ],
                    'padding' => 8,
                    'maxRotation' => 0,
                ],
            ],
            'y' => [
                'border' => ['display' => false, 'dash' => [4, 4]],
                'grid' => ['drawTicks' => false],
                'ticks' => [
                    'font' => [
                        'size' => 11,
                        'weight' => '500',
                        'family' => "Inter, system-ui, -apple-system, sans-serif",
                    ],
                    'padding' => 12,
                ],
            ],
        ];
    }

    $mergedOptions = array_replace_recursive($defaultOptions, $options);

    $variantClasses = match($variant) {
        'card' => 'rounded-xl border border-black/[0.06] dark:border-white/[0.08] bg-surface p-5 ring-1 ring-black/[0.02] dark:ring-white/[0.02] shadow-[0_1px_3px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.02)] dark:shadow-[0_1px_3px_rgba(0,0,0,0.3),0_1px_2px_rgba(0,0,0,0.15)]',
        'minimal' => 'bg-transparent',
        default => 'rounded-xl border border-black/[0.06] dark:border-white/[0.08] bg-surface p-5 ring-1 ring-black/[0.02] dark:ring-white/[0.02] shadow-[0_1px_3px_rgba(0,0,0,0.04),0_1px_2px_rgba(0,0,0,0.02)] dark:shadow-[0_1px_3px_rgba(0,0,0,0.3),0_1px_2px_rgba(0,0,0,0.15)]',
    };
@endphp

<div
    data-nk-chart
    x-data="chartComponent('{{ $chartId }}', {{ json_encode($type) }}, {{ json_encode($data) }}, {{ json_encode($mergedOptions) }})"
    class="relative {{ $variantClasses }}"
    style="height: {{ $height }};"
    {{ $attributes }}
>
    <canvas x-ref="chartCanvas" id="{{ $chartId }}"></canvas>
</div>


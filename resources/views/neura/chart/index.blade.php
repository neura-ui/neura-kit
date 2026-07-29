@props([
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => '400px',
    'variant' => 'default',
])

@php
    use Neura\Kit\Support\PackResolver;

    $chartId = 'chart-' . uniqid();
    $data = is_array($data) ? $data : json_decode($data, true) ?? [];
    $options = is_array($options) ? $options : json_decode($options, true) ?? [];

    $isRadial = in_array($type, ['pie', 'doughnut'], true);

    $defaultOptions = [
        'showLegend' => true,
        'showGrid' => ! $isRadial,
        'showTooltip' => true,
        'plugins' => [
            'legend' => ['display' => true],
            'tooltip' => true,
        ],
        'scales' => [
            'y' => ['grid' => ['display' => ! $isRadial]],
        ],
    ];

    $mergedOptions = array_replace_recursive($defaultOptions, $options);

    $chartRoundedClass = PackResolver::rounded(neura_config('chart', 'rounded'));
    $chartShadowClass = PackResolver::shadow(neura_config('chart', 'shadow'));
    $chartContainerClass = "{$chartRoundedClass} border border-black/[0.06] dark:border-white/[0.08] bg-surface p-5 ring-1 ring-black/[0.02] dark:ring-white/[0.02] {$chartShadowClass}";

    $variantClasses = match ($variant) {
        'card' => $chartContainerClass,
        'minimal' => 'bg-transparent',
        default => $chartContainerClass,
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

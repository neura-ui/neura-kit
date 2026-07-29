@props([
    'data' => [],
    'width' => 120,
    'height' => 32,
    'color' => 'primary',
    'fill' => true,
    'strokeWidth' => 1.75,
    'curve' => 'smooth',
    'showDots' => false,
    'dotRadius' => 2,
    'min' => null,
    'max' => null,
])

@php
    $values = collect(is_array($data) ? $data : [])
        ->map(fn ($v) => is_numeric($v) ? (float) $v : null)
        ->filter(fn ($v) => $v !== null)
        ->values()
        ->all();

    $count = count($values);
    $w = max(1, (float) $width);
    $h = max(1, (float) $height);
    $pad = max((float) $strokeWidth, (float) $dotRadius) + 1;
    $innerW = max(1, $w - ($pad * 2));
    $innerH = max(1, $h - ($pad * 2));

    $dataMin = $min !== null ? (float) $min : ($count ? min($values) : 0);
    $dataMax = $max !== null ? (float) $max : ($count ? max($values) : 1);
    if ($dataMax === $dataMin) {
        $dataMax = $dataMin + 1;
    }

    $points = [];
    foreach ($values as $i => $value) {
        $x = $pad + ($count === 1 ? $innerW / 2 : ($i / ($count - 1)) * $innerW);
        $y = $pad + $innerH - (($value - $dataMin) / ($dataMax - $dataMin)) * $innerH;
        $points[] = [$x, $y];
    }

    $linePath = '';
    $areaPath = '';

    if ($count === 1) {
        [$x, $y] = $points[0];
        $linePath = sprintf('M %.2f %.2f', $x, $y);
        $areaPath = sprintf(
            'M %.2f %.2f L %.2f %.2f L %.2f %.2f Z',
            $x,
            $h - $pad,
            $x,
            $y,
            $x,
            $h - $pad
        );
    } elseif ($count > 1) {
        if ($curve === 'smooth') {
            $linePath = sprintf('M %.2f %.2f', $points[0][0], $points[0][1]);
            for ($i = 0; $i < $count - 1; $i++) {
                [$x0, $y0] = $points[$i];
                [$x1, $y1] = $points[$i + 1];
                $cx = ($x0 + $x1) / 2;
                $linePath .= sprintf(' C %.2f %.2f, %.2f %.2f, %.2f %.2f', $cx, $y0, $cx, $y1, $x1, $y1);
            }
        } else {
            $linePath = 'M '.implode(' L ', array_map(
                fn ($p) => sprintf('%.2f %.2f', $p[0], $p[1]),
                $points
            ));
        }

        $first = $points[0];
        $last = $points[$count - 1];
        $areaPath = $linePath
            .sprintf(' L %.2f %.2f L %.2f %.2f Z', $last[0], $h - $pad, $first[0], $h - $pad);
    }

    $strokeClass = match ($color) {
        'primary' => 'stroke-primary-500',
        'secondary' => 'stroke-neutral-500',
        'success' => 'stroke-green-500',
        'danger' => 'stroke-red-500',
        'warning' => 'stroke-yellow-500',
        'info' => 'stroke-blue-500',
        'fg' => 'stroke-fg',
        default => 'stroke-primary-500',
    };

    $fillClass = match ($color) {
        'primary' => 'fill-primary-500/15 dark:fill-primary-400/20',
        'secondary' => 'fill-neutral-500/15 dark:fill-neutral-400/20',
        'success' => 'fill-green-500/15 dark:fill-green-400/20',
        'danger' => 'fill-red-500/15 dark:fill-red-400/20',
        'warning' => 'fill-yellow-500/15 dark:fill-yellow-400/20',
        'info' => 'fill-blue-500/15 dark:fill-blue-400/20',
        'fg' => 'fill-fg/10',
        default => 'fill-primary-500/15 dark:fill-primary-400/20',
    };

    $dotClass = match ($color) {
        'primary' => 'fill-primary-500',
        'secondary' => 'fill-neutral-500',
        'success' => 'fill-green-500',
        'danger' => 'fill-red-500',
        'warning' => 'fill-yellow-500',
        'info' => 'fill-blue-500',
        'fg' => 'fill-fg',
        default => 'fill-primary-500',
    };

    $summary = $count
        ? __('Sparkline with :count values from :min to :max', [
            'count' => $count,
            'min' => $dataMin,
            'max' => $dataMax,
        ])
        : __('Empty sparkline');
@endphp

<svg
    data-nk-sparkline
    viewBox="0 0 {{ $w }} {{ $h }}"
    width="{{ $w }}"
    height="{{ $h }}"
    role="img"
    aria-label="{{ $summary }}"
    {{ $attributes->merge(['class' => 'inline-block overflow-visible']) }}
>
    @if ($count && $fill && $areaPath !== '')
        <path d="{{ $areaPath }}" class="{{ $fillClass }} stroke-none" />
    @endif

    @if ($count && $linePath !== '')
        <path
            d="{{ $linePath }}"
            fill="none"
            class="{{ $strokeClass }}"
            stroke-width="{{ $strokeWidth }}"
            stroke-linecap="round"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
        />
    @endif

    @if ($count && $showDots)
        @foreach ($points as [$x, $y])
            <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $dotRadius }}" class="{{ $dotClass }} stroke-none" />
        @endforeach
    @endif
</svg>

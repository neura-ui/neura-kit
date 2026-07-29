@props([
    'data' => [],
    'height' => '8rem',
    'gap' => 'sm',
    'color' => 'primary',
    'rounded' => 'sm',
    'showLabels' => false,
    'showValues' => false,
    'min' => 0,
    'max' => null,
    'variant' => 'solid',
])

@php
    $items = collect(is_array($data) ? $data : [])
        ->map(function ($item) {
            if (is_array($item)) {
                return [
                    'value' => (float) ($item['value'] ?? $item[0] ?? 0),
                    'label' => $item['label'] ?? $item[1] ?? null,
                    'color' => $item['color'] ?? null,
                ];
            }

            return [
                'value' => is_numeric($item) ? (float) $item : 0,
                'label' => null,
                'color' => null,
            ];
        })
        ->values()
        ->all();

    $values = array_column($items, 'value');
    $dataMax = $max !== null ? (float) $max : (count($values) ? max(max($values), 1) : 1);
    $dataMin = (float) $min;
    $range = $dataMax - $dataMin;
    if ($range <= 0) {
        $range = 1;
    }

    $gapClass = match ($gap) {
        'none' => 'gap-0',
        'xs' => 'gap-0.5',
        'sm' => 'gap-1.5',
        'md' => 'gap-2.5',
        'lg' => 'gap-4',
        default => 'gap-1.5',
    };

    $roundedClass = match ($rounded) {
        'none' => 'rounded-none',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'full' => 'rounded-full',
        default => 'rounded-sm',
    };

    $defaultBarClass = match ($color) {
        'primary' => $variant === 'soft'
            ? 'bg-primary-500/25 dark:bg-primary-400/35'
            : 'bg-primary-500',
        'secondary' => $variant === 'soft'
            ? 'bg-neutral-500/25 dark:bg-neutral-400/35'
            : 'bg-neutral-500',
        'success' => $variant === 'soft' ? 'bg-green-500/25 dark:bg-green-400/35' : 'bg-green-500',
        'danger' => $variant === 'soft' ? 'bg-red-500/25 dark:bg-red-400/35' : 'bg-red-500',
        'warning' => $variant === 'soft' ? 'bg-yellow-500/25 dark:bg-yellow-400/35' : 'bg-yellow-500',
        'info' => $variant === 'soft' ? 'bg-blue-500/25 dark:bg-blue-400/35' : 'bg-blue-500',
        'fg' => $variant === 'soft' ? 'bg-fg/25 dark:bg-fg/35' : 'bg-fg',
        default => $variant === 'soft'
            ? 'bg-primary-500/25 dark:bg-primary-400/35'
            : 'bg-primary-500',
    };

    $colorOverride = [
        'primary' => $variant === 'soft' ? 'bg-primary-500/25 dark:bg-primary-400/35' : 'bg-primary-500',
        'secondary' => $variant === 'soft' ? 'bg-neutral-500/25 dark:bg-neutral-400/35' : 'bg-neutral-500',
        'success' => $variant === 'soft' ? 'bg-green-500/25 dark:bg-green-400/35' : 'bg-green-500',
        'danger' => $variant === 'soft' ? 'bg-red-500/25 dark:bg-red-400/35' : 'bg-red-500',
        'warning' => $variant === 'soft' ? 'bg-yellow-500/25 dark:bg-yellow-400/35' : 'bg-yellow-500',
        'info' => $variant === 'soft' ? 'bg-blue-500/25 dark:bg-blue-400/35' : 'bg-blue-500',
        'fg' => $variant === 'soft' ? 'bg-fg/25 dark:bg-fg/35' : 'bg-fg',
    ];

    $summary = count($items)
        ? __('Bar chart with :count values', ['count' => count($items)])
        : __('Empty bar chart');
@endphp

<div
    data-nk-bars
    role="img"
    aria-label="{{ $summary }}"
    {{ $attributes->merge(['class' => "flex w-full items-end {$gapClass}"]) }}
    style="height: {{ $height }};"
>
    @forelse ($items as $item)
        @php
            $pct = max(0, min(100, (($item['value'] - $dataMin) / $range) * 100));
            $barClass = ($item['color'] && isset($colorOverride[$item['color']]))
                ? $colorOverride[$item['color']]
                : $defaultBarClass;
        @endphp
        <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1.5 h-full">
            @if ($showValues)
                <span class="font-mono text-[10px] tabular-nums text-fg-muted leading-none">
                    {{ is_float($item['value']) && floor($item['value']) != $item['value']
                        ? number_format($item['value'], 1)
                        : (int) $item['value'] }}
                </span>
            @endif

            <div class="relative flex w-full flex-1 items-end">
                <span
                    class="block w-full {{ $roundedClass }} {{ $barClass }} transition-[height] duration-300 ease-out"
                    style="height: {{ $pct }}%"
                    title="{{ $item['label'] ? e($item['label']).': ' : '' }}{{ $item['value'] }}"
                ></span>
            </div>

            @if ($showLabels && $item['label'])
                <span class="w-full truncate text-center text-[10px] font-medium text-fg-muted leading-none">
                    {{ $item['label'] }}
                </span>
            @endif
        </div>
    @empty
        <div class="flex h-full w-full items-center justify-center text-xs text-fg-muted">
            {{ __('No data') }}
        </div>
    @endforelse
</div>

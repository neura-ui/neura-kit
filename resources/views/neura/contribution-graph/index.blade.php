@props([
    'data' => [],
    'from' => null,
    'to' => null,
    'color' => null,
    'size' => null,
    'showLegend' => null,
    'showMonths' => null,
    'showWeekdays' => null,
    'showTotal' => null,
    'heading' => null,
    'thresholds' => null,
    'rounded' => null,
])

@php
    use Illuminate\Support\Arr;
    use Neura\Kit\Support\ContributionGraph\ContributionGraphBuilder;
    use Neura\Kit\Support\PackResolver;

    $color = $color ?: (neura_config('contribution-graph', 'color') ?: 'green');
    $size = $size ?: (neura_config('contribution-graph', 'size') ?: 'md');
    $thresholds = is_array($thresholds) && $thresholds !== []
        ? array_values($thresholds)
        : (neura_config('contribution-graph', 'thresholds') ?: [1, 3, 6, 10]);

    $showLegend ??= (bool) (neura_config('contribution-graph', 'showLegend') ?? true);
    $showMonths ??= (bool) (neura_config('contribution-graph', 'showMonths') ?? true);
    $showWeekdays ??= (bool) (neura_config('contribution-graph', 'showWeekdays') ?? true);
    $showTotal ??= (bool) (neura_config('contribution-graph', 'showTotal') ?? true);

    $graph = ContributionGraphBuilder::build(
        data: is_array($data) ? $data : [],
        from: $from,
        to: $to,
        thresholds: $thresholds,
    );

    $cell = match ($size) {
        'sm' => 'size-2',
        'lg' => 'size-3.5',
        default => 'size-2.5',
    };

    $gap = match ($size) {
        'sm' => 'gap-0.5',
        'lg' => 'gap-1',
        default => 'gap-[3px]',
    };

    $radiusToken = $rounded ?: (neura_config('contribution-graph', 'rounded') ?: 'sm');
    $radius = PackResolver::rounded($radiusToken);
    $cellRadius = match (true) {
        str_contains($radius, 'rounded-none') => 'rounded-none',
        str_contains($radius, 'rounded-full') => 'rounded-sm',
        default => 'rounded-[2px]',
    };

    $levels = match ($color) {
        'primary' => [
            0 => 'bg-neutral-200 dark:bg-white/[0.06]',
            1 => 'bg-primary-900/35 dark:bg-primary-950',
            2 => 'bg-primary-700/70 dark:bg-primary-800',
            3 => 'bg-primary-500',
            4 => 'bg-primary-400',
        ],
        'emerald' => [
            0 => 'bg-neutral-200 dark:bg-white/[0.06]',
            1 => 'bg-emerald-900/40 dark:bg-emerald-950',
            2 => 'bg-emerald-700/80 dark:bg-emerald-800',
            3 => 'bg-emerald-500',
            4 => 'bg-emerald-400',
        ],
        default => [ // green — GitHub-like
            0 => 'bg-neutral-200 dark:bg-white/[0.06]',
            1 => 'bg-green-900/45 dark:bg-green-950',
            2 => 'bg-green-700/80 dark:bg-green-800',
            3 => 'bg-green-500',
            4 => 'bg-green-400',
        ],
    };

    $outOfRange = 'bg-transparent';

    $weekdayLabels = [
        0 => '',
        1 => neura_trans('weekdayMon'),
        2 => '',
        3 => neura_trans('weekdayWed'),
        4 => '',
        5 => neura_trans('weekdayFri'),
        6 => '',
    ];

    $totalLabel = $heading ?? neura_trans('contributionsInLastYear', [
        'count' => number_format($graph['total']),
    ]);

    $shellClasses = Arr::toCssClasses([
        'nk-contribution-graph w-full min-w-0',
    ]);
@endphp

<div
    data-slot="contribution-graph"
    {{ $attributes->class([$shellClasses]) }}
>
    @if ($showTotal)
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <neura::text size="sm" class="text-fg">
                {{ $totalLabel }}
            </neura::text>
            {{ $actions ?? '' }}
        </div>
    @endif

    <div class="overflow-x-auto overscroll-x-contain pb-1">
        <div class="inline-flex min-w-max flex-col {{ $gap }}">
            @if ($showMonths)
                <div class="flex {{ $gap }}">
                    @if ($showWeekdays)
                        <div class="w-7 shrink-0" aria-hidden="true"></div>
                    @endif
                    <div class="flex {{ $gap }} font-mono text-[10px] leading-none text-fg-muted">
                        @foreach ($graph['monthLabels'] as $label)
                            <div class="{{ $cell }} shrink-0 overflow-visible whitespace-nowrap">
                                {{ $label }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex {{ $gap }}">
                @if ($showWeekdays)
                    <div
                        class="flex w-7 shrink-0 flex-col justify-between py-px font-mono text-[10px] leading-none text-fg-muted"
                        aria-hidden="true"
                    >
                        @foreach ($weekdayLabels as $label)
                            <span class="{{ $cell }} flex items-center">{{ $label }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="flex {{ $gap }}" role="grid" aria-label="{{ $totalLabel }}">
                    @foreach ($graph['weeks'] as $week)
                        <div class="flex flex-col {{ $gap }}" role="row">
                            @foreach ($week as $day)
                                @php
                                    $levelClass = $day['level'] < 0
                                        ? $outOfRange
                                        : ($levels[$day['level']] ?? $levels[0]);
                                    $title = $day['inRange']
                                        ? neura_trans('contributionsOnDate', [
                                            'count' => number_format($day['count']),
                                            'date' => \Carbon\Carbon::parse($day['date'])->format('M j, Y'),
                                        ])
                                        : null;
                                @endphp
                                <div
                                    role="gridcell"
                                    class="{{ $cell }} {{ $cellRadius }} {{ $levelClass }}"
                                    @if ($title) title="{{ $title }}" @endif
                                    @if ($day['inRange'])
                                        data-date="{{ $day['date'] }}"
                                        data-count="{{ $day['count'] }}"
                                        data-level="{{ $day['level'] }}"
                                    @endif
                                ></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if ($showLegend)
        <div class="mt-3 flex items-center justify-end gap-1.5 font-mono text-[10px] text-fg-muted">
            <span>{{ neura_trans('less') }}</span>
            @foreach ([0, 1, 2, 3, 4] as $level)
                <span class="{{ $cell }} {{ $cellRadius }} {{ $levels[$level] }}" aria-hidden="true"></span>
            @endforeach
            <span>{{ neura_trans('more') }}</span>
        </div>
    @endif
</div>

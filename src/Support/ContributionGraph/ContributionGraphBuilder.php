<?php

namespace Neura\Kit\Support\ContributionGraph;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Builds a GitHub-style contribution calendar from a date => count map.
 */
class ContributionGraphBuilder
{
    /**
     * @param  array<string|int, int|array{date?: string, count?: int}>  $data
     * @param  list<int>  $thresholds  Count floors for levels 1–4
     * @return array{
     *     total: int,
     *     from: string,
     *     to: string,
     *     weeks: list<list<array{date: string, count: int, level: int, inRange: bool}>>,
     *     monthLabels: list<string>,
     * }
     */
    public static function build(
        array $data = [],
        ?string $from = null,
        ?string $to = null,
        array $thresholds = [1, 3, 6, 10],
    ): array {
        $counts = self::normalize($data);

        $end = $to ? Carbon::parse($to)->startOfDay() : Carbon::today()->startOfDay();
        $start = $from
            ? Carbon::parse($from)->startOfDay()
            : $end->copy()->subYear()->addDay();

        $rangeStart = $start->copy();
        $rangeEnd = $end->copy();

        // Pad to full weeks (Sunday → Saturday), matching GitHub's calendar.
        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SATURDAY);

        $weeks = [];
        $week = [];
        $total = 0;

        foreach (CarbonPeriod::create($gridStart, $gridEnd) as $day) {
            /** @var Carbon $day */
            $key = $day->toDateString();
            $inRange = $day->betweenIncluded($rangeStart, $rangeEnd);
            $count = $inRange ? (int) ($counts[$key] ?? 0) : 0;

            if ($inRange) {
                $total += $count;
            }

            $week[] = [
                'date' => $key,
                'count' => $count,
                'level' => $inRange ? self::level($count, $thresholds) : -1,
                'inRange' => $inRange,
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return [
            'total' => $total,
            'from' => $rangeStart->toDateString(),
            'to' => $rangeEnd->toDateString(),
            'weeks' => $weeks,
            'monthLabels' => self::monthLabels($weeks),
        ];
    }

    /**
     * @param  array<string|int, int|array{date?: string, count?: int}>  $data
     * @return array<string, int>
     */
    public static function normalize(array $data): array
    {
        $counts = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $date = (string) ($value['date'] ?? '');
                if ($date !== '') {
                    $counts[Carbon::parse($date)->toDateString()] = (int) ($value['count'] ?? 0);
                }

                continue;
            }

            if (is_string($key) && $key !== '') {
                $counts[Carbon::parse($key)->toDateString()] = (int) $value;
            }
        }

        return $counts;
    }

    /**
     * @param  list<int>  $thresholds
     */
    public static function level(int $count, array $thresholds): int
    {
        if ($count <= 0) {
            return 0;
        }

        $level = 1;
        foreach (array_values($thresholds) as $index => $floor) {
            if ($count >= (int) $floor) {
                $level = $index + 1;
            }
        }

        return min(4, max(1, $level));
    }

    /**
     * One abbreviated month label per week column (empty when unchanged).
     *
     * @param  list<list<array{date: string, count: int, level: int, inRange: bool}>>  $weeks
     * @return list<string>
     */
    private static function monthLabels(array $weeks): array
    {
        $labels = [];
        $lastMonth = null;

        foreach ($weeks as $week) {
            $firstOfMonth = null;
            $firstInRange = null;

            foreach ($week as $day) {
                if (! $day['inRange']) {
                    continue;
                }

                $carbon = Carbon::parse($day['date']);
                $firstInRange ??= $carbon;

                if ($carbon->day === 1) {
                    $firstOfMonth = $carbon;
                    break;
                }
            }

            $anchor = $firstOfMonth ?? $firstInRange;

            if ($anchor === null) {
                $labels[] = '';

                continue;
            }

            $monthKey = $anchor->format('Y-m');

            if ($monthKey === $lastMonth) {
                $labels[] = '';

                continue;
            }

            // Show when this week contains the 1st, or when no month has been shown yet.
            if ($firstOfMonth !== null || $lastMonth === null) {
                $labels[] = $anchor->format('M');
                $lastMonth = $monthKey;

                continue;
            }

            // Month rolled over mid-week without a day-1 cell in this column.
            if ($firstInRange !== null && $firstInRange->format('Y-m') !== $lastMonth) {
                $labels[] = $firstInRange->format('M');
                $lastMonth = $firstInRange->format('Y-m');

                continue;
            }

            $labels[] = '';
        }

        return $labels;
    }
}

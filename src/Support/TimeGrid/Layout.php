<?php

namespace Neura\Kit\Support\TimeGrid;

use Carbon\Carbon;
use IntlDateFormatter;
use Neura\Kit\Support\CssColor;

/**
 * The layout of a time grid, worked out once on the server: the columns in the order they
 * are drawn (weekdays from the locale's first day, or a free list), the cells of one
 * column in minutes, and which way round the grid runs. The script reads it as it is.
 */
final class Layout
{
    public const WEEK = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public const STEPS = [15, 30, 60];

    /** Past this many cells per column, an automatic grid runs its hours downwards. */
    private const ROW_LIMIT = 24;

    /**
     * @return array{columns: list<array{key: string, label: string, name: string}>, weekdays: bool, start: int, end: int, step: int, slots: list<int>, orientation: string, signature: string}
     */
    public static function make(?array $days, ?string $firstDay, string|int|null $start, string|int|null $end, mixed $step, ?string $orientation, string $locale): array
    {
        $step = in_array((int) $step, self::STEPS, true) ? (int) $step : 60;

        $from = self::minutes($start, 0);
        $from = min($from - $from % $step, 1440 - $step);
        $to = self::minutes($end, 1440);
        $to = min($to % $step ? $to + $step - $to % $step : $to, 1440);
        if ($to <= $from) {
            $to = $from + $step;
        }

        $slots = range($from, $to - $step, $step);
        [$columns, $weekdays] = self::columns($days, $firstDay, $locale);

        $orientation = in_array($orientation, ['rows', 'columns'], true)
            ? $orientation
            : (count($slots) > self::ROW_LIMIT ? 'columns' : 'rows');

        return [
            'columns' => $columns,
            'weekdays' => $weekdays,
            'start' => $from,
            'end' => $to,
            'step' => $step,
            'slots' => $slots,
            'orientation' => $orientation,
            'signature' => md5(json_encode([$columns, $slots, $orientation])),
        ];
    }

    /** Minutes from midnight out of 8, '08:00' or '08:00:00'; 24:00 is the end of the day. */
    public static function minutes(string|int|null $time, int $fallback): int
    {
        if (is_int($time) || (is_string($time) && ctype_digit($time))) {
            $minutes = (int) $time * 60;
        } elseif (is_string($time) && preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $match)) {
            $minutes = (int) $match[1] * 60 + (int) $match[2];
        } else {
            $minutes = $fallback;
        }

        return max(0, min(1440, $minutes));
    }

    /** 12 or 24 as asked, otherwise the locale's own clock. */
    public static function hourCycle(mixed $hourCycle, string $locale): int
    {
        if (in_array((int) $hourCycle, [12, 24], true)) {
            return (int) $hourCycle;
        }

        if (class_exists(IntlDateFormatter::class)) {
            $pattern = (new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT))->getPattern();

            return str_contains((string) $pattern, 'a') ? 12 : 24;
        }

        return str_starts_with(strtolower($locale), 'en') ? 12 : 24;
    }

    /** '09' or '9a' over a narrow column, '09:00' or '9 AM' everywhere else. */
    public static function hourLabel(int $minutes, int $cycle, bool $compact): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($cycle === 24) {
            return $compact ? self::pad($hours) : self::pad($hours).':'.self::pad($rest);
        }

        $clock = ($hours % 12 ?: 12).($rest ? ':'.self::pad($rest) : '');
        $morning = $hours % 24 < 12;

        return $compact
            ? $clock.($morning ? 'a' : 'p')
            : $clock.' '.($morning ? 'AM' : 'PM');
    }

    /** A design-system colour name as its CSS variable; anything else is kept as given. */
    public static function cssColor(string $color): string
    {
        return CssColor::resolve($color);
    }

    /**
     * Weekdays when every key is one (a list of keys, or keys with labels of their own),
     * a free list of columns otherwise.
     *
     * @return array{0: list<array{key: string, label: string, name: string}>, 1: bool}
     */
    private static function columns(?array $days, ?string $firstDay, string $locale): array
    {
        $days ??= self::WEEK;
        $isList = array_is_list($days);
        $keys = array_map('strval', $isList ? $days : array_keys($days));

        if (array_diff($keys, self::WEEK) !== []) {
            $columns = [];
            foreach ($days as $key => $label) {
                $key = (string) ($isList ? $label : $key);
                $columns[] = ['key' => $key, 'label' => (string) $label, 'name' => (string) $label];
            }

            return [$columns, false];
        }

        $first = in_array($firstDay, self::WEEK, true) ? $firstDay : self::localeFirstDay($locale);
        $offset = array_search($first, self::WEEK, true);
        $week = [...array_slice(self::WEEK, $offset), ...array_slice(self::WEEK, 0, $offset)];

        $columns = [];
        foreach ($week as $key) {
            if (! in_array($key, $keys, true)) {
                continue;
            }

            // 2024-01-01 was a Monday.
            $date = Carbon::create(2024, 1, 1)->addDays(array_search($key, self::WEEK, true))->locale($locale);
            $label = $isList ? $date->isoFormat('ddd') : (string) $days[$key];

            $columns[] = [
                'key' => $key,
                'label' => $label,
                'name' => $isList ? $date->isoFormat('dddd') : $label,
            ];
        }

        return [$columns, true];
    }

    /** ICU's week (Sunday in en, Monday in en_GB and fr), Carbon's when intl is missing. */
    public static function localeFirstDay(string $locale): string
    {
        $fromSunday = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

        if (class_exists(\IntlCalendar::class)) {
            $calendar = \IntlCalendar::createInstance(null, $locale);

            if ($calendar !== null) {
                // ICU counts from 1 = Sunday.
                return $fromSunday[($calendar->getFirstDayOfWeek() - 1) % 7];
            }
        }

        return $fromSunday[(int) Carbon::now()->locale($locale)->getTranslationMessage('first_day_of_week') % 7];
    }

    private static function pad(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }
}

<?php

namespace Neura\Kit\Support\Map;

/**
 * Plain GeoJSON geometry helpers for boundaries: Polygon and MultiPolygon in [lng, lat].
 */
final class Geometry
{
    /**
     * The polygons of a geometry as a list of polygons (each a list of rings), whatever its type.
     *
     * @return list<list<list<array{0: float, 1: float}>>>
     */
    public static function polygons(?array $geometry): array
    {
        return match ($geometry['type'] ?? null) {
            'Polygon' => [$geometry['coordinates']],
            'MultiPolygon' => $geometry['coordinates'],
            'GeometryCollection' => array_merge(...array_map(
                fn ($part) => self::polygons($part),
                $geometry['geometries'] ?? [[]],
            )),
            default => [],
        };
    }

    /**
     * Coordinates rounded to `precision` decimals (4 is about 11 m), repeated points dropped
     * and rings that collapse removed: the shape drawn at map scale, a fraction of the bytes.
     */
    public static function lighten(?array $geometry, int $precision): ?array
    {
        $polygons = [];

        foreach (self::polygons($geometry) as $polygon) {
            $rings = [];

            foreach ($polygon as $index => $ring) {
                $points = [];
                $last = null;

                foreach ($ring as $point) {
                    $next = [round((float) $point[0], $precision), round((float) $point[1], $precision)];

                    if ($next !== $last) {
                        $points[] = $next;
                        $last = $next;
                    }
                }

                if (count($points) >= 4) {
                    $rings[] = $points;
                } elseif ($index === 0) {
                    // The outer ring collapsed: so does the polygon.
                    continue 2;
                }
            }

            if ($rings !== []) {
                $polygons[] = $rings;
            }
        }

        return match (count($polygons)) {
            0 => null,
            1 => ['type' => 'Polygon', 'coordinates' => $polygons[0]],
            default => ['type' => 'MultiPolygon', 'coordinates' => $polygons],
        };
    }

    /**
     * [west, south, east, north] of a geometry, or null when it has no point.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    public static function bounds(?array $geometry): ?array
    {
        $west = $south = INF;
        $east = $north = -INF;

        foreach (self::polygons($geometry) as $polygon) {
            foreach ($polygon[0] ?? [] as [$lng, $lat]) {
                $west = min($west, $lng);
                $east = max($east, $lng);
                $south = min($south, $lat);
                $north = max($north, $lat);
            }
        }

        return $west === INF ? null : [$west, $south, $east, $north];
    }

    /**
     * A point inside the shape for its label: on the largest polygon, the middle of the widest
     * inside stretch of the horizontal line through its centre. Unlike the centre of the box,
     * it never falls outside a crescent or an L.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function labelPoint(?array $geometry): ?array
    {
        $best = null;
        $bestArea = -1.0;

        foreach (self::polygons($geometry) as $polygon) {
            $box = self::ringBounds($polygon[0] ?? []);

            if ($box === null) {
                continue;
            }

            $area = ($box[2] - $box[0]) * ($box[3] - $box[1]);

            if ($area > $bestArea) {
                $best = [$polygon, $box];
                $bestArea = $area;
            }
        }

        if ($best === null) {
            return null;
        }

        [$polygon, $box] = $best;
        $lat = ($box[1] + $box[3]) / 2;
        $crossings = [];

        foreach ($polygon as $ring) {
            $count = count($ring);

            for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                [$x1, $y1] = $ring[$i];
                [$x2, $y2] = $ring[$j];

                if (($y1 > $lat) !== ($y2 > $lat)) {
                    $crossings[] = $x1 + ($lat - $y1) * ($x2 - $x1) / ($y2 - $y1);
                }
            }
        }

        sort($crossings);
        $widest = null;

        for ($i = 0; $i + 1 < count($crossings); $i += 2) {
            $span = $crossings[$i + 1] - $crossings[$i];

            if ($widest === null || $span > $widest[1] - $widest[0]) {
                $widest = [$crossings[$i], $crossings[$i + 1]];
            }
        }

        $lng = $widest !== null ? ($widest[0] + $widest[1]) / 2 : ($box[0] + $box[2]) / 2;

        return [round($lng, 5), round($lat, 5)];
    }

    /** Whether a point falls inside a geometry (holes excluded), by the even-odd rule. */
    public static function contains(?array $geometry, float $lng, float $lat): bool
    {
        foreach (self::polygons($geometry) as $polygon) {
            $inside = false;

            foreach ($polygon as $ring) {
                $count = count($ring);

                for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                    [$x1, $y1] = $ring[$i];
                    [$x2, $y2] = $ring[$j];

                    if (($y1 > $lat) !== ($y2 > $lat) && $lng < $x1 + ($lat - $y1) * ($x2 - $x1) / ($y2 - $y1)) {
                        $inside = ! $inside;
                    }
                }
            }

            if ($inside) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $ring
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    private static function ringBounds(array $ring): ?array
    {
        if ($ring === []) {
            return null;
        }

        $lngs = array_column($ring, 0);
        $lats = array_column($ring, 1);

        return [min($lngs), min($lats), max($lngs), max($lats)];
    }
}

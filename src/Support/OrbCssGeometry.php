<?php

namespace Neura\Kit\Support;

/**
 * Geometry helpers for CSS orbs (AICSS-style variants).
 *
 * @see https://www.aicss.dev/components/orbs
 */
class OrbCssGeometry
{
    public const STAGE = 28;

    public const DEFAULT_SIZE = 20;

    public const LATTICE = ['S1', 'S2', 'S3', 'S4', 'S5'];

    public const LENS = ['B1', 'B2', 'B3', 'B4', 'B5'];

    public const RING = ['C1', 'C2', 'C3', 'C4', 'C5'];

    public const HELIX = ['G1', 'G2', 'G3', 'G4', 'G5'];

    public const MORPH = ['M1', 'M2', 'M3', 'M4', 'M5'];

    public const TASKS = [
        'S1' => 'Thinking',
        'S2' => 'Processing',
        'S3' => 'Working',
        'S4' => 'Searching',
        'S5' => 'Finalizing',
        'B1' => 'Thinking',
        'B2' => 'Searching',
        'B3' => 'Generating',
        'B4' => 'Solving',
        'B5' => 'Routing',
        'C1' => 'Loading',
        'C2' => 'Listening',
        'C3' => 'Streaming',
        'C4' => 'Analyzing',
        'C5' => 'Compiling',
        'G1' => 'Processing',
        'G2' => 'Sequencing',
        'G3' => 'Uploading',
        'G4' => 'Syncing',
        'G5' => 'Idling',
        'M1' => 'Shaping',
        'M2' => 'Expanding',
        'M3' => 'Unfolding',
        'M4' => 'Transforming',
        'M5' => 'Dispersing',
    ];

    public static function isValid(string $variant): bool
    {
        return isset(self::TASKS[strtoupper($variant)]);
    }

    public static function normalize(?string $variant): ?string
    {
        if ($variant === null || $variant === '') {
            return null;
        }

        $v = strtoupper($variant);

        return self::isValid($v) ? $v : null;
    }

    public static function family(string $variant): string
    {
        $v = strtoupper($variant);

        return match (true) {
            in_array($v, self::LATTICE, true) => 'lattice',
            in_array($v, self::LENS, true) => 'lens',
            in_array($v, self::RING, true) => 'ring',
            in_array($v, self::HELIX, true) => 'helix',
            in_array($v, self::MORPH, true) => 'morph',
            default => 'lattice',
        };
    }

    public static function task(string $variant): string
    {
        return self::TASKS[strtoupper($variant)] ?? 'Thinking';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function nodes(string $variant): array
    {
        $v = strtoupper($variant);

        return match (self::family($v)) {
            'lattice' => self::latticeCells($v),
            'ring' => self::ringDots($v),
            'helix' => self::globeDots($v),
            'morph' => self::morphDots($v),
            default => [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function latticeCells(string $v): array
    {
        $n = 3;
        $pitch = 6;
        $mid = ($n - 1) / 2;
        $ring = [];
        for ($x = 0; $x < $n; $x++) {
            $ring[] = [$x, 0];
        }
        for ($y = 1; $y < $n; $y++) {
            $ring[] = [$n - 1, $y];
        }
        for ($x = $n - 2; $x >= 0; $x--) {
            $ring[] = [$x, $n - 1];
        }
        for ($y = $n - 2; $y >= 1; $y--) {
            $ring[] = [0, $y];
        }
        $ringIndex = [];
        foreach ($ring as $i => [$rx, $ry]) {
            $ringIndex["{$rx},{$ry}"] = $i;
        }

        $swirl = 1.05;
        $spread = 1.6;
        $swirlFn = static function (int $x, int $y, float $angle) use ($mid, $spread, $pitch): array {
            $dx = $x - $mid;
            $dy = $y - $mid;
            $cos = cos($angle);
            $sin = sin($angle);

            return [
                (($dx * $cos - $dy * $sin) * $spread - $dx) * $pitch,
                (($dx * $sin + $dy * $cos) * $spread - $dy) * $pitch,
            ];
        };

        $cells = [];
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $dx = $x - $mid;
                $dy = $y - $mid;
                $key = "{$x},{$y}";
                $delay = match ($v) {
                    'S1' => hypot($dx, $dy) * 700 - ($dx === 0.0 && $dy === 0.0 ? 180 : 0),
                    'S2' => (($x + $y) / (2 * ($n - 1))) * 1500,
                    'S3' => isset($ringIndex[$key])
                        ? -((((count($ring) - $ringIndex[$key]) % count($ring)) / count($ring)) * 1700)
                        : 0,
                    'S4' => ($x / ($n - 1)) * 1100,
                    'S5' => isset($ringIndex[$key])
                        ? -(((($ringIndex[$key] * 3) % count($ring)) / count($ring)) * 1700)
                        : 0,
                    default => 0,
                };
                [$ax, $ay] = $swirlFn($x, $y, -$swirl);
                [$bx, $by] = $swirlFn($x, $y, $swirl);
                $cells[] = [
                    'key' => $key,
                    'left' => $x * $pitch,
                    'top' => $y * $pitch,
                    'delay' => $delay,
                    'ax' => $ax,
                    'ay' => $ay,
                    'bx' => $bx,
                    'by' => $by,
                    'still' => ($v === 'S3' || $v === 'S5') && ! isset($ringIndex[$key]),
                    'mid' => $x === (int) $mid && $y === (int) $mid,
                ];
            }
        }

        return $cells;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function ringDots(string $v): array
    {
        $n = 8;
        $r = 8;
        $dur = match ($v) {
            'C1' => 1600,
            'C2' => 2000,
            'C3' => 1800,
            'C4' => 1600,
            'C5' => 2200,
            default => 1600,
        };

        $dots = [];
        for ($i = 0; $i < $n; $i++) {
            $angle = ($i / $n) * M_PI * 2 - M_PI / 2;
            $delay = match ($v) {
                'C1', 'C2', 'C3' => -((($n - 1 - $i) / $n) * $dur),
                'C4' => $i % 2 === 0 ? 0 : -($dur / 2),
                'C5' => -(((($i * 3) % $n) / $n) * $dur),
                default => -(($i / $n) * $dur),
            };
            $dots[] = [
                'key' => $i,
                'rx' => cos($angle) * $r,
                'ry' => sin($angle) * $r,
                'delay' => $delay,
            ];
        }

        return $dots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function globeDots(string $v): array
    {
        $globeR = 8.5;
        $tilt = (14 * M_PI) / 180;
        $steps = 8;
        $rings = [
            ['lat' => 52, 'count' => 8],
            ['lat' => 26, 'count' => 8],
            ['lat' => 0, 'count' => 8],
            ['lat' => -26, 'count' => 8],
            ['lat' => -52, 'count' => 8],
        ];

        $project = static function (float $x, float $y, float $z, float $spin) use ($tilt): array {
            $cs = cos($spin);
            $ss = sin($spin);
            $x1 = $x * $cs - $z * $ss;
            $z1 = $x * $ss + $z * $cs;
            $ct = cos($tilt);
            $st = sin($tilt);

            return [
                'x' => $x1,
                'y' => $y * $ct - $z1 * $st,
                'z' => $y * $st + $z1 * $ct,
            ];
        };

        $opacity = static function (float $z) use ($globeR): float {
            $t = max(0, min(1, ($z / $globeR + 0.15) / 1.15));

            return 0.12 + 0.88 * $t * $t;
        };

        $ringDir = static fn (int $ring): int => $ring % 2 === 0 ? -1 : 1;
        $ringHalf = M_PI;
        $ringArc = 3;

        $g3Moves = [];
        for ($pass = 0; $pass < 2; $pass++) {
            for ($r = 0; $r < count($rings); $r++) {
                $g3Moves[] = ['ring' => $r, 'angle' => $ringDir($r) * $ringHalf];
            }
        }
        $g4Moves = array_map(
            static fn (int $ring): array => ['ring' => $ring, 'angle' => $ringDir($ring) * $ringHalf],
            [2, 1, 3, 0, 4, 2, 1, 3, 0, 4],
        );

        $ringTurnPoses = static function (float $x0, float $y0, float $z0, int $ringIndex, array $moves) use ($ringArc): array {
            $x = $x0;
            $y = $y0;
            $z = $z0;
            $poses = [[$x, $y, $z]];
            foreach ($moves as $move) {
                $xS = $x;
                $yS = $y;
                $zS = $z;
                for ($s = 1; $s <= $ringArc; $s++) {
                    if ($ringIndex === $move['ring']) {
                        $a = $move['angle'] * ($s / $ringArc);
                        $c = cos($a);
                        $sn = sin($a);
                        $x = $xS * $c - $zS * $sn;
                        $y = $yS;
                        $z = $xS * $sn + $zS * $c;
                    }
                    $poses[] = [$x, $y, $z];
                }
            }

            return $poses;
        };

        $g5Slow = 0.4;
        $g5Burst = (M_PI * 2 - $g5Slow * 4) / 4;
        $g5Poses = [['s' => 1.0, 'spin' => 0.0]];
        $spin = 0.0;
        foreach ([
            ['s' => 1.0, 'kind' => 'slow'],
            ['s' => 0.9, 'kind' => 'burst'],
            ['s' => 0.9, 'kind' => 'slow'],
            ['s' => 0.8, 'kind' => 'burst'],
            ['s' => 0.8, 'kind' => 'slow'],
            ['s' => 0.9, 'kind' => 'burst'],
            ['s' => 0.9, 'kind' => 'slow'],
            ['s' => 1.0, 'kind' => 'burst'],
        ] as $step) {
            $spin += $step['kind'] === 'slow' ? $g5Slow : $g5Burst;
            $g5Poses[] = ['s' => $step['s'], 'spin' => $spin];
        }

        $dots = [];
        $idx = 0;
        foreach ($rings as $ringIndex => $ring) {
            $latRad = ($ring['lat'] * M_PI) / 180;
            $y0 = sin($latRad) * $globeR;
            $ringR = cos($latRad) * $globeR;
            for ($j = 0; $j < $ring['count']; $j++) {
                $lon = ($j / $ring['count']) * M_PI * 2;
                $x0 = cos($lon) * $ringR;
                $z0 = sin($lon) * $ringR;
                $style = [];

                if ($v === 'G5') {
                    foreach ($g5Poses as $k => $pose) {
                        $sc = $pose['s'];
                        $p = $project($x0 * $sc, $y0 * $sc, $z0 * $sc, $pose['spin']);
                        $style["--g{$k}x"] = number_format($p['x'], 2, '.', '').'px';
                        $style["--g{$k}y"] = number_format(-$p['y'], 2, '.', '').'px';
                        $style["--g{$k}o"] = number_format($opacity($p['z']), 3, '.', '');
                    }
                } elseif ($v === 'G3' || $v === 'G4') {
                    $poses = $ringTurnPoses($x0, $y0, $z0, $ringIndex, $v === 'G3' ? $g3Moves : $g4Moves);
                    foreach ($poses as $k => $pos) {
                        $p = $project($pos[0], $pos[1], $pos[2], 0);
                        $style["--g{$k}x"] = number_format($p['x'], 2, '.', '').'px';
                        $style["--g{$k}y"] = number_format(-$p['y'], 2, '.', '').'px';
                        $style["--g{$k}o"] = number_format($opacity($p['z']), 3, '.', '');
                    }
                } else {
                    $dir = ($v === 'G2' && $ringIndex % 2 === 1) ? -1 : 1;
                    for ($k = 0; $k < $steps; $k++) {
                        $phase = $k / $steps;
                        $p = $project($x0, $y0, $z0, $dir * $phase * M_PI * 2);
                        $style["--g{$k}x"] = number_format($p['x'], 2, '.', '').'px';
                        $style["--g{$k}y"] = number_format(-$p['y'], 2, '.', '').'px';
                        $style["--g{$k}o"] = number_format($opacity($p['z']), 3, '.', '');
                    }
                }

                $dots[] = ['key' => $idx, 'style' => $style];
                $idx++;
            }
        }

        return $dots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function morphDots(string $v): array
    {
        $n = 8;
        $r = 7.0;

        $circle = static function (int $i) use ($n, $r): array {
            $a = ($i / $n) * M_PI * 2 - M_PI / 2;

            return [cos($a) * $r, sin($a) * $r];
        };
        $circleAt = static function (float $turn) use ($n, $r): callable {
            return static function (int $i) use ($n, $r, $turn): array {
                $a = ($i / $n) * M_PI * 2 - M_PI / 2 + $turn;

                return [cos($a) * $r, sin($a) * $r];
            };
        };
        $square = static function (int $i) use ($n, $r): array {
            $h = $r * 0.85;
            $corners = [[-$h, -$h], [$h, -$h], [$h, $h], [-$h, $h]];
            $t = (($i / $n) * 4 + 0.5) % 4;
            $side = (int) floor($t) % 4;
            $frac = $t - floor($t);
            $from = $corners[$side];
            $to = $corners[($side + 1) % 4];

            return [$from[0] + ($to[0] - $from[0]) * $frac, $from[1] + ($to[1] - $from[1]) * $frac];
        };
        $diamond = static function (int $i) use ($n, $r): array {
            $corners = [[0, -$r], [$r, 0], [0, $r], [-$r, 0]];
            $t = ($i / $n) * 4;
            $side = (int) floor($t) % 4;
            $frac = $t - floor($t);
            $from = $corners[$side];
            $to = $corners[($side + 1) % 4];

            return [$from[0] + ($to[0] - $from[0]) * $frac, $from[1] + ($to[1] - $from[1]) * $frac];
        };
        $center = static function (int $i) use ($n): array {
            $a = ($i / $n) * M_PI * 2 - M_PI / 2;

            return [cos($a) * 1.5, sin($a) * 1.5];
        };
        $scatterA = static function (int $i) use ($n, $r): array {
            $a = ($i / $n) * M_PI * 2 - M_PI / 2;

            return [-cos($a) * $r, sin($a) * $r];
        };

        [$s1, $s2, $s3, $s4] = match ($v) {
            'M1' => [$circle, $square, $diamond, $square],
            'M2' => [$center, $circle, $center, $circle],
            'M3' => [$circleAt(0), $circleAt(M_PI / 2), $circleAt(M_PI), $circleAt(M_PI * 1.5)],
            'M4' => [$circle, $diamond, $circle, $diamond],
            'M5' => [$circle, $scatterA, $circle, $scatterA],
            default => [$circle, $square, $diamond, $square],
        };

        $fmt = static fn (array $xy): string => number_format($xy[0], 1, '.', '').'px, '.number_format($xy[1], 1, '.', '').'px';

        $dots = [];
        for ($i = 0; $i < $n; $i++) {
            $dots[] = [
                'key' => $i,
                'm1' => $fmt($s1($i)),
                'm2' => $fmt($s2($i)),
                'm3' => $fmt($s3($i)),
                'm4' => $fmt($s4($i)),
                'delay' => $v === 'M5' ? (-$i * 10).'ms' : null,
                'depth' => $v === 'M5'
                    ? number_format(abs(cos(($i / $n) * M_PI * 2 - M_PI / 2)), 2, '.', '')
                    : null,
            ];
        }

        return $dots;
    }
}

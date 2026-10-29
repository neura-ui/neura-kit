<?php

namespace Neura\Kit\Support\Map;

use Generator;
use RuntimeException;

/**
 * The features of a GeoJSON FeatureCollection, read one at a time.
 *
 * Boundary files run to tens of megabytes, on a single line (Natural Earth) or one feature per
 * line (geoBoundaries); decoding one whole would take far more memory than PHP is usually
 * given. This walks the `features` array byte by byte, skipping strings and coordinates in
 * runs, and decodes each feature on its own, so memory stays at the size of one feature.
 */
final class GeoJsonFeatures
{
    /**
     * @return Generator<int, array<string, mixed>>
     */
    public static function read(string $path, int $chunk = 1 << 20): Generator
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open {$path}.");
        }

        try {
            $buffer = '';
            $eof = false;

            $fill = static function () use ($handle, &$buffer, &$eof, $chunk): bool {
                if ($eof) {
                    return false;
                }

                $data = fread($handle, $chunk);

                if ($data === false || $data === '') {
                    $eof = true;

                    return false;
                }

                $buffer .= $data;

                return true;
            };

            // The header is small: keep reading until the features array opens.
            while (($key = strpos($buffer, '"features"')) === false) {
                if (! $fill()) {
                    return;
                }
            }

            while (($open = strpos($buffer, '[', $key)) === false) {
                if (! $fill()) {
                    return;
                }
            }

            $buffer = substr($buffer, $open + 1);
            $position = 0;
            $depth = 0;
            $start = -1;
            $inString = false;

            while (true) {
                $length = strlen($buffer);

                if ($position >= $length) {
                    // Keep only the feature being read.
                    if ($start > 0) {
                        $buffer = substr($buffer, $start);
                        $position -= $start;
                        $start = 0;
                    } elseif ($start < 0) {
                        $buffer = '';
                        $position = 0;
                    }

                    if (! $fill()) {
                        return;
                    }

                    continue;
                }

                if ($inString) {
                    $position += strcspn($buffer, '"\\', $position);

                    if ($position >= $length) {
                        continue;
                    }

                    if ($buffer[$position] === '\\') {
                        $position += 2;

                        continue;
                    }

                    $inString = false;
                    $position++;

                    continue;
                }

                // Inside a feature only braces and strings matter; between features, the end of the array too.
                $position += strcspn($buffer, $depth > 0 ? '{}"' : '{}"]', $position);

                if ($position >= $length) {
                    continue;
                }

                $char = $buffer[$position];
                $position++;

                if ($char === '"') {
                    $inString = true;
                } elseif ($char === '{') {
                    if ($depth === 0) {
                        $start = $position - 1;
                    }

                    $depth++;
                } elseif ($char === '}') {
                    $depth--;

                    if ($depth === 0) {
                        $feature = json_decode(substr($buffer, $start, $position - $start), true);
                        $start = -1;

                        if (is_array($feature)) {
                            yield $feature;
                        }
                    }
                } elseif ($depth === 0) {
                    // `]`: the end of the features array.
                    return;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}

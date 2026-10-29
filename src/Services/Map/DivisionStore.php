<?php

namespace Neura\Kit\Services\Map;

use Closure;
use Illuminate\Support\Facades\Http;
use Neura\Kit\Support\Map\CountryCode;
use Neura\Kit\Support\Map\FeatureWriter;
use Neura\Kit\Support\Map\GeoJsonFeatures;
use Neura\Kit\Support\Map\Geometry;
use Neura\Kit\Support\PackResolver;
use RuntimeException;

/**
 * The subdivisions of a country, cut from worldwide open data the first time someone asks
 * for that country, then served from disk.
 *
 *   states     Natural Earth admin-1: the first-level divisions of every country with their
 *              ISO 3166-2 code (French departments, US states, German Länder…).
 *   adm1…adm5  geoBoundaries, country by country: from regions down to municipalities
 *              (in France, adm2 departments, adm5 its 35 000 communes).
 *
 * A source is downloaded once (Natural Earth for every country, geoBoundaries per country and
 * level), read feature by feature, lightened to map precision and written per country. The
 * finer levels are also split by their parent division, so drilling into one department
 * loads its communes alone. Nothing is fetched until a country is asked for.
 */
final class DivisionStore
{
    public const LEVELS = ['states', 'adm1', 'adm2', 'adm3', 'adm4', 'adm5'];

    private ?int $precision = null;

    /** The level's file for a country, built on the first request. */
    public function level(string $country, string $level): string
    {
        $iso3 = $this->country($country);
        $level = $this->assertLevel($level);
        $target = $this->path("divisions/{$iso3}/{$level}.json");

        $this->once($target, function () use ($iso3, $level, $target): void {
            $source = $this->source($iso3, $level);
            $writer = new FeatureWriter($target);
            $count = 0;

            foreach (GeoJsonFeatures::read($source) as $feature) {
                if ($level === 'states' && ! $this->inCountry($feature, $iso3)) {
                    continue;
                }

                $division = $this->normalize($feature, $level);

                if ($division !== null) {
                    $writer->add($division);
                    $count++;
                }
            }

            $writer->close();

            if ($count === 0) {
                @unlink($target);

                throw new DivisionsUnavailable("No {$level} divisions for {$iso3}.");
            }
        });

        return $target;
    }

    /**
     * The divisions of `level` that lie inside one division of `parentLevel`: the communes of
     * one department, say. The whole level is split by parent on the first request.
     */
    public function within(string $country, string $level, string $parentLevel, string $parentKey): string
    {
        $iso3 = $this->country($country);
        $level = $this->assertLevel($level);
        $parentLevel = $this->assertLevel($parentLevel);

        if ($level === $parentLevel) {
            throw new DivisionsUnavailable('A level cannot be split by itself.');
        }

        $parents = $this->level($iso3, $parentLevel);
        $directory = $this->path("divisions/{$iso3}/{$level}-in-{$parentLevel}");
        $complete = "{$directory}/index.json";

        $this->once($complete, fn () => $this->split($iso3, $level, $parents, $directory, $complete));

        $target = "{$directory}/{$this->slug($parentKey)}.json";

        if (! is_file($target)) {
            throw new DivisionsUnavailable("No division {$parentKey} in {$iso3} {$parentLevel}.");
        }

        return $target;
    }

    /**
     * Assign every division of a level to the parent its label point falls in (or the nearest,
     * where coarse and fine borders disagree along a coast), one file per parent.
     */
    private function split(string $iso3, string $level, string $parentsFile, string $directory, string $complete): void
    {
        $parents = [];

        foreach (GeoJsonFeatures::read($parentsFile) as $feature) {
            $key = (string) ($feature['properties']['key'] ?? '');
            $bounds = Geometry::bounds($feature['geometry'] ?? null);

            if ($key !== '' && $bounds !== null) {
                $parents[$key] = ['geometry' => $feature['geometry'], 'bounds' => $bounds];
            }
        }

        $writers = [];
        $counts = [];

        foreach (GeoJsonFeatures::read($this->source($iso3, $level)) as $feature) {
            $division = $this->normalize($feature, $level);

            if ($division === null) {
                continue;
            }

            $parent = $this->parentOf($parents, (float) $division['properties']['LABEL_X'], (float) $division['properties']['LABEL_Y']);

            if ($parent === null) {
                continue;
            }

            $division['properties']['parent'] = $parent;
            ($writers[$parent] ??= new FeatureWriter("{$directory}/{$this->slug($parent)}.json"))->add($division);
            $counts[$parent] = ($counts[$parent] ?? 0) + 1;
        }

        // Every parent gets a file, an empty one when nothing falls in it.
        foreach (array_keys($parents) as $key) {
            ($writers[$key] ?? new FeatureWriter("{$directory}/{$this->slug($key)}.json"))->close();
        }

        file_put_contents($complete, json_encode(['level' => $level, 'counts' => $counts]));
    }

    /**
     * @param  array<string, array{geometry: array, bounds: array{0: float, 1: float, 2: float, 3: float}}>  $parents
     */
    private function parentOf(array $parents, float $lng, float $lat): ?string
    {
        $nearest = null;
        $nearestDistance = INF;

        foreach ($parents as $key => ['geometry' => $geometry, 'bounds' => [$west, $south, $east, $north]]) {
            $inBox = $lng >= $west && $lng <= $east && $lat >= $south && $lat <= $north;

            if ($inBox && Geometry::contains($geometry, $lng, $lat)) {
                return $key;
            }

            // Distance to the box, then to its centre among boxes that hold the point.
            $dx = max($west - $lng, 0, $lng - $east);
            $dy = max($south - $lat, 0, $lat - $north);
            $distance = $dx * $dx + $dy * $dy + (($west + $east) / 2 - $lng) ** 2 * 1e-6 + (($south + $north) / 2 - $lat) ** 2 * 1e-6;

            if ($distance < $nearestDistance) {
                $nearest = $key;
                $nearestDistance = $distance;
            }
        }

        return $nearest;
    }

    /**
     * One division as the map reads it: key (ISO 3166-2 where there is one), code (the part
     * after the country, `18` for FR-18), name, label point, lightened outline.
     *
     * @return array<string, mixed>|null
     */
    private function normalize(array $feature, string $level): ?array
    {
        $this->precision ??= (int) (PackResolver::mapSetting('divisions')['precision'] ?? 4);
        $geometry = Geometry::lighten($feature['geometry'] ?? null, $this->precision);

        if ($geometry === null) {
            return null;
        }

        $properties = $feature['properties'] ?? [];

        if ($level === 'states') {
            $iso = trim((string) ($properties['iso_3166_2'] ?? ''));
            $iso = preg_match('/^[A-Z]{2}-[A-Z0-9]{1,3}$/', $iso) ? $iso : '';
            $key = $iso !== '' ? $iso : (string) ($properties['adm1_code'] ?? '');
            $name = (string) ($properties['name'] ?? $properties['name_en'] ?? $key);
            $extra = ['region' => $properties['region'] ?? null];
        } else {
            $iso = trim((string) ($properties['shapeISO'] ?? ''));
            $key = $iso !== '' ? $iso : (string) ($properties['shapeID'] ?? $properties['shapeName'] ?? '');
            $name = (string) ($properties['shapeName'] ?? $key);
            $extra = [];
        }

        if ($key === '') {
            return null;
        }

        $label = Geometry::labelPoint($geometry) ?? [0.0, 0.0];

        return [
            'type' => 'Feature',
            'properties' => array_filter([
                'key' => $key,
                'code' => str_contains($iso, '-') ? substr($iso, strpos($iso, '-') + 1) : null,
                'name' => $name,
                'level' => $level,
                ...$extra,
                'LABEL_X' => $label[0],
                'LABEL_Y' => $label[1],
            ], fn ($value) => $value !== null && $value !== ''),
            'geometry' => $geometry,
        ];
    }

    private function inCountry(array $feature, string $iso3): bool
    {
        $properties = $feature['properties'] ?? [];

        return ($properties['adm0_a3'] ?? null) === $iso3
            || ($properties['iso_a2'] ?? null) === CountryCode::alpha2($iso3);
    }

    /** The source file of a level, downloaded the first time a country needs it. */
    private function source(string $iso3, string $level): string
    {
        $settings = PackResolver::mapSetting('divisions') ?? [];

        if ($level === 'states') {
            $target = $this->path('sources/natural-earth-admin-1.geojson');
            $this->once($target, fn () => $this->download((string) $settings['states'], $target));

            return $target;
        }

        $number = (int) substr($level, 3);
        $target = $this->path("sources/{$iso3}-ADM{$number}.geojson");

        $this->once($target, function () use ($settings, $iso3, $number, $target): void {
            $meta = Http::timeout(30)->acceptJson()->get(sprintf((string) $settings['boundaries'], $iso3, $number));

            if ($meta->status() === 404 || ! is_array($meta->json())) {
                throw new DivisionsUnavailable("geoBoundaries has no ADM{$number} for {$iso3}.");
            }

            $meta->throw();
            $url = $meta->json('simplifiedGeometryGeoJSON') ?: $meta->json('gjDownloadURL');

            if (! is_string($url) || $url === '') {
                throw new DivisionsUnavailable("geoBoundaries has no ADM{$number} for {$iso3}.");
            }

            $this->download($url, $target);
        });

        return $target;
    }

    private function download(string $url, string $target): void
    {
        $temporary = $target.'.'.getmypid().'.download';

        try {
            Http::timeout(600)->withOptions(['sink' => $temporary])->get($url)->throw();

            if (! is_file($temporary) || filesize($temporary) < 64) {
                throw new RuntimeException("Empty download from {$url}.");
            }

            rename($temporary, $target);
        } finally {
            @unlink($temporary);
        }
    }

    /** Build a file once, even when several requests ask for it at the same time. */
    private function once(string $target, Closure $build): void
    {
        if (is_file($target)) {
            return;
        }

        $directory = dirname($target);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create {$directory}.");
        }

        $lock = fopen("{$target}.lock", 'c');

        try {
            flock($lock, LOCK_EX);

            if (! is_file($target)) {
                $build();
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function country(string $country): string
    {
        return CountryCode::alpha3($country)
            ?? throw new DivisionsUnavailable("Unknown country {$country}.");
    }

    private function assertLevel(string $level): string
    {
        $level = strtolower($level);

        return in_array($level, self::LEVELS, true)
            ? $level
            : throw new DivisionsUnavailable("Unknown level {$level}.");
    }

    private function slug(string $key): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '_', $key).'-'.substr(sha1($key), 0, 8);
    }

    private function path(string $relative): string
    {
        $base = PackResolver::mapSetting('divisions')['path'] ?? null;

        return rtrim($base ?: storage_path('app/neura-kit/map'), '/').'/'.$relative;
    }
}

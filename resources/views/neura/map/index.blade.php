@props([
    'center' => [20, 0],
    'zoom' => 2,
    'minZoom' => 1,
    'maxZoom' => 7,
    'mapStyle' => null,
    'mapStyles' => null,
    'land' => 'countries',
    'lakes' => 'lakes',
    'graticule' => true,
    'labels' => true,
    'geojson' => null,
    'country' => null,
    'cities' => null,
    'zones' => true,
    'cityValues' => [],
    'values' => [],
    'valueKey' => null,
    'nameKey' => null,
    'markers' => [],
    'fit' => false,
    'scrollZoom' => true,
    'controls' => true,
    'height' => '420px',
    'variant' => 'card',
    'label' => null,
])

@php
    use Illuminate\Support\Str;
    use Neura\Kit\Support\PackResolver;

    /*
     * The map draws everything itself from open data: the land and lakes of a dataset
     * (Natural Earth by default, coarse to fine as the zoom grows), the graticule, the labels
     * and an optional `geojson` overlay, in one of the looks it paints. A dataset is a name
     * from the config, a URL, or GeoJSON / TopoJSON data.
     *
     * `country` (an ISO code) draws that country alone, framed on its mainland, with its
     * cities (the `places` dataset unless `cities` names another, or `false`), each in its own
     * colour, or coloured by `city-values` keyed by city name; the country is split into the
     * zones of its cities (the part closer to each), unless `zones` is false.
     */
    $levels = function (mixed $source): ?array {
        if ($source === null || $source === false || $source === '') {
            return null;
        }

        if (is_array($source)) {
            return [['source' => $source, 'from' => 0]];
        }

        return array_map(
            fn (array $level): array => ['source' => $level['url'], 'from' => $level['from']],
            PackResolver::mapDataset((string) $source),
        );
    };

    $country = $country ? strtoupper((string) $country) : null;
    $cities ??= $country ? 'places' : false;

    $styles = PackResolver::mapStyles();
    $current = PackResolver::mapStyleName($mapStyle);
    $offered = array_values(array_filter((array) $mapStyles, fn ($name) => isset($styles[$name])));

    $styleConfig = [];
    foreach (array_unique([$current, ...$offered]) as $name) {
        $style = $styles[$name];
        $styleConfig[$name] = [
            'water' => $style['water'] ?? 'transparent',
            'land' => $style['land'] ?? 'transparent',
            'dot' => $style['dot'] ?? null,
            'border' => $style['border'] ?? 'transparent',
            'graticule' => $style['graticule'] ?? null,
            'label' => $style['label'] ?? 'currentColor',
            'ink' => $style['ink'] ?? $style['label'] ?? 'currentColor',
            'halo' => $style['halo'] ?? 'transparent',
            'theme' => $style['theme'] ?? 'auto',
        ];
    }

    $locale = str_replace('_', '-', app()->getLocale());
    $language = strtoupper(strtok($locale, '-'));

    $config = [
        'center' => array_values((array) $center),
        'zoom' => (float) $zoom,
        'minZoom' => (float) $minZoom,
        'maxZoom' => (float) $maxZoom,
        'style' => $current,
        'styles' => $styleConfig,
        'land' => $levels($land),
        'lakes' => $levels($lakes),
        'overlay' => $levels($geojson),
        'country' => $country,
        'cities' => $levels($cities === true ? 'places' : $cities),
        'zones' => (bool) $zones && $country !== null,
        'graticule' => (bool) $graticule,
        'labels' => (bool) $labels,
        'fit' => (bool) $fit,
        'scrollZoom' => (bool) $scrollZoom,
        'valueKeys' => $valueKey ? (array) $valueKey : ['ISO_A2_EH', 'ISO_A2', 'iso_a2', 'ISO', 'iso', 'code', 'id'],
        // In English, Natural Earth's NAME is the common name; NAME_EN is the formal one.
        'nameKeys' => $nameKey ? (array) $nameKey : [...($language === 'EN' ? [] : ["NAME_{$language}"]), 'NAME', 'name', 'ADMIN'],
        'locale' => $locale,
        'noData' => neura_trans('noData'),
        'credit' => $land || $lakes ? PackResolver::mapSetting('credit') : null,
    ];

    $data = [
        'markers' => array_values((array) $markers),
        'values' => (object) (array) $values,
        'cityValues' => (object) (array) $cityValues,
    ];


    $roundedClass = PackResolver::rounded(neura_config('map', 'rounded'));
    $shadowClass = PackResolver::shadow(neura_config('map', 'shadow'));

    $containerClass = match ($variant) {
        'minimal' => '',
        default => "border border-edge {$roundedClass} {$shadowClass}",
    };
@endphp

<div
    {{ $attributes->class(['nk-map', $containerClass]) }}
    data-nk-map
    data-map="{{ json_encode($data) }}"
    x-data="neuraMap(@js($config))"
    style="height: {{ $height }};"
>
    <div
        x-ref="viewport"
        wire:ignore
        class="nk-map-viewport"
        tabindex="0"
        role="region"
        aria-roledescription="map"
        aria-label="{{ $label ?? neura_trans('map') }}"
    ></div>

    @if ($controls)
        <div class="nk-map-controls">
            <button type="button" class="nk-map-control" x-on:click="zoomIn()" aria-label="{{ neura_trans('zoomIn') }}">
                <neura::icon name="plus" class="size-4" />
            </button>
            <button type="button" class="nk-map-control" x-on:click="zoomOut()" aria-label="{{ neura_trans('zoomOut') }}">
                <neura::icon name="minus" class="size-4" />
            </button>
        </div>
    @endif

    @if (count($offered) > 1)
        <div class="nk-map-styles" role="radiogroup" aria-label="{{ neura_trans('mapStyle') }}">
            @foreach ($offered as $name)
                <button
                    type="button"
                    role="radio"
                    class="nk-map-style"
                    x-bind:class="{ 'is-active': style === @js($name) }"
                    x-bind:aria-checked="style === @js($name)"
                    x-on:click="useStyle(@js($name))"
                >{{ neura_trans('mapStyle'.Str::studly($name), null, Str::headline($name)) }}</button>
            @endforeach
        </div>
    @endif

    <div class="nk-map-status" x-show="loading" x-cloak>
        <neura::spinner variant="ring" size="sm" color="current" />
    </div>

</div>

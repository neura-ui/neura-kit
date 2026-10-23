<?php

namespace Neura\Kit\Packs\Map;

use Neura\Kit\Packs\BasePack;

/**
 * The looks the native map draws itself: no tiles, no provider, only colours for the water,
 * the land, the borders, the graticule and the labels (`label` for the countries, the stronger
 * `ink` for the cities, both legible on the look's own `halo`).
 *
 * `standard` follows the kit's surface tokens, so it turns dark with the theme. `land` may be
 * `dots`, which draws the land as a dot pattern; a shape coloured by its value is then mixed
 * with the water instead. `theme` tells the map whether the look reads light or dark, so its
 * outlines stay legible (`auto` follows the page).
 */
class Style extends BasePack
{
    public static function default(): array
    {
        return [
            'standard' => [
                'water' => 'var(--nk-surface-inset)',
                'land' => 'var(--nk-surface)',
                'border' => 'color-mix(in oklab, var(--nk-fg) 20%, transparent)',
                'graticule' => 'color-mix(in oklab, var(--nk-fg) 6%, transparent)',
                'label' => 'var(--nk-fg-secondary)',
                'ink' => 'var(--nk-fg)',
                'halo' => 'var(--nk-surface)',
                'theme' => 'auto',
            ],
            'dark' => [
                'water' => '#0b0e13',
                'land' => '#1a1e26',
                'border' => '#353b48',
                'graticule' => 'rgb(255 255 255 / 0.05)',
                'label' => '#8d95a7',
                'ink' => '#e8ebf1',
                'halo' => '#0b0e13',
                'theme' => 'dark',
            ],
            'atlas' => [
                'water' => '#aad3df',
                'land' => '#f2efe9',
                'border' => '#b9a9bf',
                'graticule' => 'rgb(40 80 110 / 0.10)',
                'label' => '#5b4f45',
                'ink' => '#3a302a',
                'halo' => '#f2efe9',
                'theme' => 'light',
            ],
            'blueprint' => [
                'water' => '#0f356f',
                'land' => '#16458c',
                'border' => 'rgb(255 255 255 / 0.55)',
                'graticule' => 'rgb(255 255 255 / 0.12)',
                'label' => 'rgb(255 255 255 / 0.85)',
                'ink' => '#ffffff',
                'halo' => '#0f356f',
                'theme' => 'dark',
            ],
            'paper' => [
                'water' => '#ffffff',
                'land' => '#ffffff',
                'border' => '#1d1d1f',
                'graticule' => null,
                'label' => '#1d1d1f',
                'ink' => '#000000',
                'halo' => '#ffffff',
                'theme' => 'light',
            ],
            'dots' => [
                'water' => 'var(--nk-surface)',
                'land' => 'dots',
                'dot' => 'color-mix(in oklab, var(--nk-fg) 32%, transparent)',
                'border' => 'transparent',
                'graticule' => null,
                'label' => 'var(--nk-fg-secondary)',
                'ink' => 'var(--nk-fg)',
                'halo' => 'var(--nk-surface)',
                'theme' => 'auto',
            ],
        ];
    }
}

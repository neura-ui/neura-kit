<?php

namespace Neura\Kit\Packs\Filters;

use Neura\Kit\Packs\BasePack;

/**
 * Structural appearance variants for the filter chips.
 *
 * The `chip` classes define the resting look of each active filter chip
 * (border style, surface), `panel` the dropdown surfaces. Reactive hover
 * states live in the component.
 */
class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'chip' => 'border border-solid border-edge bg-surface',
                'panel' => 'bg-surface-raised',
                'shadow' => null,
                'rounded' => null,
            ],
            'solid' => [
                'chip' => 'border border-solid border-edge bg-surface-inset',
                'panel' => 'bg-surface-raised',
                'shadow' => null,
                'rounded' => null,
            ],
            'card' => [
                'chip' => 'border border-solid border-edge bg-surface',
                'panel' => 'bg-surface-raised',
                'shadow' => 'md',
                'rounded' => null,
            ],
            'minimal' => [
                'chip' => 'border border-solid border-edge bg-transparent',
                'panel' => 'bg-surface-raised',
                'shadow' => 'none',
                'rounded' => null,
            ],
        ];
    }
}

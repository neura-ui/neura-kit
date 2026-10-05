<?php

namespace Neura\Kit\Packs\Button;

use Neura\Kit\Packs\BasePack;

class IconSize extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => 'size-4',
            'sm' => 'size-4',
            'md' => 'size-5',
            'lg' => 'size-5',
            'xl' => 'size-6',
        ];
    }

    public static function variant(): array
    {
        return [
            'xs' => 'micro',
            'sm' => 'mini',
            'md' => 'mini',
            'lg' => 'mini',
            'xl' => 'outline',
        ];
    }

    /** Pixel size for loading orbs, keyed by button size. */
    public static function orb(): array
    {
        return [
            'xs' => 18,
            'sm' => 20,
            'md' => 22,
            'lg' => 24,
            'xl' => 28,
        ];
    }
}


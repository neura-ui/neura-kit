<?php

namespace Neura\Kit\Packs\Badge;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => 'text-xs py-0.5 px-1.5',
            'sm' => 'text-xs py-1 px-2',
            'md' => 'text-sm py-1 px-2.5',
            'lg' => 'text-sm py-1.5 px-3',
        ];
    }

    public static function pill(): array
    {
        return [
            'xs' => 'text-xs py-0.5 px-2',
            'sm' => 'text-xs py-1 px-2.5',
            'md' => 'text-sm py-1 px-3',
            'lg' => 'text-sm py-1.5 px-4',
        ];
    }
}

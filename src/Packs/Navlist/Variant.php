<?php

namespace Neura\Kit\Packs\Navlist;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:bg-hover',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:bg-active',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
            ],
            'ghost' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
            ],
        ];
    }
}

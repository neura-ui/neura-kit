<?php

namespace Neura\Kit\Packs\Navlist;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => [
                'text' => 'text-xs',
                'icon' => 'size-4',
            ],
            'sm' => [
                'text' => 'text-sm',
                'icon' => 'size-4',
            ],
            'md' => [
                'text' => 'text-base',
                'icon' => 'size-5',
            ],
            'lg' => [
                'text' => 'text-lg',
                'icon' => 'size-6',
            ],
            'xl' => [
                'text' => 'text-xl',
                'icon' => 'size-7',
            ],
        ];
    }
}

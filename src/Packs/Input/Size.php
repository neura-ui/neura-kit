<?php

namespace Neura\Kit\Packs\Input;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => 'h-6 text-xs px-1 py-0',
            'sm' => 'h-8 text-sm px-2 py-1',
            'md' => 'h-10 text-sm px-3 py-2',
            'lg' => 'h-12 text-base px-4 py-2',
            'xl' => 'h-14 text-lg px-5 py-3',
        ];
    }
}





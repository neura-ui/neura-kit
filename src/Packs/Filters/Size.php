<?php

namespace Neura\Kit\Packs\Filters;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => [
                'chip' => 'h-6 text-xs',
                'segment' => 'gap-1 px-2',
                'icon' => 'size-3',
                'remove' => 'px-1',
                'input' => 'w-28',
            ],
            'sm' => [
                'chip' => 'h-8 text-sm',
                'segment' => 'gap-1.5 px-2.5',
                'icon' => 'size-3.5',
                'remove' => 'px-1.5',
                'input' => 'w-32',
            ],
            'md' => [
                'chip' => 'h-10 text-sm',
                'segment' => 'gap-2 px-3',
                'icon' => 'size-4',
                'remove' => 'px-2',
                'input' => 'w-36',
            ],
            'lg' => [
                'chip' => 'h-12 text-base',
                'segment' => 'gap-2 px-3.5',
                'icon' => 'size-4',
                'remove' => 'px-2.5',
                'input' => 'w-40',
            ],
            'xl' => [
                'chip' => 'h-14 text-lg',
                'segment' => 'gap-2.5 px-4',
                'icon' => 'size-5',
                'remove' => 'px-3',
                'input' => 'w-44',
            ],
        ];
    }
}

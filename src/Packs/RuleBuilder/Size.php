<?php

namespace Neura\Kit\Packs\RuleBuilder;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => [
                'row' => 'min-h-7 text-xs gap-1.5 px-1.5 py-1',
                'control' => 'h-7 text-xs px-2',
                'icon' => 'size-3',
                'chip' => 'h-5 text-[11px] px-1.5 gap-1',
                'rail' => 'w-6',
            ],
            'sm' => [
                'row' => 'min-h-8 text-sm gap-2 px-2 py-1.5',
                'control' => 'h-8 text-sm px-2.5',
                'icon' => 'size-3.5',
                'chip' => 'h-6 text-xs px-2 gap-1',
                'rail' => 'w-7',
            ],
            'md' => [
                'row' => 'min-h-10 text-sm gap-2 px-2.5 py-2',
                'control' => 'h-9 text-sm px-3',
                'icon' => 'size-4',
                'chip' => 'h-7 text-xs px-2 gap-1.5',
                'rail' => 'w-8',
            ],
            'lg' => [
                'row' => 'min-h-11 text-base gap-2.5 px-3 py-2',
                'control' => 'h-10 text-base px-3.5',
                'icon' => 'size-4',
                'chip' => 'h-8 text-sm px-2.5 gap-1.5',
                'rail' => 'w-9',
            ],
            'xl' => [
                'row' => 'min-h-12 text-base gap-3 px-3.5 py-2.5',
                'control' => 'h-11 text-base px-4',
                'icon' => 'size-5',
                'chip' => 'h-9 text-sm px-3 gap-2',
                'rail' => 'w-10',
            ],
        ];
    }
}

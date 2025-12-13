<?php

namespace Neura\Kit\Packs\Button;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => [
                'base' => 'h-6 text-xs',
                'padding' => 'px-2 py-1',
                'paddingSquared' => 'w-6',
                'paddingWithIcon' => 'ps-1 pe-2',
                'paddingWithIconAfter' => 'ps-2 pe-1',
                'icon' => 'size-4',
            ],
            'sm' => [
                'base' => 'h-8 text-sm',
                'padding' => 'px-3 py-1.5',
                'paddingSquared' => 'w-8',
                'paddingWithIcon' => 'ps-2 pe-3',
                'paddingWithIconAfter' => 'ps-3 pe-2',
                'icon' => 'size-4',
            ],
            'md' => [
                'base' => 'h-10 text-sm',
                'padding' => 'px-4 py-2',
                'paddingSquared' => 'w-10',
                'paddingWithIcon' => 'ps-3 pe-4',
                'paddingWithIconAfter' => 'ps-4 pe-3',
                'icon' => 'size-5',
            ],
            'lg' => [
                'base' => 'h-12 text-base',
                'padding' => 'px-5 py-2.5',
                'paddingSquared' => 'w-12',
                'paddingWithIcon' => 'ps-4 pe-5',
                'paddingWithIconAfter' => 'ps-5 pe-4',
                'icon' => 'size-5',
            ],
            'xl' => [
                'base' => 'h-14 text-lg',
                'padding' => 'px-6 py-3',
                'paddingSquared' => 'w-14',
                'paddingWithIcon' => 'ps-5 pe-6',
                'paddingWithIconAfter' => 'ps-6 pe-5',
                'icon' => 'size-6',
            ],
        ];
    }
}


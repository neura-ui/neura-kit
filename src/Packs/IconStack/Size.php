<?php

namespace Neura\Kit\Packs\IconStack;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Container width/height keep the ~0.9 aspect ratio of the SVG viewBox
     * (72 × 81) so the isometric perspective never distorts across sizes.
     * `icon` is the size of the centered content icon.
     */
    public static function default(): array
    {
        return [
            'xs' => [
                'container' => 'h-12 w-[2.7rem]',
                'icon' => 'size-4',
            ],
            'sm' => [
                'container' => 'h-16 w-[3.6rem]',
                'icon' => 'size-5',
            ],
            'md' => [
                'container' => 'h-20 w-18',
                'icon' => 'size-6',
            ],
            'lg' => [
                'container' => 'h-24 w-[5.4rem]',
                'icon' => 'size-7',
            ],
            'xl' => [
                'container' => 'h-28 w-[6.3rem]',
                'icon' => 'size-8',
            ],
        ];
    }
}

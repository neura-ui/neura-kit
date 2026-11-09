<?php

namespace Neura\Kit\Packs\HoverCard;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the hover card: `width` and `padding` of the card, `gap` between its
     * parts. Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'width' => 'w-60',
                'padding' => 'p-3',
                'gap' => 'gap-2.5',
            ],
            'md' => [
                'width' => 'w-76',
                'padding' => 'p-4',
                'gap' => 'gap-3.5',
            ],
            'lg' => [
                'width' => 'w-96',
                'padding' => 'p-5',
                'gap' => 'gap-4',
            ],
        ];
    }
}

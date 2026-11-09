<?php

namespace Neura\Kit\Packs\Rating;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the rating.
     *
     * `icon` is the side of one star (a CSS length), `gap` the space between stars, `text`
     * the class of the caption beside them. Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'icon' => '1rem',
                'gap' => 'gap-0.5',
                'text' => 'text-xs',
            ],
            'md' => [
                'icon' => '1.5rem',
                'gap' => 'gap-0.5',
                'text' => 'text-sm',
            ],
            'lg' => [
                'icon' => '2rem',
                'gap' => 'gap-1',
                'text' => 'text-base',
            ],
        ];
    }
}

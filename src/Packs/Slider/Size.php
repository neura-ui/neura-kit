<?php

namespace Neura\Kit\Packs\Slider;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the slider.
     *
     * `track` is the height of the rail, `thumb` the diameter of a handle (CSS lengths), `text`
     * the class of the label row and the marks, `field` the input size of the synced number
     * fields. Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'track' => '0.25rem',
                'thumb' => '0.875rem',
                'text' => 'text-xs',
                'field' => 'h-7 text-xs px-2',
            ],
            'md' => [
                'track' => '0.375rem',
                'thumb' => '1.125rem',
                'text' => 'text-sm',
                'field' => 'h-8 text-sm px-2.5',
            ],
            'lg' => [
                'track' => '0.5rem',
                'thumb' => '1.375rem',
                'text' => 'text-base',
                'field' => 'h-10 text-base px-3',
            ],
        ];
    }
}

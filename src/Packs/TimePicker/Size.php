<?php

namespace Neura\Kit\Packs\TimePicker;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the time columns (time picker, and the date picker's time side).
     *
     * Every branch lists complete class literals so Tailwind can scan them.
     * `field` is the input size the picker's trigger uses; `list` is the
     * height of the columns in the time picker's own popup.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'field' => 'sm',
                'option' => 'h-7 text-xs',
                'column' => 'w-10',
                'list' => 'h-56',
            ],
            'md' => [
                'field' => 'md',
                'option' => 'h-8 text-sm',
                'column' => 'w-12',
                'list' => 'h-64',
            ],
            'lg' => [
                'field' => 'lg',
                'option' => 'h-10 text-base',
                'column' => 'w-14',
                'list' => 'h-80',
            ],
        ];
    }
}

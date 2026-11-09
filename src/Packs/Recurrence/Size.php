<?php

namespace Neura\Kit\Packs\Recurrence;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the recurrence editor.
     *
     * `text` is the base class of the editor, `control` the height and text of its buttons and
     * fields, `day` the side of a weekday toggle, `picker` the size handed to the nested time
     * picker. Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'text' => 'text-xs',
                'control' => 'h-7 text-xs',
                'day' => 'size-7 text-[11px]',
                'picker' => 'sm',
            ],
            'md' => [
                'text' => 'text-sm',
                'control' => 'h-8 text-sm',
                'day' => 'size-9 text-xs',
                'picker' => 'md',
            ],
            'lg' => [
                'text' => 'text-base',
                'control' => 'h-10 text-base',
                'day' => 'size-11 text-sm',
                'picker' => 'lg',
            ],
        ];
    }
}

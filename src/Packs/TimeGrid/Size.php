<?php

namespace Neura\Kit\Packs\TimeGrid;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the time grid.
     *
     * `label` is the class of the day and hour headers, `cell` the height of a one-hour
     * cell (finer steps shrink it when the hours run down the grid), `gap` the space
     * between cells. Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'label' => 'text-[11px]',
                'cell' => '1rem',
                'gap' => 'gap-px',
            ],
            'md' => [
                'label' => 'text-xs',
                'cell' => '1.375rem',
                'gap' => 'gap-0.5',
            ],
            'lg' => [
                'label' => 'text-sm',
                'cell' => '1.75rem',
                'gap' => 'gap-1',
            ],
        ];
    }
}

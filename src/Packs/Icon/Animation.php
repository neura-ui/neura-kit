<?php

namespace Neura\Kit\Packs\Icon;

use Neura\Kit\Packs\BasePack;

class Animation extends BasePack
{
    /**
     * Animation preset => default trigger.
     * The trigger decides when the animation plays and can be overridden per
     * instance via the `trigger` prop:
     *   loop  — plays continuously (spinners, ringing bell)
     *   hover — plays while the icon is hovered (micro-interactions)
     *   once  — plays a single time on mount (draw-on)
     */
    public static function default(): array
    {
        return [
            'spin' => 'loop',
            'pulse' => 'loop',
            'ring' => 'loop',
            'float' => 'loop',
            'blink' => 'loop',
            'beat' => 'hover',
            'bounce' => 'hover',
            'nudge' => 'hover',
            'open' => 'hover',
            'wiggle' => 'hover',
            'shake' => 'hover',
            'pop' => 'hover',
            'swing' => 'hover',
            'draw' => 'once',
        ];
    }
}

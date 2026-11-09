<?php

namespace Neura\Kit\Packs\Flow;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    /**
     * Card styles of the flow's nodes, each a class on the node (themes/flow.css).
     *
     *   elevated  surface, hairline edge and a soft shadow
     *   outline   no shadow, a firmer edge
     *   soft      tinted with the node's colour
     *   solid     filled with the node's colour, white icon
     *
     * A style added here needs its own `.flow-node.<class>` rules.
     */
    public static function default(): array
    {
        return [
            'elevated' => 'flow-v-elevated',
            'outline' => 'flow-v-outline',
            'soft' => 'flow-v-soft',
            'solid' => 'flow-v-solid',
        ];
    }
}

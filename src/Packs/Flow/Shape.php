<?php

namespace Neura\Kit\Packs\Flow;

use Neura\Kit\Packs\BasePack;

class Shape extends BasePack
{
    /**
     * Node shapes of the flow and their default size in canvas pixels.
     *
     * A node's own `width` / `height` win. Tiles (node, trigger, sub) carry their name
     * below the tile, so only the tile itself is measured here. A table grows with its rows;
     * its height here is for four.
     */
    public static function default(): array
    {
        return [
            'node' => ['width' => 96, 'height' => 96],
            'trigger' => ['width' => 96, 'height' => 96],
            'agent' => ['width' => 208, 'height' => 96],
            'sub' => ['width' => 72, 'height' => 72],
            'note' => ['width' => 240, 'height' => 160],
            'card' => ['width' => 208, 'height' => 64],
            'table' => ['width' => 240, 'height' => 162],
        ];
    }
}

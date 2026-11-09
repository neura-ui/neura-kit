<?php

namespace Neura\Kit\Packs\Flow;

use Neura\Kit\Packs\BasePack;

class Theme extends BasePack
{
    /**
     * Looks of the whole flow, each a class on the flow (themes/flow.css).
     *
     *   classic  n8n-style: square tiles with their name below, curved connections
     *   signal   capsules with a status light, circuit-like traces, data dots moving
     *            along the connections while a flow runs
     *
     * `edgeType` is the connection drawing when the flow sets none, `shape` the node shape
     * when the flow sets none (over the config's default: it is the theme's own look);
     * `shapes` replaces the size of the shapes the theme draws differently (Flow\Shape pack
     * for the rest).
     */
    public static function default(): array
    {
        return [
            'classic' => [
                'class' => 'flow-t-classic',
                'edgeType' => 'bezier',
                'shapes' => [],
            ],
            'signal' => [
                'class' => 'flow-t-signal',
                'edgeType' => 'smoothstep',
                'shape' => 'node',
                'shapes' => [
                    'node' => ['width' => 160, 'height' => 44],
                    'trigger' => ['width' => 160, 'height' => 44],
                    'agent' => ['width' => 232, 'height' => 96],
                    'sub' => ['width' => 40, 'height' => 40],
                    'note' => ['width' => 220, 'height' => 110],
                ],
            ],
        ];
    }
}

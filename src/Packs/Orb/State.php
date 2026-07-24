<?php

namespace Neura\Kit\Packs\Orb;

use Neura\Kit\Packs\BasePack;

class State extends BasePack
{
    /**
     * Each thinking state maps to a distinct dot geometry (rendered on canvas
     * by the `neuraOrb` JS component):
     *   orbits — particles on tilted orbits
     *   globe  — a scan meridian sweeps a dotted globe
     *   rubik  — latitude bands scramble in quarter turns
     *   wave   — a waveform rolls through latitude rings
     *   ribbon — an undulating multi-band sash
     *   morph  — a dotted outline morphs circle → triangle → square
     */
    public static function default(): array
    {
        return [
            'working' => 'orbits',
            'searching' => 'globe',
            'solving' => 'rubik',
            'listening' => 'wave',
            'composing' => 'ribbon',
            'shaping' => 'morph',
        ];
    }
}

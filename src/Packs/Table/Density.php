<?php

namespace Neura\Kit\Packs\Table;

use Neura\Kit\Packs\BasePack;

class Density extends BasePack
{
    public static function default(): array
    {
        return [
            'compact' => [
                'toolbar' => 'px-2.5 py-1.5',
                'th' => 'px-2.5 py-1.5 text-[11px]',
                'td' => 'px-2.5 py-1',
                'footer' => 'px-2.5 py-1.5',
                'text' => 'text-xs',
            ],
            'normal' => [
                'toolbar' => 'px-3 py-2',
                'th' => 'px-3 py-2 text-[11px]',
                'td' => 'px-3 py-2',
                'footer' => 'px-3 py-2',
                'text' => 'text-sm',
            ],
            'comfortable' => [
                'toolbar' => 'px-4 py-3',
                'th' => 'px-4 py-3 text-xs',
                'td' => 'px-4 py-3',
                'footer' => 'px-4 py-3',
                'text' => 'text-sm',
            ],
        ];
    }
}

<?php

namespace Neura\Kit\Packs;

class Shadow extends BasePack
{
    public static function default(): array
    {
        return [
            'none' => 'shadow-none',
            'sm' => 'shadow-sm',
            'base' => 'shadow',
            'md' => 'shadow-md',
            'lg' => 'shadow-lg',
            'xl' => 'shadow-xl',
            '2xl' => 'shadow-2xl',
        ];
    }
}

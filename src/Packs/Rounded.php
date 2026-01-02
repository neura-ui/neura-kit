<?php

namespace Neura\Kit\Packs;

class Rounded extends BasePack
{
    public static function default(): array
    {
        return [
            'none' => 'rounded-none',
            'sm' => 'rounded-sm',
            'md' => 'rounded-md',
            'lg' => 'rounded-lg',
            'xl' => 'rounded-xl',
            '2xl' => 'rounded-2xl',
            '3xl' => 'rounded-3xl',
            'full' => 'rounded-full',
        ];
    }
}

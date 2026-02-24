<?php

namespace Neura\Kit\Packs\Table;

use Neura\Kit\Packs\BasePack;

class Rounded extends BasePack
{
    public static function default(): array
    {
        return [
            'none' => [
                'wrapper' => 'rounded-none',
                'toolbar' => '',
                'footer' => '',
            ],
            'sm' => [
                'wrapper' => 'rounded-sm',
                'toolbar' => 'rounded-t-sm',
                'footer' => 'rounded-b-sm',
            ],
            'md' => [
                'wrapper' => 'rounded-md',
                'toolbar' => 'rounded-t-md',
                'footer' => 'rounded-b-md',
            ],
            'lg' => [
                'wrapper' => 'rounded-lg',
                'toolbar' => 'rounded-t-lg',
                'footer' => 'rounded-b-lg',
            ],
            'xl' => [
                'wrapper' => 'rounded-xl',
                'toolbar' => 'rounded-t-xl',
                'footer' => 'rounded-b-xl',
            ],
            '2xl' => [
                'wrapper' => 'rounded-2xl',
                'toolbar' => 'rounded-t-2xl',
                'footer' => 'rounded-b-2xl',
            ],
        ];
    }
}

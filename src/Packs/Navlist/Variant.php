<?php

namespace Neura\Kit\Packs\Navlist;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:bg-hover',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:bg-active',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
            ],
            'ghost' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
            ],
            'soft' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:bg-hover/70',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:bg-active',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
                'data-active-link:shadow-sm',
            ],
            'rail' => [
                'text-fg-secondary',
                '[&:not([data-active-link])]:hover:text-fg',
                'data-active-link:text-fg',
                'data-active-link:font-medium',
                'before:pointer-events-none before:absolute before:inset-y-1.5 before:left-0 before:w-0.5 before:rounded-full before:bg-transparent',
                'rtl:before:left-auto rtl:before:right-0',
                'data-active-link:before:bg-primary-500',
                '[:has([data-collapsed]_&)_&]:before:inset-y-2',
            ],
        ];
    }
}

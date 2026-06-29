<?php

namespace Neura\Kit\Packs\Navbar;

use Neura\Kit\Packs\BasePack;

class Item extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'base' => 'flex items-center justify-center dark:text-neutral-200 text-neutral-600 px-2 gap-x-1 py-1 rounded-box',
                'active' => 'data-active-link:bg-primary-50 dark:data-active-link:bg-primary-950/50 data-active-link:!text-primary-600 dark:data-active-link:!text-primary-400 data-active-link:[&_[data-slot=icon]]:!text-primary-600 dark:data-active-link:[&_[data-slot=icon]]:!text-primary-400',
                'hover' => '[&:not([data-active-link])]:hover:bg-primary-50 dark:[&:not([data-active-link])]:hover:bg-primary-950/50 [&:not([data-active-link])]:hover:!text-primary-600 dark:[&:not([data-active-link])]:hover:!text-primary-400 [&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-primary-600 dark:[&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-primary-400',
                'icon' => '[&_[data-slot=icon]]:dark:text-neutral-400 [&_[data-slot=icon]]:text-neutral-600 data-[active-link]:text-primary-600 dark:data-[active-link]:text-primary-400',
                'badge' => '[&:has([data-slot=badge])]:pr-1',
            ],
        ];
    }
}

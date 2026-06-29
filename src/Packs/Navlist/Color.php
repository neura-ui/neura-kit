<?php

namespace Neura\Kit\Packs\Navlist;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'neutral' => [],
            'primary' => [
                'text-primary-600 dark:text-primary-400',
                'hover:bg-primary-50 dark:hover:bg-primary-500/10',
                'hover:text-primary-700 dark:hover:text-primary-300',
                '[&_[data-slot=icon]]:text-primary-500 dark:[&_[data-slot=icon]]:text-primary-400',
            ],
            'success' => [
                'text-emerald-600 dark:text-emerald-400',
                'hover:bg-emerald-50 dark:hover:bg-emerald-500/10',
                'hover:text-emerald-700 dark:hover:text-emerald-300',
                '[&_[data-slot=icon]]:text-emerald-500 dark:[&_[data-slot=icon]]:text-emerald-400',
            ],
            'warning' => [
                'text-amber-600 dark:text-amber-400',
                'hover:bg-amber-50 dark:hover:bg-amber-500/10',
                'hover:text-amber-700 dark:hover:text-amber-300',
                '[&_[data-slot=icon]]:text-amber-500 dark:[&_[data-slot=icon]]:text-amber-400',
            ],
            'danger' => [
                'text-red-600 dark:text-red-400',
                'hover:bg-red-50 dark:hover:bg-red-500/10',
                'hover:text-red-700 dark:hover:text-red-300',
                '[&_[data-slot=icon]]:text-red-500 dark:[&_[data-slot=icon]]:text-red-400',
            ],
        ];
    }
}

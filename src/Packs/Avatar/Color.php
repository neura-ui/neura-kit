<?php

namespace Neura\Kit\Packs\Avatar;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'neutral' => 'bg-neutral-200 text-neutral-700 dark:bg-white/[0.10] dark:text-neutral-300 [&>[data-slot=icon]]:text-white!',
            'red' => 'bg-red-200 text-red-800 dark:bg-red-500/20 dark:text-red-300',
            'orange' => 'bg-orange-200 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300',
            'amber' => 'bg-amber-200 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
            'yellow' => 'bg-yellow-200 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300',
            'lime' => 'bg-lime-200 text-lime-800 dark:bg-lime-500/20 dark:text-lime-300',
            'green' => 'bg-green-200 text-green-800 dark:bg-green-500/20 dark:text-green-300',
            'emerald' => 'bg-emerald-200 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
            'teal' => 'bg-teal-200 text-teal-800 dark:bg-teal-500/20 dark:text-teal-300',
            'cyan' => 'bg-cyan-200 text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300',
            'sky' => 'bg-sky-200 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300',
            'blue' => 'bg-blue-200 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
            'indigo' => 'bg-indigo-200 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300',
            'violet' => 'bg-violet-200 text-violet-800 dark:bg-violet-500/20 dark:text-violet-300',
            'purple' => 'bg-purple-200 text-purple-800 dark:bg-purple-500/20 dark:text-purple-300',
            'fuchsia' => 'bg-fuchsia-200 text-fuchsia-800 dark:bg-fuchsia-500/20 dark:text-fuchsia-300',
            'pink' => 'bg-pink-200 text-pink-800 dark:bg-pink-500/20 dark:text-pink-300',
            'rose' => 'bg-rose-200 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
        ];
    }

    public static function autoColors(): array
    {
        return ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
    }
}


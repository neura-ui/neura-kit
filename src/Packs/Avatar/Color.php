<?php

namespace Neura\Kit\Packs\Avatar;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'neutral' => 'bg-neutral-200 dark:bg-neutral-500 [&>[data-slot=icon]]:text-white!',
            'red' => 'bg-red-400 text-red-800',
            'orange' => 'bg-orange-200 text-orange-800',
            'amber' => 'bg-amber-200 text-amber-800',
            'yellow' => 'bg-yellow-200 text-yellow-800',
            'lime' => 'bg-lime-200 text-lime-800',
            'green' => 'bg-green-200 text-green-800',
            'emerald' => 'bg-emerald-200 text-emerald-800',
            'teal' => 'bg-teal-200 text-teal-800',
            'cyan' => 'bg-cyan-200 text-cyan-800',
            'sky' => 'bg-sky-200 text-sky-800',
            'blue' => 'bg-blue-200 text-blue-800',
            'indigo' => 'bg-indigo-200 text-indigo-800',
            'violet' => 'bg-violet-200 text-violet-800',
            'purple' => 'bg-purple-200 text-purple-800',
            'fuchsia' => 'bg-fuchsia-200 text-fuchsia-800',
            'pink' => 'bg-pink-200 text-pink-800',
            'rose' => 'bg-rose-200 text-rose-800',
        ];
    }

    public static function autoColors(): array
    {
        return ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
    }
}

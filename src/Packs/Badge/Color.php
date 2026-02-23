<?php

namespace Neura\Kit\Packs\Badge;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'primary' => [
                'solid' => 'bg-primary-900 text-white dark:bg-primary-100 dark:text-primary-900',
                'outline' => 'text-primary-900 dark:text-primary-100 bg-primary-50 dark:bg-primary-500/10 border border-primary-300 dark:border-primary-700',
                'soft' => 'bg-primary-100 dark:bg-primary-500/10 text-primary-800 dark:text-primary-200',
            ],
            'secondary' => [
                'solid' => 'bg-secondary-200 dark:bg-secondary-700 text-secondary-800 dark:text-secondary-200',
                'outline' => 'text-secondary-700 dark:text-secondary-300 bg-secondary-50 dark:bg-secondary-500/10 border border-secondary-300 dark:border-secondary-500/30',
                'soft' => 'bg-secondary-100 dark:bg-secondary-500/10 text-secondary-700 dark:text-secondary-300',
            ],
            'danger' => [
                'solid' => 'bg-danger-600 text-white',
                'outline' => 'text-danger-700 dark:text-danger-400 bg-danger-50 dark:bg-danger-500/10 border border-danger-300 dark:border-danger-700',
                'soft' => 'bg-danger-100 dark:bg-danger-500/10 text-danger-700 dark:text-danger-400',
            ],
            'success' => [
                'solid' => 'bg-success-600 text-white',
                'outline' => 'text-success-700 dark:text-success-400 bg-success-50 dark:bg-success-500/10 border border-success-300 dark:border-success-700',
                'soft' => 'bg-success-100 dark:bg-success-500/10 text-success-700 dark:text-success-400',
            ],
            'warning' => [
                'solid' => 'bg-warning-500 text-white',
                'outline' => 'text-warning-700 dark:text-warning-400 bg-warning-50 dark:bg-warning-500/10 border border-warning-300 dark:border-warning-700',
                'soft' => 'bg-warning-100 dark:bg-warning-500/10 text-warning-700 dark:text-warning-400',
            ],
            'info' => [
                'solid' => 'bg-info-600 text-white',
                'outline' => 'text-info-700 dark:text-info-400 bg-info-50 dark:bg-info-500/10 border border-info-300 dark:border-info-700',
                'soft' => 'bg-info-100 dark:bg-info-500/10 text-info-700 dark:text-info-400',
            ],
        ];
    }

    public static function tailwindColors(): array
    {
        return [
            'red' => [
                'solid' => 'bg-red-500 text-white',
                'outline' => 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-700',
                'soft' => 'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-400',
            ],
            'orange' => [
                'solid' => 'bg-orange-500 text-white',
                'outline' => 'text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-500/10 border border-orange-300 dark:border-orange-700',
                'soft' => 'bg-orange-100 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400',
            ],
            'amber' => [
                'solid' => 'bg-amber-500 text-white',
                'outline' => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-700',
                'soft' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
            ],
            'yellow' => [
                'solid' => 'bg-yellow-500 text-white',
                'outline' => 'text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-300 dark:border-yellow-700',
                'soft' => 'bg-yellow-100 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
            ],
            'lime' => [
                'solid' => 'bg-lime-500 text-white',
                'outline' => 'text-lime-700 dark:text-lime-400 bg-lime-50 dark:bg-lime-500/10 border border-lime-300 dark:border-lime-700',
                'soft' => 'bg-lime-100 dark:bg-lime-500/10 text-lime-700 dark:text-lime-400',
            ],
            'green' => [
                'solid' => 'bg-green-500 text-white',
                'outline' => 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-500/10 border border-green-300 dark:border-green-700',
                'soft' => 'bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400',
            ],
            'emerald' => [
                'solid' => 'bg-emerald-500 text-white',
                'outline' => 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-700',
                'soft' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
            ],
            'teal' => [
                'solid' => 'bg-teal-500 text-white',
                'outline' => 'text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-500/10 border border-teal-300 dark:border-teal-700',
                'soft' => 'bg-teal-100 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400',
            ],
            'cyan' => [
                'solid' => 'bg-cyan-500 text-white',
                'outline' => 'text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-300 dark:border-cyan-700',
                'soft' => 'bg-cyan-100 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400',
            ],
            'sky' => [
                'solid' => 'bg-sky-500 text-white',
                'outline' => 'text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/10 border border-sky-300 dark:border-sky-700',
                'soft' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400',
            ],
            'blue' => [
                'solid' => 'bg-blue-500 text-white',
                'outline' => 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 border border-blue-300 dark:border-blue-700',
                'soft' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
            ],
            'indigo' => [
                'solid' => 'bg-indigo-500 text-white',
                'outline' => 'text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-300 dark:border-indigo-700',
                'soft' => 'bg-indigo-100 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400',
            ],
            'violet' => [
                'solid' => 'bg-violet-500 text-white',
                'outline' => 'text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-500/10 border border-violet-300 dark:border-violet-700',
                'soft' => 'bg-violet-100 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400',
            ],
            'purple' => [
                'solid' => 'bg-purple-500 text-white',
                'outline' => 'text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-500/10 border border-purple-300 dark:border-purple-700',
                'soft' => 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400',
            ],
            'fuchsia' => [
                'solid' => 'bg-fuchsia-500 text-white',
                'outline' => 'text-fuchsia-700 dark:text-fuchsia-400 bg-fuchsia-50 dark:bg-fuchsia-500/10 border border-fuchsia-300 dark:border-fuchsia-700',
                'soft' => 'bg-fuchsia-100 dark:bg-fuchsia-500/10 text-fuchsia-700 dark:text-fuchsia-400',
            ],
            'pink' => [
                'solid' => 'bg-pink-500 text-white',
                'outline' => 'text-pink-700 dark:text-pink-400 bg-pink-50 dark:bg-pink-500/10 border border-pink-300 dark:border-pink-700',
                'soft' => 'bg-pink-100 dark:bg-pink-500/10 text-pink-700 dark:text-pink-400',
            ],
            'rose' => [
                'solid' => 'bg-rose-500 text-white',
                'outline' => 'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border border-rose-300 dark:border-rose-700',
                'soft' => 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400',
            ],
        ];
    }

    public static function all(): array
    {
        return array_merge(static::default(), static::tailwindColors());
    }
}

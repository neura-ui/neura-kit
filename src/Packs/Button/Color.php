<?php

namespace Neura\Kit\Packs\Button;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'primary' => [
                'solid' => [
                    'base' => 'bg-primary-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-primary-700 dark:hover:bg-primary-500',
                ],
                'dark' => [
                    'base' => 'bg-primary-900 text-white [&_svg]:text-white dark:bg-primary-100 dark:text-primary-900 dark:[&_svg]:text-primary-900 shadow-sm',
                    'hover' => 'hover:bg-primary-800 dark:hover:bg-primary-200',
                ],
                'outline' => [
                    'base' => 'border border-primary-300 dark:border-primary-700 bg-white dark:bg-primary-950 text-primary-600 [&_svg]:text-primary-600 dark:text-primary-400 dark:[&_svg]:text-primary-400 shadow-sm',
                    'hover' => 'hover:bg-primary-50 dark:hover:bg-primary-900/30',
                ],
                'soft' => [
                    'base' => 'bg-primary-100 dark:bg-primary-950/50 text-primary-700 [&_svg]:text-primary-700 dark:text-primary-400 dark:[&_svg]:text-primary-400',
                    'hover' => 'hover:bg-primary-200 dark:hover:bg-primary-900/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-primary-600 [&_svg]:text-primary-600 dark:text-primary-400 dark:[&_svg]:text-primary-400',
                    'hover' => 'hover:bg-primary-50 dark:hover:bg-primary-950/30',
                ],
            ],
            'secondary' => [
                'solid' => [
                    'base' => 'bg-secondary-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-secondary-700 dark:hover:bg-secondary-500',
                ],
                'dark' => [
                    'base' => 'bg-secondary-900 text-white [&_svg]:text-white dark:bg-secondary-100 dark:text-secondary-900 dark:[&_svg]:text-secondary-900 shadow-sm',
                    'hover' => 'hover:bg-secondary-800 dark:hover:bg-secondary-200',
                ],
                'outline' => [
                    'base' => 'border border-secondary-300 dark:border-secondary-700 bg-white dark:bg-secondary-950 text-secondary-600 [&_svg]:text-secondary-600 dark:text-secondary-400 dark:[&_svg]:text-secondary-400 shadow-sm',
                    'hover' => 'hover:bg-secondary-50 dark:hover:bg-secondary-900/30',
                ],
                'soft' => [
                    'base' => 'bg-secondary-100 dark:bg-secondary-800/50 text-secondary-700 [&_svg]:text-secondary-700 dark:text-secondary-300 dark:[&_svg]:text-secondary-300',
                    'hover' => 'hover:bg-secondary-200 dark:hover:bg-secondary-700/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-secondary-600 [&_svg]:text-secondary-600 dark:text-secondary-400 dark:[&_svg]:text-secondary-400',
                    'hover' => 'hover:bg-secondary-100 dark:hover:bg-secondary-800',
                ],
            ],
            'danger' => [
                'solid' => [
                    'base' => 'bg-danger-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-danger-700 dark:hover:bg-danger-500',
                ],
                'dark' => [
                    'base' => 'bg-danger-900 text-white [&_svg]:text-white dark:bg-danger-100 dark:text-danger-900 dark:[&_svg]:text-danger-900 shadow-sm',
                    'hover' => 'hover:bg-danger-800 dark:hover:bg-danger-200',
                ],
                'outline' => [
                    'base' => 'border border-danger-300 dark:border-danger-700 bg-white dark:bg-danger-950 text-danger-600 [&_svg]:text-danger-600 dark:text-danger-400 dark:[&_svg]:text-danger-400 shadow-sm',
                    'hover' => 'hover:bg-danger-50 dark:hover:bg-danger-900/30',
                ],
                'soft' => [
                    'base' => 'bg-danger-100 dark:bg-danger-950/50 text-danger-700 [&_svg]:text-danger-700 dark:text-danger-400 dark:[&_svg]:text-danger-400',
                    'hover' => 'hover:bg-danger-200 dark:hover:bg-danger-900/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-danger-600 [&_svg]:text-danger-600 dark:text-danger-400 dark:[&_svg]:text-danger-400',
                    'hover' => 'hover:bg-danger-50 dark:hover:bg-danger-950/30',
                ],
            ],
            'success' => [
                'solid' => [
                    'base' => 'bg-success-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-success-700 dark:hover:bg-success-500',
                ],
                'dark' => [
                    'base' => 'bg-success-900 text-white [&_svg]:text-white dark:bg-success-100 dark:text-success-900 dark:[&_svg]:text-success-900 shadow-sm',
                    'hover' => 'hover:bg-success-800 dark:hover:bg-success-200',
                ],
                'outline' => [
                    'base' => 'border border-success-300 dark:border-success-700 bg-white dark:bg-success-950 text-success-600 [&_svg]:text-success-600 dark:text-success-400 dark:[&_svg]:text-success-400 shadow-sm',
                    'hover' => 'hover:bg-success-50 dark:hover:bg-success-900/30',
                ],
                'soft' => [
                    'base' => 'bg-success-100 dark:bg-success-950/50 text-success-700 [&_svg]:text-success-700 dark:text-success-400 dark:[&_svg]:text-success-400',
                    'hover' => 'hover:bg-success-200 dark:hover:bg-success-900/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-success-600 [&_svg]:text-success-600 dark:text-success-400 dark:[&_svg]:text-success-400',
                    'hover' => 'hover:bg-success-50 dark:hover:bg-success-950/30',
                ],
            ],
            'warning' => [
                'solid' => [
                    'base' => 'bg-warning-500 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-warning-600 dark:hover:bg-warning-400',
                ],
                'dark' => [
                    'base' => 'bg-warning-900 text-white [&_svg]:text-white dark:bg-warning-100 dark:text-warning-900 dark:[&_svg]:text-warning-900 shadow-sm',
                    'hover' => 'hover:bg-warning-800 dark:hover:bg-warning-200',
                ],
                'outline' => [
                    'base' => 'border border-warning-300 dark:border-warning-700 bg-white dark:bg-warning-950 text-warning-600 [&_svg]:text-warning-600 dark:text-warning-400 dark:[&_svg]:text-warning-400 shadow-sm',
                    'hover' => 'hover:bg-warning-50 dark:hover:bg-warning-900/30',
                ],
                'soft' => [
                    'base' => 'bg-warning-100 dark:bg-warning-950/50 text-warning-700 [&_svg]:text-warning-700 dark:text-warning-400 dark:[&_svg]:text-warning-400',
                    'hover' => 'hover:bg-warning-200 dark:hover:bg-warning-900/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-warning-600 [&_svg]:text-warning-600 dark:text-warning-400 dark:[&_svg]:text-warning-400',
                    'hover' => 'hover:bg-warning-50 dark:hover:bg-warning-950/30',
                ],
            ],
            'info' => [
                'solid' => [
                    'base' => 'bg-info-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-info-700 dark:hover:bg-info-500',
                ],
                'dark' => [
                    'base' => 'bg-info-900 text-white [&_svg]:text-white dark:bg-info-100 dark:text-info-900 dark:[&_svg]:text-info-900 shadow-sm',
                    'hover' => 'hover:bg-info-800 dark:hover:bg-info-200',
                ],
                'outline' => [
                    'base' => 'border border-info-300 dark:border-info-700 bg-white dark:bg-info-950 text-info-600 [&_svg]:text-info-600 dark:text-info-400 dark:[&_svg]:text-info-400 shadow-sm',
                    'hover' => 'hover:bg-info-50 dark:hover:bg-info-900/30',
                ],
                'soft' => [
                    'base' => 'bg-info-100 dark:bg-info-950/50 text-info-700 [&_svg]:text-info-700 dark:text-info-400 dark:[&_svg]:text-info-400',
                    'hover' => 'hover:bg-info-200 dark:hover:bg-info-900/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-info-600 [&_svg]:text-info-600 dark:text-info-400 dark:[&_svg]:text-info-400',
                    'hover' => 'hover:bg-info-50 dark:hover:bg-info-950/30',
                ],
            ],
        ];
    }

    public static function tailwindColors(): array
    {
        return [
            'slate' => [
                'solid' => [
                    'base' => 'bg-slate-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-slate-700 dark:hover:bg-slate-500',
                ],
                'dark' => [
                    'base' => 'bg-slate-900 text-white [&_svg]:text-white dark:bg-slate-100 dark:text-slate-900 dark:[&_svg]:text-slate-900 shadow-sm',
                    'hover' => 'hover:bg-slate-800 dark:hover:bg-slate-200',
                ],
                'outline' => [
                    'base' => 'border border-slate-300 dark:border-slate-700 bg-white dark:bg-neutral-950 text-slate-600 [&_svg]:text-slate-600 dark:text-slate-400 dark:[&_svg]:text-slate-400 shadow-sm',
                    'hover' => 'hover:bg-slate-50 dark:hover:bg-slate-950/20',
                ],
                'soft' => [
                    'base' => 'bg-slate-100 dark:bg-slate-950/30 text-slate-700 [&_svg]:text-slate-700 dark:text-slate-400 dark:[&_svg]:text-slate-400',
                    'hover' => 'hover:bg-slate-200 dark:hover:bg-slate-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-slate-600 [&_svg]:text-slate-600 dark:text-slate-400 dark:[&_svg]:text-slate-400',
                    'hover' => 'hover:bg-slate-50 dark:hover:bg-slate-950/20',
                ],
            ],
            'gray' => [
                'solid' => [
                    'base' => 'bg-gray-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-gray-700 dark:hover:bg-gray-500',
                ],
                'dark' => [
                    'base' => 'bg-gray-900 text-white [&_svg]:text-white dark:bg-gray-100 dark:text-gray-900 dark:[&_svg]:text-gray-900 shadow-sm',
                    'hover' => 'hover:bg-gray-800 dark:hover:bg-gray-200',
                ],
                'outline' => [
                    'base' => 'border border-gray-300 dark:border-gray-700 bg-white dark:bg-neutral-950 text-gray-600 [&_svg]:text-gray-600 dark:text-gray-400 dark:[&_svg]:text-gray-400 shadow-sm',
                    'hover' => 'hover:bg-gray-50 dark:hover:bg-gray-950/20',
                ],
                'soft' => [
                    'base' => 'bg-gray-100 dark:bg-gray-950/30 text-gray-700 [&_svg]:text-gray-700 dark:text-gray-400 dark:[&_svg]:text-gray-400',
                    'hover' => 'hover:bg-gray-200 dark:hover:bg-gray-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-gray-600 [&_svg]:text-gray-600 dark:text-gray-400 dark:[&_svg]:text-gray-400',
                    'hover' => 'hover:bg-gray-50 dark:hover:bg-gray-950/20',
                ],
            ],
            'zinc' => [
                'solid' => [
                    'base' => 'bg-zinc-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-zinc-700 dark:hover:bg-zinc-500',
                ],
                'dark' => [
                    'base' => 'bg-zinc-900 text-white [&_svg]:text-white dark:bg-zinc-100 dark:text-zinc-900 dark:[&_svg]:text-zinc-900 shadow-sm',
                    'hover' => 'hover:bg-zinc-800 dark:hover:bg-zinc-200',
                ],
                'outline' => [
                    'base' => 'border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-neutral-950 text-zinc-600 [&_svg]:text-zinc-600 dark:text-zinc-400 dark:[&_svg]:text-zinc-400 shadow-sm',
                    'hover' => 'hover:bg-zinc-50 dark:hover:bg-zinc-950/20',
                ],
                'soft' => [
                    'base' => 'bg-zinc-100 dark:bg-zinc-950/30 text-zinc-700 [&_svg]:text-zinc-700 dark:text-zinc-400 dark:[&_svg]:text-zinc-400',
                    'hover' => 'hover:bg-zinc-200 dark:hover:bg-zinc-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-zinc-600 [&_svg]:text-zinc-600 dark:text-zinc-400 dark:[&_svg]:text-zinc-400',
                    'hover' => 'hover:bg-zinc-50 dark:hover:bg-zinc-950/20',
                ],
            ],
            'neutral' => [
                'solid' => [
                    'base' => 'bg-neutral-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-neutral-700 dark:hover:bg-neutral-500',
                ],
                'dark' => [
                    'base' => 'bg-neutral-900 text-white [&_svg]:text-white dark:bg-neutral-100 dark:text-neutral-900 dark:[&_svg]:text-neutral-900 shadow-sm',
                    'hover' => 'hover:bg-neutral-800 dark:hover:bg-neutral-200',
                ],
                'outline' => [
                    'base' => 'border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-neutral-600 [&_svg]:text-neutral-600 dark:text-neutral-400 dark:[&_svg]:text-neutral-400 shadow-sm',
                    'hover' => 'hover:bg-neutral-50 dark:hover:bg-neutral-900',
                ],
                'soft' => [
                    'base' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-700 [&_svg]:text-neutral-700 dark:text-neutral-300 dark:[&_svg]:text-neutral-300',
                    'hover' => 'hover:bg-neutral-200 dark:hover:bg-neutral-700',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-neutral-600 [&_svg]:text-neutral-600 dark:text-neutral-400 dark:[&_svg]:text-neutral-400',
                    'hover' => 'hover:bg-neutral-100 dark:hover:bg-neutral-800',
                ],
            ],
            'stone' => [
                'solid' => [
                    'base' => 'bg-stone-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-stone-700 dark:hover:bg-stone-500',
                ],
                'dark' => [
                    'base' => 'bg-stone-900 text-white [&_svg]:text-white dark:bg-stone-100 dark:text-stone-900 dark:[&_svg]:text-stone-900 shadow-sm',
                    'hover' => 'hover:bg-stone-800 dark:hover:bg-stone-200',
                ],
                'outline' => [
                    'base' => 'border border-stone-300 dark:border-stone-700 bg-white dark:bg-neutral-950 text-stone-600 [&_svg]:text-stone-600 dark:text-stone-400 dark:[&_svg]:text-stone-400 shadow-sm',
                    'hover' => 'hover:bg-stone-50 dark:hover:bg-stone-950/20',
                ],
                'soft' => [
                    'base' => 'bg-stone-100 dark:bg-stone-950/30 text-stone-700 [&_svg]:text-stone-700 dark:text-stone-400 dark:[&_svg]:text-stone-400',
                    'hover' => 'hover:bg-stone-200 dark:hover:bg-stone-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-stone-600 [&_svg]:text-stone-600 dark:text-stone-400 dark:[&_svg]:text-stone-400',
                    'hover' => 'hover:bg-stone-50 dark:hover:bg-stone-950/20',
                ],
            ],
            'red' => [
                'solid' => [
                    'base' => 'bg-red-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-red-700 dark:hover:bg-red-500',
                ],
                'dark' => [
                    'base' => 'bg-red-900 text-white [&_svg]:text-white dark:bg-red-100 dark:text-red-900 dark:[&_svg]:text-red-900 shadow-sm',
                    'hover' => 'hover:bg-red-800 dark:hover:bg-red-200',
                ],
                'outline' => [
                    'base' => 'border border-red-300 dark:border-red-700 bg-white dark:bg-neutral-950 text-red-600 [&_svg]:text-red-600 dark:text-red-400 dark:[&_svg]:text-red-400 shadow-sm',
                    'hover' => 'hover:bg-red-50 dark:hover:bg-red-950/20',
                ],
                'soft' => [
                    'base' => 'bg-red-100 dark:bg-red-950/30 text-red-700 [&_svg]:text-red-700 dark:text-red-400 dark:[&_svg]:text-red-400',
                    'hover' => 'hover:bg-red-200 dark:hover:bg-red-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-red-600 [&_svg]:text-red-600 dark:text-red-400 dark:[&_svg]:text-red-400',
                    'hover' => 'hover:bg-red-50 dark:hover:bg-red-950/20',
                ],
            ],
            'orange' => [
                'solid' => [
                    'base' => 'bg-orange-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-orange-700 dark:hover:bg-orange-500',
                ],
                'dark' => [
                    'base' => 'bg-orange-900 text-white [&_svg]:text-white dark:bg-orange-100 dark:text-orange-900 dark:[&_svg]:text-orange-900 shadow-sm',
                    'hover' => 'hover:bg-orange-800 dark:hover:bg-orange-200',
                ],
                'outline' => [
                    'base' => 'border border-orange-300 dark:border-orange-700 bg-white dark:bg-neutral-950 text-orange-600 [&_svg]:text-orange-600 dark:text-orange-400 dark:[&_svg]:text-orange-400 shadow-sm',
                    'hover' => 'hover:bg-orange-50 dark:hover:bg-orange-950/20',
                ],
                'soft' => [
                    'base' => 'bg-orange-100 dark:bg-orange-950/30 text-orange-700 [&_svg]:text-orange-700 dark:text-orange-400 dark:[&_svg]:text-orange-400',
                    'hover' => 'hover:bg-orange-200 dark:hover:bg-orange-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-orange-600 [&_svg]:text-orange-600 dark:text-orange-400 dark:[&_svg]:text-orange-400',
                    'hover' => 'hover:bg-orange-50 dark:hover:bg-orange-950/20',
                ],
            ],
            'amber' => [
                'solid' => [
                    'base' => 'bg-amber-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-amber-700 dark:hover:bg-amber-500',
                ],
                'dark' => [
                    'base' => 'bg-amber-900 text-white [&_svg]:text-white dark:bg-amber-100 dark:text-amber-900 dark:[&_svg]:text-amber-900 shadow-sm',
                    'hover' => 'hover:bg-amber-800 dark:hover:bg-amber-200',
                ],
                'outline' => [
                    'base' => 'border border-amber-300 dark:border-amber-700 bg-white dark:bg-neutral-950 text-amber-600 [&_svg]:text-amber-600 dark:text-amber-400 dark:[&_svg]:text-amber-400 shadow-sm',
                    'hover' => 'hover:bg-amber-50 dark:hover:bg-amber-950/20',
                ],
                'soft' => [
                    'base' => 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 [&_svg]:text-amber-700 dark:text-amber-400 dark:[&_svg]:text-amber-400',
                    'hover' => 'hover:bg-amber-200 dark:hover:bg-amber-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-amber-600 [&_svg]:text-amber-600 dark:text-amber-400 dark:[&_svg]:text-amber-400',
                    'hover' => 'hover:bg-amber-50 dark:hover:bg-amber-950/20',
                ],
            ],
            'yellow' => [
                'solid' => [
                    'base' => 'bg-yellow-500 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-yellow-600 dark:hover:bg-yellow-400',
                ],
                'dark' => [
                    'base' => 'bg-yellow-900 text-white [&_svg]:text-white dark:bg-yellow-100 dark:text-yellow-900 dark:[&_svg]:text-yellow-900 shadow-sm',
                    'hover' => 'hover:bg-yellow-800 dark:hover:bg-yellow-200',
                ],
                'outline' => [
                    'base' => 'border border-yellow-300 dark:border-yellow-700 bg-white dark:bg-neutral-950 text-yellow-600 [&_svg]:text-yellow-600 dark:text-yellow-400 dark:[&_svg]:text-yellow-400 shadow-sm',
                    'hover' => 'hover:bg-yellow-50 dark:hover:bg-yellow-950/20',
                ],
                'soft' => [
                    'base' => 'bg-yellow-100 dark:bg-yellow-950/30 text-yellow-700 [&_svg]:text-yellow-700 dark:text-yellow-400 dark:[&_svg]:text-yellow-400',
                    'hover' => 'hover:bg-yellow-200 dark:hover:bg-yellow-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-yellow-600 [&_svg]:text-yellow-600 dark:text-yellow-400 dark:[&_svg]:text-yellow-400',
                    'hover' => 'hover:bg-yellow-50 dark:hover:bg-yellow-950/20',
                ],
            ],
            'lime' => [
                'solid' => [
                    'base' => 'bg-lime-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-lime-700 dark:hover:bg-lime-500',
                ],
                'dark' => [
                    'base' => 'bg-lime-900 text-white [&_svg]:text-white dark:bg-lime-100 dark:text-lime-900 dark:[&_svg]:text-lime-900 shadow-sm',
                    'hover' => 'hover:bg-lime-800 dark:hover:bg-lime-200',
                ],
                'outline' => [
                    'base' => 'border border-lime-300 dark:border-lime-700 bg-white dark:bg-neutral-950 text-lime-600 [&_svg]:text-lime-600 dark:text-lime-400 dark:[&_svg]:text-lime-400 shadow-sm',
                    'hover' => 'hover:bg-lime-50 dark:hover:bg-lime-950/20',
                ],
                'soft' => [
                    'base' => 'bg-lime-100 dark:bg-lime-950/30 text-lime-700 [&_svg]:text-lime-700 dark:text-lime-400 dark:[&_svg]:text-lime-400',
                    'hover' => 'hover:bg-lime-200 dark:hover:bg-lime-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-lime-600 [&_svg]:text-lime-600 dark:text-lime-400 dark:[&_svg]:text-lime-400',
                    'hover' => 'hover:bg-lime-50 dark:hover:bg-lime-950/20',
                ],
            ],
            'green' => [
                'solid' => [
                    'base' => 'bg-green-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-green-700 dark:hover:bg-green-500',
                ],
                'dark' => [
                    'base' => 'bg-green-900 text-white [&_svg]:text-white dark:bg-green-100 dark:text-green-900 dark:[&_svg]:text-green-900 shadow-sm',
                    'hover' => 'hover:bg-green-800 dark:hover:bg-green-200',
                ],
                'outline' => [
                    'base' => 'border border-green-300 dark:border-green-700 bg-white dark:bg-neutral-950 text-green-600 [&_svg]:text-green-600 dark:text-green-400 dark:[&_svg]:text-green-400 shadow-sm',
                    'hover' => 'hover:bg-green-50 dark:hover:bg-green-950/20',
                ],
                'soft' => [
                    'base' => 'bg-green-100 dark:bg-green-950/30 text-green-700 [&_svg]:text-green-700 dark:text-green-400 dark:[&_svg]:text-green-400',
                    'hover' => 'hover:bg-green-200 dark:hover:bg-green-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-green-600 [&_svg]:text-green-600 dark:text-green-400 dark:[&_svg]:text-green-400',
                    'hover' => 'hover:bg-green-50 dark:hover:bg-green-950/20',
                ],
            ],
            'emerald' => [
                'solid' => [
                    'base' => 'bg-emerald-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-emerald-700 dark:hover:bg-emerald-500',
                ],
                'dark' => [
                    'base' => 'bg-emerald-900 text-white [&_svg]:text-white dark:bg-emerald-100 dark:text-emerald-900 dark:[&_svg]:text-emerald-900 shadow-sm',
                    'hover' => 'hover:bg-emerald-800 dark:hover:bg-emerald-200',
                ],
                'outline' => [
                    'base' => 'border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-neutral-950 text-emerald-600 [&_svg]:text-emerald-600 dark:text-emerald-400 dark:[&_svg]:text-emerald-400 shadow-sm',
                    'hover' => 'hover:bg-emerald-50 dark:hover:bg-emerald-950/20',
                ],
                'soft' => [
                    'base' => 'bg-emerald-100 dark:bg-emerald-950/30 text-emerald-700 [&_svg]:text-emerald-700 dark:text-emerald-400 dark:[&_svg]:text-emerald-400',
                    'hover' => 'hover:bg-emerald-200 dark:hover:bg-emerald-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-emerald-600 [&_svg]:text-emerald-600 dark:text-emerald-400 dark:[&_svg]:text-emerald-400',
                    'hover' => 'hover:bg-emerald-50 dark:hover:bg-emerald-950/20',
                ],
            ],
            'teal' => [
                'solid' => [
                    'base' => 'bg-teal-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-teal-700 dark:hover:bg-teal-500',
                ],
                'dark' => [
                    'base' => 'bg-teal-900 text-white [&_svg]:text-white dark:bg-teal-100 dark:text-teal-900 dark:[&_svg]:text-teal-900 shadow-sm',
                    'hover' => 'hover:bg-teal-800 dark:hover:bg-teal-200',
                ],
                'outline' => [
                    'base' => 'border border-teal-300 dark:border-teal-700 bg-white dark:bg-neutral-950 text-teal-600 [&_svg]:text-teal-600 dark:text-teal-400 dark:[&_svg]:text-teal-400 shadow-sm',
                    'hover' => 'hover:bg-teal-50 dark:hover:bg-teal-950/20',
                ],
                'soft' => [
                    'base' => 'bg-teal-100 dark:bg-teal-950/30 text-teal-700 [&_svg]:text-teal-700 dark:text-teal-400 dark:[&_svg]:text-teal-400',
                    'hover' => 'hover:bg-teal-200 dark:hover:bg-teal-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-teal-600 [&_svg]:text-teal-600 dark:text-teal-400 dark:[&_svg]:text-teal-400',
                    'hover' => 'hover:bg-teal-50 dark:hover:bg-teal-950/20',
                ],
            ],
            'cyan' => [
                'solid' => [
                    'base' => 'bg-cyan-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-cyan-700 dark:hover:bg-cyan-500',
                ],
                'dark' => [
                    'base' => 'bg-cyan-900 text-white [&_svg]:text-white dark:bg-cyan-100 dark:text-cyan-900 dark:[&_svg]:text-cyan-900 shadow-sm',
                    'hover' => 'hover:bg-cyan-800 dark:hover:bg-cyan-200',
                ],
                'outline' => [
                    'base' => 'border border-cyan-300 dark:border-cyan-700 bg-white dark:bg-neutral-950 text-cyan-600 [&_svg]:text-cyan-600 dark:text-cyan-400 dark:[&_svg]:text-cyan-400 shadow-sm',
                    'hover' => 'hover:bg-cyan-50 dark:hover:bg-cyan-950/20',
                ],
                'soft' => [
                    'base' => 'bg-cyan-100 dark:bg-cyan-950/30 text-cyan-700 [&_svg]:text-cyan-700 dark:text-cyan-400 dark:[&_svg]:text-cyan-400',
                    'hover' => 'hover:bg-cyan-200 dark:hover:bg-cyan-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-cyan-600 [&_svg]:text-cyan-600 dark:text-cyan-400 dark:[&_svg]:text-cyan-400',
                    'hover' => 'hover:bg-cyan-50 dark:hover:bg-cyan-950/20',
                ],
            ],
            'sky' => [
                'solid' => [
                    'base' => 'bg-sky-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-sky-700 dark:hover:bg-sky-500',
                ],
                'dark' => [
                    'base' => 'bg-sky-900 text-white [&_svg]:text-white dark:bg-sky-100 dark:text-sky-900 dark:[&_svg]:text-sky-900 shadow-sm',
                    'hover' => 'hover:bg-sky-800 dark:hover:bg-sky-200',
                ],
                'outline' => [
                    'base' => 'border border-sky-300 dark:border-sky-700 bg-white dark:bg-neutral-950 text-sky-600 [&_svg]:text-sky-600 dark:text-sky-400 dark:[&_svg]:text-sky-400 shadow-sm',
                    'hover' => 'hover:bg-sky-50 dark:hover:bg-sky-950/20',
                ],
                'soft' => [
                    'base' => 'bg-sky-100 dark:bg-sky-950/30 text-sky-700 [&_svg]:text-sky-700 dark:text-sky-400 dark:[&_svg]:text-sky-400',
                    'hover' => 'hover:bg-sky-200 dark:hover:bg-sky-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-sky-600 [&_svg]:text-sky-600 dark:text-sky-400 dark:[&_svg]:text-sky-400',
                    'hover' => 'hover:bg-sky-50 dark:hover:bg-sky-950/20',
                ],
            ],
            'blue' => [
                'solid' => [
                    'base' => 'bg-blue-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-blue-700 dark:hover:bg-blue-500',
                ],
                'dark' => [
                    'base' => 'bg-blue-900 text-white [&_svg]:text-white dark:bg-blue-100 dark:text-blue-900 dark:[&_svg]:text-blue-900 shadow-sm',
                    'hover' => 'hover:bg-blue-800 dark:hover:bg-blue-200',
                ],
                'outline' => [
                    'base' => 'border border-blue-300 dark:border-blue-700 bg-white dark:bg-neutral-950 text-blue-600 [&_svg]:text-blue-600 dark:text-blue-400 dark:[&_svg]:text-blue-400 shadow-sm',
                    'hover' => 'hover:bg-blue-50 dark:hover:bg-blue-950/20',
                ],
                'soft' => [
                    'base' => 'bg-blue-100 dark:bg-blue-950/30 text-blue-700 [&_svg]:text-blue-700 dark:text-blue-400 dark:[&_svg]:text-blue-400',
                    'hover' => 'hover:bg-blue-200 dark:hover:bg-blue-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-blue-600 [&_svg]:text-blue-600 dark:text-blue-400 dark:[&_svg]:text-blue-400',
                    'hover' => 'hover:bg-blue-50 dark:hover:bg-blue-950/20',
                ],
            ],
            'indigo' => [
                'solid' => [
                    'base' => 'bg-indigo-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-indigo-700 dark:hover:bg-indigo-500',
                ],
                'dark' => [
                    'base' => 'bg-indigo-900 text-white [&_svg]:text-white dark:bg-indigo-100 dark:text-indigo-900 dark:[&_svg]:text-indigo-900 shadow-sm',
                    'hover' => 'hover:bg-indigo-800 dark:hover:bg-indigo-200',
                ],
                'outline' => [
                    'base' => 'border border-indigo-300 dark:border-indigo-700 bg-white dark:bg-neutral-950 text-indigo-600 [&_svg]:text-indigo-600 dark:text-indigo-400 dark:[&_svg]:text-indigo-400 shadow-sm',
                    'hover' => 'hover:bg-indigo-50 dark:hover:bg-indigo-950/20',
                ],
                'soft' => [
                    'base' => 'bg-indigo-100 dark:bg-indigo-950/30 text-indigo-700 [&_svg]:text-indigo-700 dark:text-indigo-400 dark:[&_svg]:text-indigo-400',
                    'hover' => 'hover:bg-indigo-200 dark:hover:bg-indigo-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-indigo-600 [&_svg]:text-indigo-600 dark:text-indigo-400 dark:[&_svg]:text-indigo-400',
                    'hover' => 'hover:bg-indigo-50 dark:hover:bg-indigo-950/20',
                ],
            ],
            'violet' => [
                'solid' => [
                    'base' => 'bg-violet-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-violet-700 dark:hover:bg-violet-500',
                ],
                'dark' => [
                    'base' => 'bg-violet-900 text-white [&_svg]:text-white dark:bg-violet-100 dark:text-violet-900 dark:[&_svg]:text-violet-900 shadow-sm',
                    'hover' => 'hover:bg-violet-800 dark:hover:bg-violet-200',
                ],
                'outline' => [
                    'base' => 'border border-violet-300 dark:border-violet-700 bg-white dark:bg-neutral-950 text-violet-600 [&_svg]:text-violet-600 dark:text-violet-400 dark:[&_svg]:text-violet-400 shadow-sm',
                    'hover' => 'hover:bg-violet-50 dark:hover:bg-violet-950/20',
                ],
                'soft' => [
                    'base' => 'bg-violet-100 dark:bg-violet-950/30 text-violet-700 [&_svg]:text-violet-700 dark:text-violet-400 dark:[&_svg]:text-violet-400',
                    'hover' => 'hover:bg-violet-200 dark:hover:bg-violet-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-violet-600 [&_svg]:text-violet-600 dark:text-violet-400 dark:[&_svg]:text-violet-400',
                    'hover' => 'hover:bg-violet-50 dark:hover:bg-violet-950/20',
                ],
            ],
            'purple' => [
                'solid' => [
                    'base' => 'bg-purple-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-purple-700 dark:hover:bg-purple-500',
                ],
                'dark' => [
                    'base' => 'bg-purple-900 text-white [&_svg]:text-white dark:bg-purple-100 dark:text-purple-900 dark:[&_svg]:text-purple-900 shadow-sm',
                    'hover' => 'hover:bg-purple-800 dark:hover:bg-purple-200',
                ],
                'outline' => [
                    'base' => 'border border-purple-300 dark:border-purple-700 bg-white dark:bg-neutral-950 text-purple-600 [&_svg]:text-purple-600 dark:text-purple-400 dark:[&_svg]:text-purple-400 shadow-sm',
                    'hover' => 'hover:bg-purple-50 dark:hover:bg-purple-950/20',
                ],
                'soft' => [
                    'base' => 'bg-purple-100 dark:bg-purple-950/30 text-purple-700 [&_svg]:text-purple-700 dark:text-purple-400 dark:[&_svg]:text-purple-400',
                    'hover' => 'hover:bg-purple-200 dark:hover:bg-purple-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-purple-600 [&_svg]:text-purple-600 dark:text-purple-400 dark:[&_svg]:text-purple-400',
                    'hover' => 'hover:bg-purple-50 dark:hover:bg-purple-950/20',
                ],
            ],
            'fuchsia' => [
                'solid' => [
                    'base' => 'bg-fuchsia-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-fuchsia-700 dark:hover:bg-fuchsia-500',
                ],
                'dark' => [
                    'base' => 'bg-fuchsia-900 text-white [&_svg]:text-white dark:bg-fuchsia-100 dark:text-fuchsia-900 dark:[&_svg]:text-fuchsia-900 shadow-sm',
                    'hover' => 'hover:bg-fuchsia-800 dark:hover:bg-fuchsia-200',
                ],
                'outline' => [
                    'base' => 'border border-fuchsia-300 dark:border-fuchsia-700 bg-white dark:bg-neutral-950 text-fuchsia-600 [&_svg]:text-fuchsia-600 dark:text-fuchsia-400 dark:[&_svg]:text-fuchsia-400 shadow-sm',
                    'hover' => 'hover:bg-fuchsia-50 dark:hover:bg-fuchsia-950/20',
                ],
                'soft' => [
                    'base' => 'bg-fuchsia-100 dark:bg-fuchsia-950/30 text-fuchsia-700 [&_svg]:text-fuchsia-700 dark:text-fuchsia-400 dark:[&_svg]:text-fuchsia-400',
                    'hover' => 'hover:bg-fuchsia-200 dark:hover:bg-fuchsia-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-fuchsia-600 [&_svg]:text-fuchsia-600 dark:text-fuchsia-400 dark:[&_svg]:text-fuchsia-400',
                    'hover' => 'hover:bg-fuchsia-50 dark:hover:bg-fuchsia-950/20',
                ],
            ],
            'pink' => [
                'solid' => [
                    'base' => 'bg-pink-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-pink-700 dark:hover:bg-pink-500',
                ],
                'dark' => [
                    'base' => 'bg-pink-900 text-white [&_svg]:text-white dark:bg-pink-100 dark:text-pink-900 dark:[&_svg]:text-pink-900 shadow-sm',
                    'hover' => 'hover:bg-pink-800 dark:hover:bg-pink-200',
                ],
                'outline' => [
                    'base' => 'border border-pink-300 dark:border-pink-700 bg-white dark:bg-neutral-950 text-pink-600 [&_svg]:text-pink-600 dark:text-pink-400 dark:[&_svg]:text-pink-400 shadow-sm',
                    'hover' => 'hover:bg-pink-50 dark:hover:bg-pink-950/20',
                ],
                'soft' => [
                    'base' => 'bg-pink-100 dark:bg-pink-950/30 text-pink-700 [&_svg]:text-pink-700 dark:text-pink-400 dark:[&_svg]:text-pink-400',
                    'hover' => 'hover:bg-pink-200 dark:hover:bg-pink-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-pink-600 [&_svg]:text-pink-600 dark:text-pink-400 dark:[&_svg]:text-pink-400',
                    'hover' => 'hover:bg-pink-50 dark:hover:bg-pink-950/20',
                ],
            ],
            'rose' => [
                'solid' => [
                    'base' => 'bg-rose-600 text-white [&_svg]:text-white shadow-sm',
                    'hover' => 'hover:bg-rose-700 dark:hover:bg-rose-500',
                ],
                'dark' => [
                    'base' => 'bg-rose-900 text-white [&_svg]:text-white dark:bg-rose-100 dark:text-rose-900 dark:[&_svg]:text-rose-900 shadow-sm',
                    'hover' => 'hover:bg-rose-800 dark:hover:bg-rose-200',
                ],
                'outline' => [
                    'base' => 'border border-rose-300 dark:border-rose-700 bg-white dark:bg-neutral-950 text-rose-600 [&_svg]:text-rose-600 dark:text-rose-400 dark:[&_svg]:text-rose-400 shadow-sm',
                    'hover' => 'hover:bg-rose-50 dark:hover:bg-rose-950/20',
                ],
                'soft' => [
                    'base' => 'bg-rose-100 dark:bg-rose-950/30 text-rose-700 [&_svg]:text-rose-700 dark:text-rose-400 dark:[&_svg]:text-rose-400',
                    'hover' => 'hover:bg-rose-200 dark:hover:bg-rose-950/50',
                ],
                'ghost' => [
                    'base' => 'bg-transparent text-rose-600 [&_svg]:text-rose-600 dark:text-rose-400 dark:[&_svg]:text-rose-400',
                    'hover' => 'hover:bg-rose-50 dark:hover:bg-rose-950/20',
                ],
                ],
            ];
    }

    public static function all(): array
    {
        return array_merge(static::default(), static::tailwindColors());
    }
}

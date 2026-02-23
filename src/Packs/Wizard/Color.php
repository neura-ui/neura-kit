<?php

namespace Neura\Kit\Packs\Wizard;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'neutral' => [
                'active' => 'bg-neutral-900 dark:bg-white text-white dark:text-neutral-900',
                'activeTab' => 'border-neutral-800 dark:border-neutral-200 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-50 shadow-sm',
                'completed' => 'bg-transparent border-neutral-400 dark:border-white/25 text-neutral-500 dark:text-neutral-400',
                'connector' => 'bg-neutral-400 dark:bg-white/25',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-fg-secondary',
            ],
            'primary' => [
                'active' => 'bg-primary-500 dark:bg-primary-500 text-white',
                'activeTab' => 'border-primary-600 dark:border-primary-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-primary-100 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300 shadow-sm',
                'completed' => 'bg-transparent border-primary-300 dark:border-primary-500/40 text-primary-500 dark:text-primary-400',
                'connector' => 'bg-primary-300 dark:bg-primary-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-primary-500 dark:text-primary-400',
            ],
            'secondary' => [
                'active' => 'bg-secondary-500 dark:bg-secondary-500 text-white',
                'activeTab' => 'border-secondary-600 dark:border-secondary-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-secondary-100 dark:bg-secondary-500/10 text-secondary-700 dark:text-secondary-300 shadow-sm',
                'completed' => 'bg-transparent border-secondary-300 dark:border-secondary-500/40 text-secondary-500 dark:text-secondary-400',
                'connector' => 'bg-secondary-300 dark:bg-secondary-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-secondary-500 dark:text-secondary-400',
            ],
            'success' => [
                'active' => 'bg-success-500 dark:bg-success-500 text-white',
                'activeTab' => 'border-success-600 dark:border-success-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-success-100 dark:bg-success-500/10 text-success-700 dark:text-success-300 shadow-sm',
                'completed' => 'bg-transparent border-success-300 dark:border-success-500/40 text-success-500 dark:text-success-400',
                'connector' => 'bg-success-300 dark:bg-success-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-success-500 dark:text-success-400',
            ],
            'danger' => [
                'active' => 'bg-danger-500 dark:bg-danger-500 text-white',
                'activeTab' => 'border-danger-600 dark:border-danger-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-danger-100 dark:bg-danger-500/10 text-danger-700 dark:text-danger-300 shadow-sm',
                'completed' => 'bg-transparent border-danger-300 dark:border-danger-500/40 text-danger-500 dark:text-danger-400',
                'connector' => 'bg-danger-300 dark:bg-danger-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-danger-500 dark:text-danger-400',
            ],
            'warning' => [
                'active' => 'bg-warning-500 dark:bg-warning-500 text-white',
                'activeTab' => 'border-warning-500 dark:border-warning-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-warning-100 dark:bg-warning-500/10 text-warning-700 dark:text-warning-400 shadow-sm',
                'completed' => 'bg-transparent border-warning-300 dark:border-warning-500/40 text-warning-500 dark:text-warning-400',
                'connector' => 'bg-warning-300 dark:bg-warning-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-warning-500 dark:text-warning-400',
            ],
            'info' => [
                'active' => 'bg-info-500 dark:bg-info-500 text-white',
                'activeTab' => 'border-info-600 dark:border-info-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-info-100 dark:bg-info-500/10 text-info-700 dark:text-info-300 shadow-sm',
                'completed' => 'bg-transparent border-info-300 dark:border-info-500/40 text-info-500 dark:text-info-400',
                'connector' => 'bg-info-300 dark:bg-info-500/40',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-info-500 dark:text-info-400',
            ],
        ];
    }

    public static function tailwindColors(): array
    {
        $colors = ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
        $result = [];

        foreach ($colors as $c) {
            $result[$c] = [
                'active' => "bg-{$c}-500 dark:bg-{$c}-500 text-white",
                'activeTab' => "border-{$c}-600 dark:border-{$c}-500 text-neutral-900 dark:text-neutral-50",
                'activePill' => "bg-{$c}-100 dark:bg-{$c}-500/10 text-{$c}-700 dark:text-{$c}-400 shadow-sm",
                'completed' => "bg-transparent border-{$c}-300 dark:border-{$c}-500/40 text-{$c}-500 dark:text-{$c}-400",
                'connector' => "bg-{$c}-300 dark:bg-{$c}-500/40",
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => "text-{$c}-500 dark:text-{$c}-400",
            ];
        }

        return $result;
    }

    public static function all(): array
    {
        return array_merge(static::default(), static::tailwindColors());
    }
}

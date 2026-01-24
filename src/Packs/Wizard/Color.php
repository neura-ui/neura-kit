<?php

namespace Neura\Kit\Packs\Wizard;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'neutral' => [
                'active' => 'bg-neutral-800 dark:bg-neutral-200 border-neutral-800 dark:border-neutral-200 text-white dark:text-neutral-900',
                'activeTab' => 'border-neutral-800 dark:border-neutral-200 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-white dark:bg-neutral-950 text-neutral-900 dark:text-neutral-50 shadow-sm',
                'completed' => 'bg-neutral-100 dark:bg-neutral-800 border-neutral-400 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300',
                'connector' => 'bg-neutral-600 dark:bg-neutral-400',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-neutral-700 dark:text-neutral-300',
            ],
            'primary' => [
                'active' => 'bg-primary-600 dark:bg-primary-500 border-primary-600 dark:border-primary-500 text-white',
                'activeTab' => 'border-primary-600 dark:border-primary-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 shadow-sm',
                'completed' => 'bg-primary-50 dark:bg-primary-950/30 border-primary-500 dark:border-primary-500 text-primary-600 dark:text-primary-400',
                'connector' => 'bg-primary-600 dark:bg-primary-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-primary-600 dark:text-primary-400',
            ],
            'secondary' => [
                'active' => 'bg-secondary-600 dark:bg-secondary-500 border-secondary-600 dark:border-secondary-500 text-white',
                'activeTab' => 'border-secondary-600 dark:border-secondary-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300 shadow-sm',
                'completed' => 'bg-secondary-100 dark:bg-secondary-900/50 border-secondary-500 dark:border-secondary-500 text-secondary-700 dark:text-secondary-300',
                'connector' => 'bg-secondary-600 dark:bg-secondary-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-secondary-700 dark:text-secondary-300',
            ],
            'success' => [
                'active' => 'bg-success-600 dark:bg-success-500 border-success-600 dark:border-success-500 text-white',
                'activeTab' => 'border-success-600 dark:border-success-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-success-100 dark:bg-success-950/30 text-success-700 dark:text-success-300 shadow-sm',
                'completed' => 'bg-success-50 dark:bg-success-950/30 border-success-500 dark:border-success-500 text-success-600 dark:text-success-400',
                'connector' => 'bg-success-600 dark:bg-success-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-success-600 dark:text-success-400',
            ],
            'danger' => [
                'active' => 'bg-danger-600 dark:bg-danger-500 border-danger-600 dark:border-danger-500 text-white',
                'activeTab' => 'border-danger-600 dark:border-danger-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-danger-100 dark:bg-danger-950/30 text-danger-700 dark:text-danger-300 shadow-sm',
                'completed' => 'bg-danger-50 dark:bg-danger-950/30 border-danger-500 dark:border-danger-500 text-danger-600 dark:text-danger-400',
                'connector' => 'bg-danger-600 dark:bg-danger-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-danger-600 dark:text-danger-400',
            ],
            'warning' => [
                'active' => 'bg-warning-500 dark:bg-warning-500 border-warning-500 dark:border-warning-500 text-white',
                'activeTab' => 'border-warning-500 dark:border-warning-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-warning-100 dark:bg-warning-950/30 text-warning-700 dark:text-warning-400 shadow-sm',
                'completed' => 'bg-warning-50 dark:bg-warning-950/30 border-warning-500 dark:border-warning-500 text-warning-600 dark:text-warning-400',
                'connector' => 'bg-warning-500 dark:bg-warning-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-warning-600 dark:text-warning-400',
            ],
            'info' => [
                'active' => 'bg-info-600 dark:bg-info-500 border-info-600 dark:border-info-500 text-white',
                'activeTab' => 'border-info-600 dark:border-info-500 text-neutral-900 dark:text-neutral-50',
                'activePill' => 'bg-info-100 dark:bg-info-950/30 text-info-700 dark:text-info-300 shadow-sm',
                'completed' => 'bg-info-50 dark:bg-info-950/30 border-info-500 dark:border-info-500 text-info-600 dark:text-info-400',
                'connector' => 'bg-info-600 dark:bg-info-500',
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => 'text-info-600 dark:text-info-400',
            ],
        ];
    }

    public static function tailwindColors(): array
    {
        $colors = ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
        $result = [];

        foreach ($colors as $c) {
            $result[$c] = [
                'active' => "bg-{$c}-600 dark:bg-{$c}-500 border-{$c}-600 dark:border-{$c}-500 text-white",
                'activeTab' => "border-{$c}-600 dark:border-{$c}-500 text-neutral-900 dark:text-neutral-50",
                'activePill' => "bg-{$c}-100 dark:bg-{$c}-950/30 text-{$c}-700 dark:text-{$c}-400 shadow-sm",
                'completed' => "bg-{$c}-50 dark:bg-{$c}-950/30 border-{$c}-500 dark:border-{$c}-500 text-{$c}-600 dark:text-{$c}-400",
                'connector' => "bg-{$c}-600 dark:bg-{$c}-500",
                'labelActive' => 'text-neutral-900 dark:text-neutral-50',
                'labelCompleted' => "text-{$c}-600 dark:text-{$c}-400",
            ];
        }

        return $result;
    }

    public static function all(): array
    {
        return array_merge(static::default(), static::tailwindColors());
    }
}

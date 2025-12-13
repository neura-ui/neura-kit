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
                'outline' => 'text-primary-900 dark:text-primary-100 bg-primary-50 dark:bg-primary-950 border border-primary-300 dark:border-primary-700',
                'soft' => 'bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200',
            ],
            'secondary' => [
                'solid' => 'bg-secondary-200 dark:bg-secondary-700 text-secondary-800 dark:text-secondary-200',
                'outline' => 'text-secondary-700 dark:text-secondary-300 bg-secondary-50 dark:bg-secondary-900 border border-secondary-300 dark:border-secondary-600',
                'soft' => 'bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300',
            ],
            'danger' => [
                'solid' => 'bg-danger-600 text-white',
                'outline' => 'text-danger-700 dark:text-danger-400 bg-danger-50 dark:bg-danger-950/50 border border-danger-300 dark:border-danger-700',
                'soft' => 'bg-danger-100 dark:bg-danger-950/50 text-danger-700 dark:text-danger-400',
            ],
            'success' => [
                'solid' => 'bg-success-600 text-white',
                'outline' => 'text-success-700 dark:text-success-400 bg-success-50 dark:bg-success-950/50 border border-success-300 dark:border-success-700',
                'soft' => 'bg-success-100 dark:bg-success-950/50 text-success-700 dark:text-success-400',
            ],
            'warning' => [
                'solid' => 'bg-warning-500 text-white',
                'outline' => 'text-warning-700 dark:text-warning-400 bg-warning-50 dark:bg-warning-950/50 border border-warning-300 dark:border-warning-700',
                'soft' => 'bg-warning-100 dark:bg-warning-950/50 text-warning-700 dark:text-warning-400',
            ],
            'info' => [
                'solid' => 'bg-info-600 text-white',
                'outline' => 'text-info-700 dark:text-info-400 bg-info-50 dark:bg-info-950/50 border border-info-300 dark:border-info-700',
                'soft' => 'bg-info-100 dark:bg-info-950/50 text-info-700 dark:text-info-400',
            ],
        ];
    }

    public static function tailwindColors(): array
    {
        $colors = ['red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
        $result = [];

        foreach ($colors as $color) {
            $result[$color] = [
                'solid' => "bg-{$color}-500 text-white",
                'outline' => "text-{$color}-700 dark:text-{$color}-400 bg-{$color}-50 dark:bg-{$color}-950/50 border border-{$color}-300 dark:border-{$color}-700",
                'soft' => "bg-{$color}-100 dark:bg-{$color}-950/30 text-{$color}-700 dark:text-{$color}-400",
            ];
        }

        return $result;
    }

    public static function all(): array
    {
        return array_merge(static::default(), static::tailwindColors());
    }
}

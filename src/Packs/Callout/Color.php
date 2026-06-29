<?php

namespace Neura\Kit\Packs\Callout;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'primary' => [
                'container' => 'bg-primary-50 dark:bg-primary-950/60 border-primary-200 dark:border-primary-700/60',
                'icon' => 'text-primary-600 dark:text-primary-400',
            ],
            'secondary' => [
                'container' => 'bg-secondary-50 dark:bg-secondary-900/50 border-secondary-200 dark:border-secondary-700/60',
                'icon' => 'text-secondary-600 dark:text-secondary-400',
            ],
            'success' => [
                'container' => 'bg-success-50 dark:bg-success-950/60 border-success-300 dark:border-success-700/60',
                'icon' => 'text-success-600 dark:text-success-400',
            ],
            'warning' => [
                'container' => 'bg-warning-50 dark:bg-warning-950/60 border-warning-300 dark:border-warning-600/60',
                'icon' => 'text-warning-600 dark:text-warning-400',
            ],
            'danger' => [
                'container' => 'bg-danger-50 dark:bg-danger-950/60 border-danger-300 dark:border-danger-700/60',
                'icon' => 'text-danger-600 dark:text-danger-400',
            ],
            'info' => [
                'container' => 'bg-info-50 dark:bg-info-950/60 border-info-300 dark:border-info-700/60',
                'icon' => 'text-info-600 dark:text-info-400',
            ],
        ];
    }
}

<?php

namespace Neura\Kit\Packs\Alert;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'info' => [
                'container' => 'bg-info-50 dark:bg-info-950/50 border-info-200 dark:border-info-800',
                'icon' => 'text-info-500',
                'iconName' => 'information-circle',
            ],
            'success' => [
                'container' => 'bg-success-50 dark:bg-success-950/50 border-success-200 dark:border-success-800',
                'icon' => 'text-success-500',
                'iconName' => 'check-circle',
            ],
            'warning' => [
                'container' => 'bg-warning-50 dark:bg-warning-950/50 border-warning-200 dark:border-warning-800',
                'icon' => 'text-warning-500',
                'iconName' => 'exclamation-triangle',
            ],
            'danger' => [
                'container' => 'bg-danger-50 dark:bg-danger-950/50 border-danger-200 dark:border-danger-800',
                'icon' => 'text-danger-500',
                'iconName' => 'exclamation-circle',
            ],
            'error' => [
                'container' => 'bg-danger-50 dark:bg-danger-950/50 border-danger-200 dark:border-danger-800',
                'icon' => 'text-danger-500',
                'iconName' => 'exclamation-circle',
            ],
        ];
    }
}

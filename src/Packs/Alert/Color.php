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
            'blue' => [
                'container' => 'bg-blue-50 dark:bg-blue-950/50 border-blue-200 dark:border-blue-800',
                'icon' => 'text-blue-500',
                'iconName' => 'information-circle',
            ],
            'green' => [
                'container' => 'bg-green-50 dark:bg-green-950/50 border-green-200 dark:border-green-800',
                'icon' => 'text-green-500',
                'iconName' => 'check-circle',
            ],
            'yellow' => [
                'container' => 'bg-yellow-50 dark:bg-yellow-950/50 border-yellow-200 dark:border-yellow-800',
                'icon' => 'text-yellow-500',
                'iconName' => 'exclamation-triangle',
            ],
            'orange' => [
                'container' => 'bg-orange-50 dark:bg-orange-950/50 border-orange-200 dark:border-orange-800',
                'icon' => 'text-orange-500',
                'iconName' => 'exclamation-triangle',
            ],
            'red' => [
                'container' => 'bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800',
                'icon' => 'text-red-500',
                'iconName' => 'exclamation-circle',
            ],
            'purple' => [
                'container' => 'bg-purple-50 dark:bg-purple-950/50 border-purple-200 dark:border-purple-800',
                'icon' => 'text-purple-500',
                'iconName' => 'information-circle',
            ],
            'pink' => [
                'container' => 'bg-pink-50 dark:bg-pink-950/50 border-pink-200 dark:border-pink-800',
                'icon' => 'text-pink-500',
                'iconName' => 'information-circle',
            ],
            'teal' => [
                'container' => 'bg-teal-50 dark:bg-teal-950/50 border-teal-200 dark:border-teal-800',
                'icon' => 'text-teal-500',
                'iconName' => 'check-circle',
            ],
            'neutral' => [
                'container' => 'bg-neutral-50 dark:bg-neutral-950/50 border-neutral-200 dark:border-neutral-800',
                'icon' => 'text-neutral-500',
                'iconName' => 'information-circle',
            ],
        ];
    }
}

<?php

namespace Neura\Kit\Packs\Input;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'base' => [
                'border'  => 'border-primary-200 dark:border-primary-700',
                'focus'   => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-400 dark:focus:ring-primary-400/20',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
            'checkbox' => [
                'border' => 'border-primary-300/60 dark:border-primary-600/60',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'checked' => 'data-[checked]:bg-primary-600 data-[checked]:border-primary-600 dark:data-[checked]:bg-primary-500 dark:data-[checked]:border-primary-500',
                'indeterminate' => 'data-[indeterminate]:bg-primary-600 data-[indeterminate]:border-primary-600 dark:data-[indeterminate]:bg-primary-500 dark:data-[indeterminate]:border-primary-500',
                'invalid' => 'border-danger-500/60 border-2 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400/60 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'radio' => [
                'border' => 'border-primary-300/60 dark:border-primary-600/60',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'checked' => 'data-[checked]:border-primary-600 dark:data-[checked]:border-primary-500',
                'dot' => 'bg-primary-600 dark:bg-primary-500',
                'invalid' => 'border-danger-500/60 border-2 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400/60 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'switch' => [
                'track' => 'bg-primary-200 dark:bg-primary-900',
                'trackActive' => 'bg-primary-600 dark:bg-primary-500',
                'thumb' => 'bg-white dark:bg-primary-100',
                'thumbActive' => 'bg-white dark:bg-white',
            ],
            'select' => [
                'border' => 'border-primary-200 dark:border-primary-700',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-400 dark:focus:ring-primary-400/20',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
        ];
    }
}

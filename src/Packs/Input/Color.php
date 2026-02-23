<?php

namespace Neura\Kit\Packs\Input;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'base' => [
                'border' => 'border-neutral-300 dark:border-white/20',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'select' => [
                'border' => 'border-neutral-300 dark:border-white/20',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'checkbox' => [
                'border' => 'border-neutral-300 dark:border-white/20',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'checked' => 'data-[checked]:bg-primary-500 data-[checked]:border-primary-500 data-[checked]:shadow-sm dark:data-[checked]:bg-primary-500 dark:data-[checked]:border-primary-500',
                'indeterminate' => 'data-[indeterminate]:bg-primary-500 data-[indeterminate]:border-primary-500 data-[indeterminate]:shadow-sm dark:data-[indeterminate]:bg-primary-500 dark:data-[indeterminate]:border-primary-500',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'radio' => [
                'border' => 'border-neutral-300 dark:border-white/20',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/25 dark:focus:border-primary-400 dark:focus:ring-primary-400/25',
                'checked' => 'peer-checked:bg-primary-500 peer-checked:border-primary-500 peer-checked:shadow-sm dark:peer-checked:bg-primary-500 dark:peer-checked:border-primary-500',
                'dot' => 'after:bg-white',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/25 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/25',
            ],
            'switch' => [
                'track' => 'bg-neutral-200 dark:bg-white/[0.12]',
                'trackActive' => 'bg-primary-500 dark:bg-primary-500',
                'thumb' => 'bg-white dark:bg-neutral-200',
                'thumbActive' => 'bg-white dark:bg-white',
            ],
        ];
    }
}

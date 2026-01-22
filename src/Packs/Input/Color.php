<?php

namespace Neura\Kit\Packs\Input;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'base' => [
                'border' => 'border-primary-200 dark:border-primary-900',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-800 dark:focus:ring-primary-800/20',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
            'select' => [
                'border' => 'border-primary-200 dark:border-primary-900',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-800 dark:focus:ring-primary-800/20',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
            'checkbox' => [
                'border' => 'border-primary-200 dark:border-primary-900',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-800 dark:focus:ring-primary-800/20',
                'checked' => 'data-[checked]:bg-primary-500 data-[checked]:border-primary-500 data-[checked]:shadow focus:shadow-primary-500/10 dark:data-[checked]:bg-primary-800 dark:data-[checked]:border-primary-800 dark:data-[checked]:shadow dark:focus:shadow-primary-800/10',
                'indeterminate' => 'data-[indeterminate]:bg-primary-500 data-[indeterminate]:border-primary-500 data-[indeterminate]:shadow focus:shadow-primary-500/10 dark:data-[indeterminate]:bg-primary-800 dark:data-[indeterminate]:border-primary-800 dark:data-[indeterminate]:shadow dark:focus:shadow-primary-800/10',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
            'radio' => [
                'border' => 'border-primary-200 dark:border-primary-900',
                'focus' => 'focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:focus:border-primary-800 dark:focus:ring-primary-800/20',
                'checked' => 'peer-checked:bg-primary-500 peer-checked:border-primary-500 peer-checked:shadow focus:shadow-primary-500/10 dark:peer-checked:bg-primary-800 dark:peer-checked:border-primary-800 dark:peer-checked:shadow dark:focus:shadow-primary-800/10',
                'dot' => 'after:bg-white',
                'invalid' => 'border-danger-500 focus:border-danger-500 focus:ring-2 focus:ring-danger-500/20 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400/20',
            ],
            'switch' => [
                'track' => 'bg-neutral-200 dark:bg-neutral-800',
                'trackActive' => 'bg-primary-500 dark:bg-primary-800',
                'thumb' => 'bg-white dark:bg-neutral-200',
                'thumbActive' => 'bg-white dark:bg-white',
            ],
        ];
    }
}

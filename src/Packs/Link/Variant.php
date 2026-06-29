<?php

namespace Neura\Kit\Packs\Link;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'decoration' => 'underline',
                'primary' => 'text-primary-600 dark:text-primary-400 decoration-primary-600/20 dark:decoration-primary-400/20',
                'secondary' => 'text-neutral-800 dark:text-white decoration-neutral-800/20 dark:decoration-white/20',
            ],
            'ghost' => [
                'decoration' => 'no-underline hover:underline',
                'primary' => 'text-primary-600 dark:text-primary-400 decoration-primary-600/20 dark:decoration-primary-400/20',
                'secondary' => 'text-neutral-800 dark:text-white decoration-neutral-800/20 dark:decoration-white/20',
            ],
            'soft' => [
                'decoration' => 'no-underline',
                'primary' => 'text-neutral-500 dark:text-white/70 hover:text-neutral-800 dark:hover:text-white',
                'secondary' => 'text-neutral-500 dark:text-white/70 hover:text-neutral-800 dark:hover:text-white',
            ],
        ];
    }
}

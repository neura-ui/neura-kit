<?php

namespace Neura\Kit\Packs\Orb;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    /**
     * Orb ink colors — applied as text-* utilities so the canvas
     * `currentColor` (re-read every frame) picks them up in light and dark mode.
     */
    public static function default(): array
    {
        return [
            'foreground' => 'text-fg',
            'muted' => 'text-fg-muted',
            'primary' => 'text-primary-500 dark:text-primary-400',
            'secondary' => 'text-neutral-500 dark:text-neutral-400',
            'success' => 'text-success-500 dark:text-success-400',
            'warning' => 'text-warning-500 dark:text-warning-400',
            'danger' => 'text-danger-500 dark:text-danger-400',
            'info' => 'text-info-500 dark:text-info-400',
            'white' => 'text-white',
            'black' => 'text-black dark:text-white',
            'current' => 'text-current',
        ];
    }
}

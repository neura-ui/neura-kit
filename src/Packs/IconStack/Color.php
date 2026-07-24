<?php

namespace Neura\Kit\Packs\IconStack;

use Neura\Kit\Packs\BasePack;

class Color extends BasePack
{
    /**
     * `frame`   tints the stacked isometric panels (drives currentColor used
     *           by the panel strokes and the ground shadow).
     * `content` tints the centered icon / slot content.
     */
    public static function default(): array
    {
        return [
            'foreground' => [
                'frame' => 'text-fg',
                'content' => 'text-fg-muted',
            ],
            'muted' => [
                'frame' => 'text-fg-muted',
                'content' => 'text-fg-muted',
            ],
            'primary' => [
                'frame' => 'text-primary-500 dark:text-primary-400',
                'content' => 'text-primary-600 dark:text-primary-400',
            ],
            'success' => [
                'frame' => 'text-success-500 dark:text-success-400',
                'content' => 'text-success-600 dark:text-success-400',
            ],
            'warning' => [
                'frame' => 'text-warning-500 dark:text-warning-400',
                'content' => 'text-warning-600 dark:text-warning-400',
            ],
            'danger' => [
                'frame' => 'text-danger-500 dark:text-danger-400',
                'content' => 'text-danger-600 dark:text-danger-400',
            ],
        ];
    }
}

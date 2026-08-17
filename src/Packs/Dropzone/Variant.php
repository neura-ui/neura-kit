<?php

namespace Neura\Kit\Packs\Dropzone;

use Neura\Kit\Packs\BasePack;

/**
 * Structural appearance variants for the dropzone.
 *
 * Each variant shares the calm ReUI frame language — a 1px dashed border that
 * only tints on hover/drag — and only changes the resting surface, shape or
 * shadow. Reactive colours (hover / dragging / invalid) live in the Color pack.
 */
class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'area' => 'border border-dashed border-edge',
                'tile' => '',
                'wrapper' => '',
                'rounded' => null,
                'shadow' => 'shadow-none',
                'showTile' => true,
            ],
            'solid' => [
                'area' => 'border border-solid border-edge bg-surface-inset',
                'tile' => '',
                'wrapper' => '',
                'rounded' => null,
                'shadow' => 'shadow-none',
                'showTile' => true,
            ],
            'card' => [
                'area' => 'border border-dashed border-edge bg-surface',
                'tile' => '',
                'wrapper' => '',
                'rounded' => null,
                'shadow' => 'shadow-sm',
                'showTile' => true,
            ],
            'minimal' => [
                'area' => 'border-b-2 border-dashed border-edge bg-transparent !p-4',
                'tile' => '',
                'wrapper' => '',
                'rounded' => null,
                'shadow' => 'shadow-none',
                'showTile' => false,
            ],
            'cover' => [
                'area' => 'border border-dashed border-edge bg-surface aspect-[3/1] min-h-44',
                'tile' => '',
                'wrapper' => '',
                'rounded' => 'rounded-xl',
                'shadow' => 'shadow-none',
                'showTile' => true,
            ],
            'avatar' => [
                'area' => 'mx-auto size-28 !p-0 !gap-0 border border-dashed border-edge bg-surface overflow-hidden',
                'tile' => 'size-12 rounded-full',
                'wrapper' => 'w-fit mx-auto',
                'icon' => 'camera',
                'rounded' => 'rounded-full',
                'shadow' => 'shadow-sm',
                'showTile' => true,
                'showText' => false,
                'avatar' => true,
            ],
        ];
    }
}

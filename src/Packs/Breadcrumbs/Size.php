<?php

namespace Neura\Kit\Packs\Breadcrumbs;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Size tokens for the breadcrumbs.
     *
     * `text` is the class of the trail, `gap` the space around a separator, `icon` the side of
     * an item icon and of a separator, `chip` the height of a pill, a step or a switcher (CSS
     * lengths). Complete class literals, so Tailwind can scan them.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'text' => 'text-xs',
                'gap' => '0.25rem',
                'icon' => '0.875rem',
                'chip' => '1.5rem',
            ],
            'md' => [
                'text' => 'text-sm',
                'gap' => '0.375rem',
                'icon' => '1rem',
                'chip' => '1.75rem',
            ],
            'lg' => [
                'text' => 'text-base',
                'gap' => '0.5rem',
                'icon' => '1.125rem',
                'chip' => '2.125rem',
            ],
        ];
    }
}

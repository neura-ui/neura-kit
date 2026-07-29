<?php

namespace Neura\Kit\Packs\Avatar;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        // Plain size-* (no [:where(&)] — & is HTML-escaped in attributes and breaks matching)
        return [
            'xs' => [
                'container' => 'size-6 shrink-0 text-xs',
                'icon' => 'size-4',
                'radius' => '[--avatar-radius:var(--radius-sm)]',
                'badge' => 'h-2 min-w-2',
            ],
            'sm' => [
                'container' => 'size-8 shrink-0 text-sm',
                'icon' => 'size-5',
                'radius' => '[--avatar-radius:var(--radius-md)]',
                'badge' => 'h-2 min-w-2',
            ],
            'md' => [
                'container' => 'size-10 shrink-0 text-sm',
                'icon' => 'size-6',
                'radius' => '[--avatar-radius:var(--radius-lg)]',
                'badge' => 'h-3 min-w-3',
            ],
            'lg' => [
                'container' => 'size-12 shrink-0 text-base',
                'icon' => 'size-8',
                'radius' => '[--avatar-radius:var(--radius-lg)]',
                'badge' => 'h-3 min-w-3',
            ],
            'xl' => [
                'container' => 'size-16 shrink-0 text-base',
                'icon' => 'size-10',
                'radius' => '[--avatar-radius:var(--radius-xl)]',
                'badge' => 'h-4 min-w-4',
            ],
        ];
    }
}

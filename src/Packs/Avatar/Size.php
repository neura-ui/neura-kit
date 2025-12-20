<?php

namespace Neura\Kit\Packs\Avatar;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'xs' => [
                'container' => '[:where(&)]:size-6 [:where(&)]:text-xs',
                'icon' => 'size-4',
                'radius' => '[--avatar-radius:var(--radius-sm)]',
                'badge' => 'h-2 min-w-2',
            ],
            'sm' => [
                'container' => '[:where(&)]:size-8 [:where(&)]:text-sm',
                'icon' => 'size-5',
                'radius' => '[--avatar-radius:var(--radius-md)]',
                'badge' => 'h-2 min-w-2',
            ],
            'md' => [
                'container' => '[:where(&)]:size-10 [:where(&)]:text-sm',
                'icon' => 'size-6',
                'radius' => '[--avatar-radius:var(--radius-lg)]',
                'badge' => 'h-3 min-w-3',
            ],
            'lg' => [
                'container' => '[:where(&)]:size-12 [:where(&)]:text-base',
                'icon' => 'size-8',
                'radius' => '[--avatar-radius:var(--radius-lg)]',
                'badge' => 'h-3 min-w-3',
            ],
            'xl' => [
                'container' => '[:where(&)]:size-16 [:where(&)]:text-base',
                'icon' => 'size-10',
                'radius' => '[--avatar-radius:var(--radius-xl)]',
                'badge' => 'h-4 min-w-4',
            ],
        ];
    }
}






<?php

namespace Neura\Kit\Packs\FileManager;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'sm' => [
                'media' => 'h-14',
                'toolbar' => 'h-11 px-2 gap-1.5',
                'crumb' => 'text-xs',
                'row' => 'h-9 px-2 gap-2.5',
                'icon' => 'size-4',
                'glyph' => 'size-3.5',
                'label' => 'text-xs',
                'meta' => 'text-[0.6875rem]',
                'action' => 'size-6',
                'grid' => 'grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6',
                'tile' => 'p-2 gap-1.5',
                'thumb' => 'size-8',
                'body' => 'min-h-56',
                'panel' => 'w-64',
            ],
            'md' => [
                'media' => 'h-20',
                'toolbar' => 'h-13 px-2.5 gap-2',
                'crumb' => 'text-[0.8125rem]',
                'row' => 'h-11 px-2.5 gap-3',
                'icon' => 'size-5',
                'glyph' => 'size-4',
                'label' => 'text-sm',
                'meta' => 'text-xs',
                'action' => 'size-7',
                'grid' => 'grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5',
                'tile' => 'p-3 gap-2',
                'thumb' => 'size-10',
                'body' => 'min-h-72',
                'panel' => 'w-72',
            ],
            'lg' => [
                'media' => 'h-24',
                'toolbar' => 'h-14 px-3 gap-2.5',
                'crumb' => 'text-sm',
                'row' => 'h-13 px-3 gap-3.5',
                'icon' => 'size-6',
                'glyph' => 'size-4',
                'label' => 'text-[0.9375rem]',
                'meta' => 'text-[0.8125rem]',
                'action' => 'size-8',
                'grid' => 'grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4',
                'tile' => 'p-3.5 gap-2.5',
                'thumb' => 'size-12',
                'body' => 'min-h-96',
                'panel' => 'w-80',
            ],
        ];
    }
}

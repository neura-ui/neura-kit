<?php

namespace Neura\Kit\Packs\Dropzone;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    public static function default(): array
    {
        return [
            'sm' => [
                'area' => 'px-4 py-5 gap-2.5',
                'tile' => 'size-9 rounded-lg',
                'glyph' => 'size-4',
                'text' => 'text-xs',
                'hint' => 'text-[0.6875rem]',
                'item' => 'p-2 gap-2.5',
                'thumb' => 'size-9 rounded-md',
                'label' => 'text-xs',
                'meta' => 'text-[0.6875rem]',
                'action' => 'size-6',
                'actionGlyph' => 'size-3.5',
                'bar' => 'h-1',
                'grid' => 'grid-cols-3 gap-2 sm:grid-cols-4',
            ],
            'md' => [
                'area' => 'px-6 py-7 gap-3',
                'tile' => 'size-11 rounded-xl',
                'glyph' => 'size-5',
                'text' => 'text-sm',
                'hint' => 'text-xs',
                'item' => 'p-2.5 gap-3',
                'thumb' => 'size-10 rounded-lg',
                'label' => 'text-sm',
                'meta' => 'text-xs',
                'action' => 'size-7',
                'actionGlyph' => 'size-4',
                'bar' => 'h-1.5',
                'grid' => 'grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4',
            ],
            'lg' => [
                'area' => 'px-8 py-10 gap-4',
                'tile' => 'size-14 rounded-2xl',
                'glyph' => 'size-6',
                'text' => 'text-base',
                'hint' => 'text-sm',
                'item' => 'p-3 gap-3.5',
                'thumb' => 'size-12 rounded-lg',
                'label' => 'text-sm',
                'meta' => 'text-xs',
                'action' => 'size-8',
                'actionGlyph' => 'size-4',
                'bar' => 'h-1.5',
                'grid' => 'grid-cols-2 gap-3 sm:grid-cols-3',
            ],
        ];
    }
}

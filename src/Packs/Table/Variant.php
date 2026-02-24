<?php

namespace Neura\Kit\Packs\Table;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'wrapper' => 'border border-edge bg-surface',
                'toolbar' => 'border-b border-separator bg-surface',
                'thead' => 'bg-surface-inset',
                'row' => 'border-b border-separator hover:bg-hover',
                'footer' => 'border-t border-separator bg-surface',
            ],
            'striped' => [
                'wrapper' => 'border border-edge bg-surface',
                'toolbar' => 'border-b border-separator bg-surface',
                'thead' => 'bg-surface-inset',
                'row' => 'border-b border-separator even:bg-surface-inset hover:bg-hover',
                'footer' => 'border-t border-separator bg-surface',
            ],
            'minimal' => [
                'wrapper' => 'bg-transparent',
                'toolbar' => 'border-b border-separator bg-transparent',
                'thead' => 'bg-transparent',
                'row' => 'border-b border-separator hover:bg-hover',
                'footer' => 'border-t border-separator bg-transparent',
            ],
            'flat' => [
                'wrapper' => 'bg-surface-inset',
                'toolbar' => 'border-b border-separator bg-surface-inset',
                'thead' => 'bg-active',
                'row' => 'border-b border-separator hover:bg-hover',
                'footer' => 'border-t border-separator bg-surface-inset',
            ],
            'bordered' => [
                'wrapper' => 'border-2 border-edge bg-surface',
                'toolbar' => 'border-b border-edge bg-surface',
                'thead' => 'bg-surface-inset',
                'row' => 'border-b border-edge hover:bg-hover',
                'footer' => 'border-t border-edge bg-surface',
            ],
            'elevated' => [
                'wrapper' => 'border border-edge bg-surface',
                'toolbar' => 'border-b border-separator bg-surface',
                'thead' => 'bg-surface-inset',
                'row' => 'border-b border-separator hover:bg-hover',
                'footer' => 'border-t border-separator bg-surface',
            ],
        ];
    }
}

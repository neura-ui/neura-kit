<?php

namespace Neura\Kit\Packs\FileManager;

use Neura\Kit\Packs\BasePack;

/**
 * State driven styles for the file manager.
 *
 * Entries expose `data-selected`, `data-focused` and `data-kind`; the root
 * exposes `data-dragging`. Styling therefore stays declarative instead of being
 * rebuilt at runtime through `:class` maps.
 */
class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'shell' => 'border-edge bg-surface text-fg',

            // Chrome around the list: opaque, so scrolled rows never show through.
            'bar' => 'border-edge bg-surface-raised',

            /*
             * Sticky column header: no fill at all — the bottom rule carries the
             * separation. It only has to be opaque so rows do not bleed through
             * while scrolling, which `surface` already guarantees.
             */
            'head' => 'border-edge bg-surface',

            'dropping' => 'data-[dropping]:ring-4 data-[dropping]:ring-primary-500/15 data-[dropping]:border-primary-400',

            'entry' => [
                'base' => 'text-fg-secondary hover:bg-hover',
                'selected' => 'data-[selected]:bg-primary-50 data-[selected]:text-fg dark:data-[selected]:bg-primary-950/50',
                'focused' => 'data-[focused]:ring-2 data-[focused]:ring-inset data-[focused]:ring-primary-500/45',
            ],

            'tile' => [
                'base' => 'border-edge bg-surface hover:border-edge-hover hover:bg-hover',
                'selected' => 'data-[selected]:border-primary-400 data-[selected]:bg-primary-50/70 dark:data-[selected]:border-primary-500/70 dark:data-[selected]:bg-primary-950/40',
                'focused' => 'data-[focused]:ring-2 data-[focused]:ring-primary-500/40',
            ],

            'kind' => 'text-fg-muted group-data-[kind=folder]/entry:text-info-500 group-data-[kind=image]/entry:text-primary-500 group-data-[kind=pdf]/entry:text-danger-500 group-data-[kind=sheet]/entry:text-success-500 group-data-[kind=archive]/entry:text-warning-500 group-data-[kind=video]/entry:text-info-400 group-data-[kind=audio]/entry:text-success-400',

            'name' => 'text-fg group-data-[kind=folder]/entry:font-medium',

            'action' => 'text-fg-muted hover:bg-active hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',

            'toggle' => [
                'base' => 'text-fg-muted hover:text-fg',
                'active' => 'bg-surface text-fg shadow-xs ring-1 ring-edge',
            ],

            'selection' => 'border-primary-200 bg-primary-50 text-fg dark:border-primary-800/60 dark:bg-primary-950/50',

            'status' => 'border-edge bg-surface-raised text-fg-muted',
        ];
    }
}

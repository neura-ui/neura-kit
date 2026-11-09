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

            'dropping' => 'data-[dropping]:ring-4 data-[dropping]:ring-[var(--nk-fm-accent-soft)] data-[dropping]:border-[var(--nk-fm-accent-edge)]',

            // Sortable drag affordances on rows / tiles (`data-drop`, `data-dragging`).
            'drop' => [
                'before' => 'data-[drop=before]:shadow-[inset_0_2px_0_0_var(--nk-fm-accent)]',
                'after' => 'data-[drop=after]:shadow-[inset_0_-2px_0_0_var(--nk-fm-accent)]',
                'inside' => 'data-[drop=inside]:bg-[var(--nk-fm-accent-soft)] data-[drop=inside]:ring-2 data-[drop=inside]:ring-inset data-[drop=inside]:ring-[var(--nk-fm-accent-edge)]',
                'dragging' => 'data-[dragging]:opacity-40 data-[dragging]:cursor-grabbing',
            ],

            'entry' => [
                'base' => 'text-fg-secondary hover:bg-hover',
                'selected' => 'data-[selected]:bg-[var(--nk-fm-accent-soft)] data-[selected]:text-[var(--nk-fm-accent-ink)] data-[selected]:shadow-[inset_0_0_0_1px_var(--nk-fm-accent-edge)]',
                'focused' => 'data-[focused]:ring-2 data-[focused]:ring-inset data-[focused]:ring-[var(--nk-fm-accent)]/60',
            ],

            'tile' => [
                'base' => 'border-edge bg-surface hover:border-edge-hover hover:bg-hover',
                'selected' => 'data-[selected]:border-[var(--nk-fm-accent)] data-[selected]:bg-[var(--nk-fm-accent-soft)] data-[selected]:shadow-[0_0_0_1px_var(--nk-fm-accent)]',
                'focused' => 'data-[focused]:ring-2 data-[focused]:ring-[var(--nk-fm-accent)]/60',
            ],

            'kind' => 'text-fg-muted group-data-[kind=folder]/entry:text-info-500 group-data-[kind=image]/entry:text-primary-500 group-data-[kind=pdf]/entry:text-danger-500 group-data-[kind=sheet]/entry:text-success-500 group-data-[kind=archive]/entry:text-warning-500 group-data-[kind=video]/entry:text-info-400 group-data-[kind=audio]/entry:text-success-400',

            'name' => 'text-fg group-data-[kind=folder]/entry:font-medium',

            'action' => 'text-fg-muted hover:bg-active hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',

            'toggle' => [
                'base' => 'text-fg-muted hover:text-fg',
                'active' => 'bg-surface text-fg shadow-xs ring-1 ring-edge',
            ],

            'selection' => 'border-[var(--nk-fm-accent-edge)] bg-[var(--nk-fm-accent-soft)] text-fg',

            'status' => 'border-edge bg-surface-raised text-fg-muted',
        ];
    }
}

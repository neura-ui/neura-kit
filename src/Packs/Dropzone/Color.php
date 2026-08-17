<?php

namespace Neura\Kit\Packs\Dropzone;

use Neura\Kit\Packs\BasePack;

/**
 * State driven styles for the dropzone.
 *
 * The drop area exposes its state through `data-state` (idle | dragging | invalid | disabled)
 * and each preview row through `data-status` (idle | uploading | success | error).
 * Styling therefore lives in a single place instead of being duplicated between
 * server rendered classes and runtime `:class` overrides.
 *
 * Matches the ReUI file-upload look: a calm dashed frame that only tints the
 * border on hover, and a soft `bg-primary/5` wash while dragging. No background
 * flash on hover so the transition stays unobtrusive.
 */
class Color extends BasePack
{
    public static function default(): array
    {
        return [
            'area' => [
                'interactive' => 'cursor-pointer hover:border-edge-hover dark:hover:border-edge-focus',
                'focus' => 'has-[:focus-visible]:border-primary-500 has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-primary-500/15 dark:has-[:focus-visible]:border-primary-400',
                'dragging' => 'data-[state=dragging]:border-primary-500 data-[state=dragging]:bg-primary-500/5 dark:data-[state=dragging]:border-primary-400 dark:data-[state=dragging]:bg-primary-500/10',
                'invalid' => 'data-[state=invalid]:border-danger-400 data-[state=invalid]:bg-danger-50/60 dark:data-[state=invalid]:border-danger-500/70 dark:data-[state=invalid]:bg-danger-950/25',
                'disabled' => 'border-edge bg-surface-inset opacity-60 cursor-not-allowed',
            ],

            'tile' => [
                'base' => 'bg-surface-inset text-fg-muted',
                'hover' => 'group-hover/dz:text-fg-secondary',
                'dragging' => 'group-data-[state=dragging]/dz:bg-primary-500/10 group-data-[state=dragging]/dz:text-primary-600 dark:group-data-[state=dragging]/dz:bg-primary-500/15 dark:group-data-[state=dragging]/dz:text-primary-400',
                'invalid' => 'group-data-[state=invalid]/dz:bg-danger-500/10 group-data-[state=invalid]/dz:text-danger-600 dark:group-data-[state=invalid]/dz:bg-danger-500/15 dark:group-data-[state=invalid]/dz:text-danger-400',
            ],

            'text' => [
                'title' => 'text-fg-secondary',
                'action' => 'text-primary-600 dark:text-primary-400',
                'hint' => 'text-fg-muted group-data-[state=invalid]/dz:text-danger-500 dark:group-data-[state=invalid]/dz:text-danger-400',
            ],

            'item' => [
                'base' => 'border-edge bg-surface-inset',
                'error' => 'data-[status=error]:border-danger-200 data-[status=error]:bg-danger-50/70 dark:data-[status=error]:border-danger-800/60 dark:data-[status=error]:bg-danger-950/30',
                'thumb' => 'bg-surface ring-1 ring-edge',
            ],

            'status' => 'text-fg-muted group-data-[status=uploading]/item:text-primary-600 group-data-[status=success]/item:text-success-600 group-data-[status=error]/item:text-danger-600 dark:group-data-[status=uploading]/item:text-primary-400 dark:group-data-[status=success]/item:text-success-400 dark:group-data-[status=error]/item:text-danger-400',

            'kind' => 'text-fg-muted group-data-[kind=image]/item:text-primary-600 group-data-[kind=pdf]/item:text-danger-600 group-data-[kind=video]/item:text-info-600 group-data-[kind=audio]/item:text-success-600 group-data-[kind=archive]/item:text-warning-600 dark:group-data-[kind=image]/item:text-primary-400 dark:group-data-[kind=pdf]/item:text-danger-400 dark:group-data-[kind=video]/item:text-info-400 dark:group-data-[kind=audio]/item:text-success-400 dark:group-data-[kind=archive]/item:text-warning-400',

            'bar' => [
                'track' => 'bg-edge',
                'fill' => 'bg-primary-500 dark:bg-primary-400',
            ],

            'action' => [
                'base' => 'text-fg-muted hover:text-fg hover:bg-active focus-visible:ring-2 focus-visible:ring-primary-500/30',
                'danger' => 'text-danger-500 hover:text-danger-600 hover:bg-danger-100 dark:hover:bg-danger-900/40',
            ],

            'rejection' => 'border-danger-200 bg-danger-50/70 text-danger-700 dark:border-danger-800/60 dark:bg-danger-950/30 dark:text-danger-300',
        ];
    }
}

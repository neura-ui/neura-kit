<?php

namespace Neura\Kit\Packs\Table;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'default' => [
                'wrapper' => 'border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03] bg-white dark:bg-neutral-900',
                'toolbar' => 'border-b border-neutral-100 dark:border-white/[0.06] bg-white dark:bg-neutral-900',
                'thead' => 'bg-neutral-50 dark:bg-neutral-800/60',
                'row' => 'border-b border-neutral-100 dark:border-white/[0.04] hover:bg-neutral-50/70 dark:hover:bg-white/[0.02]',
                'footer' => 'border-t border-neutral-100 dark:border-white/[0.06] bg-white dark:bg-neutral-900',
            ],
            'striped' => [
                'wrapper' => 'border border-black/[0.06] dark:border-white/[0.08] ring-1 ring-black/[0.02] dark:ring-white/[0.03] bg-white dark:bg-neutral-900',
                'toolbar' => 'border-b border-neutral-100 dark:border-white/[0.06] bg-white dark:bg-neutral-900',
                'thead' => 'bg-neutral-50 dark:bg-neutral-800/60',
                'row' => 'border-b border-neutral-100 dark:border-white/[0.04] even:bg-neutral-50/50 dark:even:bg-neutral-800/30 hover:bg-neutral-100/50 dark:hover:bg-white/[0.03]',
                'footer' => 'border-t border-neutral-100 dark:border-white/[0.06] bg-white dark:bg-neutral-900',
            ],
            'minimal' => [
                'wrapper' => 'bg-transparent',
                'toolbar' => 'border-b border-neutral-200/60 dark:border-white/[0.06] bg-transparent',
                'thead' => 'bg-transparent',
                'row' => 'border-b border-neutral-100/60 dark:border-white/[0.03] hover:bg-neutral-50/50 dark:hover:bg-white/[0.015]',
                'footer' => 'border-t border-neutral-200/60 dark:border-white/[0.06] bg-transparent',
            ],
            'flat' => [
                'wrapper' => 'bg-neutral-50 dark:bg-neutral-800/40',
                'toolbar' => 'border-b border-neutral-200/40 dark:border-white/[0.04] bg-neutral-50 dark:bg-neutral-800/40',
                'thead' => 'bg-neutral-100/60 dark:bg-neutral-800/60',
                'row' => 'border-b border-neutral-100/40 dark:border-white/[0.03] hover:bg-neutral-100/50 dark:hover:bg-white/[0.025]',
                'footer' => 'border-t border-neutral-200/40 dark:border-white/[0.04] bg-neutral-50 dark:bg-neutral-800/40',
            ],
            'bordered' => [
                'wrapper' => 'border-2 border-black/[0.08] dark:border-white/[0.1] bg-white dark:bg-neutral-900',
                'toolbar' => 'border-b border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-neutral-900',
                'thead' => 'bg-neutral-50 dark:bg-neutral-800/60',
                'row' => 'border-b border-neutral-200 dark:border-white/[0.06] hover:bg-neutral-50/70 dark:hover:bg-white/[0.02]',
                'footer' => 'border-t border-neutral-200 dark:border-white/[0.08] bg-white dark:bg-neutral-900',
            ],
            'elevated' => [
                'wrapper' => 'border border-black/[0.04] dark:border-white/[0.06] ring-1 ring-black/[0.02] dark:ring-white/[0.03] bg-white dark:bg-neutral-900',
                'toolbar' => 'border-b border-neutral-100 dark:border-white/[0.05] bg-white dark:bg-neutral-900',
                'thead' => 'bg-neutral-50/60 dark:bg-neutral-800/40',
                'row' => 'border-b border-neutral-100 dark:border-white/[0.04] hover:bg-neutral-50/70 dark:hover:bg-white/[0.02]',
                'footer' => 'border-t border-neutral-100 dark:border-white/[0.05] bg-white dark:bg-neutral-900',
            ],
        ];
    }
}

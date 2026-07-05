<?php

namespace Neura\Kit\Packs\Tabs;

use Neura\Kit\Packs\BasePack;

class Variant extends BasePack
{
    public static function default(): array
    {
        return [
            'line' => 'px-3 py-1.5 border-b-2 border-transparent data-[state=active]:border-neutral-900 data-[state=active]:text-neutral-900 dark:data-[state=active]:border-neutral-50 dark:data-[state=active]:text-neutral-50 text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-50 -mb-[2px]',
            'pills' => 'px-3 py-1.5 data-[state=active]:bg-white data-[state=active]:text-neutral-950 data-[state=active]:shadow-sm dark:data-[state=active]:bg-neutral-950 dark:data-[state=active]:text-neutral-50',
        ];
    }
}

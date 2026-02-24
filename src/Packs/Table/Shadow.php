<?php

namespace Neura\Kit\Packs\Table;

use Neura\Kit\Packs\BasePack;

class Shadow extends BasePack
{
    public static function default(): array
    {
        return [
            'none' => '',
            'xs' => 'shadow-[0_1px_2px_rgb(0_0_0/0.04)] dark:shadow-[0_1px_2px_rgb(0_0_0/0.2)]',
            'sm' => 'shadow-[0_1px_3px_rgb(0_0_0/0.04),0_1px_2px_-1px_rgb(0_0_0/0.03)] dark:shadow-[0_1px_3px_rgb(0_0_0/0.3),0_1px_2px_-1px_rgb(0_0_0/0.2)]',
            'md' => 'shadow-[0_4px_6px_-1px_rgb(0_0_0/0.05),0_2px_4px_-2px_rgb(0_0_0/0.04)] dark:shadow-[0_4px_6px_-1px_rgb(0_0_0/0.35),0_2px_4px_-2px_rgb(0_0_0/0.25)]',
            'lg' => 'shadow-[0_10px_15px_-3px_rgb(0_0_0/0.05),0_4px_6px_-4px_rgb(0_0_0/0.04)] dark:shadow-[0_10px_15px_-3px_rgb(0_0_0/0.4),0_4px_6px_-4px_rgb(0_0_0/0.3)]',
            'xl' => 'shadow-[0_20px_25px_-5px_rgb(0_0_0/0.06),0_8px_10px_-6px_rgb(0_0_0/0.04)] dark:shadow-[0_20px_25px_-5px_rgb(0_0_0/0.45),0_8px_10px_-6px_rgb(0_0_0/0.3)]',
        ];
    }
}

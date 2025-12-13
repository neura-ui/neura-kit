<?php

declare(strict_types=1);

namespace Neura\Kit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void success(string $content, int $duration = 4000)
 * @method static void warning(string $content, int $duration = 4000)
 * @method static void error(string $content, int $duration = 4000)
 * @method static void info(string $content, int $duration = 4000)
 * @method static void add(string $content, string $type = 'info', int $duration = 4000)
 * 
 * @see \Neura\Kit\Services\ToastService
 */
class Toast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'neura-kit.toast';
    }
}


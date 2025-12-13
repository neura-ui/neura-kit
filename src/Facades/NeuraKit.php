<?php

declare(strict_types=1);

namespace Neura\Kit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string renderManagers()
 * @method static string openModal(array $params = [])
 * 
 * @see \Neura\Kit\Services\NeuraKitService
 */
class NeuraKit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'neura-kit';
    }
}

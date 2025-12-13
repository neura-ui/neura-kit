<?php

namespace Neura\Kit\Packs;

abstract class BasePack
{
    abstract public static function default(): array;

    public static function get(string $key): ?string
    {
        return static::default()[$key] ?? null;
    }

    public static function all(): array
    {
        return static::default();
    }

    public static function keys(): array
    {
        return array_keys(static::default());
    }
}


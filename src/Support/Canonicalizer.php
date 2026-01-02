<?php

declare(strict_types=1);

namespace Neura\Kit\Support;

final class Canonicalizer
{
    public static function canonicalize(array $data): array
    {
        ksort($data);

        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = self::canonicalize($value);
            }
        }

        return $data;
    }
}

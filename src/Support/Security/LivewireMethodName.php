<?php

namespace Neura\Kit\Support\Security;

use InvalidArgumentException;

/**
 * Validates Livewire method names used in injected JavaScript.
 */
final class LivewireMethodName
{
    /**
     * @throws InvalidArgumentException
     */
    public static function assertValid(string $method): void
    {
        if ($method === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $method)) {
            throw new InvalidArgumentException('Invalid Livewire method name.');
        }
    }
}

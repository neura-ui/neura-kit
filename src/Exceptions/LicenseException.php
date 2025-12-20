<?php

declare(strict_types=1);

namespace Neura\Kit\Exceptions;

use Exception;

class LicenseException extends Exception
{
    public static function notActivated(): self
    {
        return new self('License is not activated. Please run: php artisan neura-kit:activate');
    }

    public static function activationFailed(string $message): self
    {
        return new self("License activation failed: {$message}");
    }

    public static function invalidSignature(): self
    {
        return new self('License signature validation failed');
    }

    public static function expired(string $expiresAt = null): self
    {
        $message = 'License has expired';
        if ($expiresAt) {
            $message .= " (expired on {$expiresAt})";
        }
        return new self($message);
    }

    public static function invalid(): self
    {
        return new self('License is invalid or corrupted');
    }
}


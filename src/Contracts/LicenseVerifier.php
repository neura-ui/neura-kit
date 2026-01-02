<?php

declare(strict_types=1);

namespace Neura\Kit\Contracts;

interface LicenseVerifier
{
    public function verify(array $license): bool;

    public function isExpired(array $license): bool;
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Carbon\Carbon;
use Neura\Kit\Contracts\LicenseVerifier;
use Neura\Kit\Support\Canonicalizer;

final class LicenseValidator implements LicenseVerifier
{
    private const HMAC_ALGORITHM = 'sha256';

    public function verify(array $license): bool
    {
        if (! isset($license['signature'])) {
            return false;
        }

        $signature = $license['signature'];
        $licenseCopy = $license;
        unset($licenseCopy['signature']);

        $canonical = Canonicalizer::canonicalize($licenseCopy);
        $data = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $secret = config('neura-kit.signing_secret');

        if (! $secret) {
            return false;
        }

        $expectedSignature = hash_hmac(self::HMAC_ALGORITHM, $data, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    public function isExpired(array $license): bool
    {
        if (! isset($license['expires_at']) || ! $license['expires_at']) {
            return false;
        }

        try {
            $expiresAt = Carbon::parse($license['expires_at']);

            return $expiresAt->isPast();
        } catch (\Exception $e) {
            return false;
        }
    }
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Tests\Unit\Services\License;

use Neura\Kit\Services\License\LicenseValidator;
use Neura\Kit\Tests\TestCase;

class LicenseValidatorTest extends TestCase
{
    private LicenseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LicenseValidator();
        
        putenv('NEURA_KIT_LICENSE_KEY=test-secret-key');
    }

    protected function tearDown(): void
    {
        putenv('NEURA_KIT_LICENSE_KEY');
        parent::tearDown();
    }

    public function test_verifies_valid_signature(): void
    {
        $license = [
            'license_key' => 'test-key',
            'plan' => 'solo',
            'expires_at' => now()->addYear()->toIso8601String(),
        ];

        $secret = 'test-secret-key';
        $canonical = \Neura\Kit\Support\Canonicalizer::canonicalize($license);
        $data = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $data, $secret);
        $license['signature'] = $signature;

        $this->assertTrue($this->validator->verify($license));
    }

    public function test_rejects_invalid_signature(): void
    {
        $license = [
            'license_key' => 'test-key',
            'plan' => 'solo',
            'signature' => 'invalid-signature',
        ];

        $this->assertFalse($this->validator->verify($license));
    }

    public function test_rejects_license_without_signature(): void
    {
        $license = [
            'license_key' => 'test-key',
            'plan' => 'solo',
        ];

        $this->assertFalse($this->validator->verify($license));
    }

    public function test_detects_expired_license(): void
    {
        $license = [
            'expires_at' => now()->subYear()->toIso8601String(),
        ];

        $this->assertTrue($this->validator->isExpired($license));
    }

    public function test_detects_non_expired_license(): void
    {
        $license = [
            'expires_at' => now()->addYear()->toIso8601String(),
        ];

        $this->assertFalse($this->validator->isExpired($license));
    }

    public function test_handles_license_without_expiration(): void
    {
        $license = [
            'license_key' => 'test-key',
        ];

        $this->assertFalse($this->validator->isExpired($license));
    }
}


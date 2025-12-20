<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Neura\Kit\Contracts\LicenseVerifier;
use Neura\Kit\Exceptions\LicenseException;

final class LicenseService
{
    public function __construct(
        private LicenseCache $cache,
        private LicenseValidator $validator,
        private ActivationClient $activationClient
    ) {}

    public function isActivated(): bool
    {
        $license = $this->cache->get();

        if (!$license) {
            return false;
        }

        if (!$this->validator->verify($license)) {
            return false;
        }

        if ($this->validator->isExpired($license)) {
            return false;
        }

        return true;
    }

    public function getLicense(): ?array
    {
        $license = $this->cache->get();

        if (!$license) {
            return null;
        }

        if (!$this->validator->verify($license)) {
            return null;
        }

        return $license;
    }

    public function activate(string $licenseKey): array
    {
        $payload = $this->buildActivationPayload($licenseKey);
        $licenseData = $this->activationClient->activate($licenseKey, $payload);

        if (!$this->validator->verify($licenseData)) {
            throw LicenseException::invalidSignature();
        }

        $this->cache->put($licenseData);

        return $licenseData;
    }

    public function getPlan(): ?string
    {
        $license = $this->getLicense();
        return $license['plan'] ?? null;
    }

    public function getFeatureFlags(): array
    {
        $license = $this->getLicense();
        return $license['features'] ?? [];
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->getFeatureFlags();
        return in_array($feature, $features, true);
    }

    public function isExpired(): bool
    {
        $license = $this->cache->get();
        return $license ? $this->validator->isExpired($license) : false;
    }

    public function getExpirationMessage(): ?string
    {
        if (!$this->isExpired()) {
            return null;
        }

        $license = $this->cache->get();
        $expiresAt = $license['expires_at'] ?? null;

        if (!$expiresAt) {
            return 'Your Neura Kit license has expired. Please renew to continue receiving updates.';
        }

        return sprintf(
            'Your Neura Kit license expired on %s. Please renew to continue receiving updates.',
            $expiresAt
        );
    }

    public function getExpirationDate(): ?string
    {
        $license = $this->getLicense();
        return $license['expires_at'] ?? null;
    }

    public function getProjectLimit(): ?int
    {
        $license = $this->getLicense();
        return $license['project_limit'] ?? null;
    }

    public function getAssignedProjects(): array
    {
        $license = $this->getLicense();
        return $license['assigned_projects'] ?? [];
    }

    public function clearCache(): void
    {
        $this->cache->forget();
    }

    private function buildActivationPayload(string $licenseKey): array
    {
        $environment = $this->getEnvironment();
        $projectIdentifier = $this->getProjectIdentifier();

        $payload = [
            'license_key' => $licenseKey,
            'project_identifier' => $projectIdentifier,
            'environment' => $environment,
            'system_metadata' => $this->getSystemMetadata(),
            'package' => [
                'name' => 'neura-ui/neura-kit',
                'version' => $this->getPackageVersion(),
            ],
        ];

        if ($environment === 'production') {
            $payload['primary_domain'] = $this->getPrimaryDomain();
        }

        return $payload;
    }

    private function getProjectIdentifier(): string
    {
        return hash('sha256', base_path());
    }

    private function getEnvironment(): string
    {
        $env = config('app.env', 'local');

        if (in_array($env, ['local', 'staging', 'production'], true)) {
            return $env;
        }

        return 'local';
    }

    private function getPrimaryDomain(): string
    {
        $url = config('app.url', '');

        if (empty($url)) {
            return request()->getHost() ?? 'unknown';
        }

        $parsed = parse_url($url);
        return $parsed['host'] ?? 'unknown';
    }

    private function getSystemMetadata(): array
    {
        return [
            'ip' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => PHP_OS,
        ];
    }

    private function getPackageVersion(): string
    {
        $composerPath = __DIR__ . '/../../../composer.json';

        if (!file_exists($composerPath)) {
            return 'unknown';
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        return $composer['version'] ?? 'dev-master';
    }
}


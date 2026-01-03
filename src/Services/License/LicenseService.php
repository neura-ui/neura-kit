<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Carbon\Carbon;
use Exception;
use Neura\Kit\Exceptions\LicenseException;
use RuntimeException;

final class LicenseService
{
    private ?array $tokenData = null;
    private ?bool $isValidCache = null;

    public function __construct(
        private readonly ActivationClient $client,
        private readonly LicenseCache $cache,
        private readonly EnvironmentDetector $environmentDetector,
        private readonly DomainDetector $domainDetector,
    ) {}

    public function isActivated(): bool
    {
        if ($this->isValidCache !== null) {
            return $this->isValidCache;
        }

        $data = $this->getTokenData();

        if ($data === null) {
            $this->isValidCache = false;
            return false;
        }

        if ($this->isTokenExpired($data)) {
            $this->isValidCache = false;
            return false;
        }

        $this->isValidCache = true;
        return true;
    }

    /**
     * @throws LicenseException
     */
    public function activate(string $licenseKey): array
    {
        $projectIdentifier = $this->getProjectIdentifier();
        $environment = $this->environmentDetector->detect();
        $domain = $this->domainDetector->getPrimaryDomain();

        $response = $this->client->activate(
            licenseKey: $licenseKey,
            projectIdentifier: $projectIdentifier,
            environment: $environment,
            domain: $domain !== 'unknown' ? $domain : null
        );

        if (!$response['ok']) {
            throw new RuntimeException('Activation failed: ' . ($response['error'] ?? 'Unknown error'));
        }

        $tokenData = [
            'token' => $response['token'],
            'expires_at' => $response['expires_at'],
            'license_id' => $response['license_id'],
            'project_id' => $response['project_id'],
            'activated_at' => now()->toIso8601String(),
        ];

        $this->cache->put($tokenData);
        $this->tokenData = $tokenData;
        $this->isValidCache = true;

        return $tokenData;
    }

    public function validate(): bool
    {
        $data = $this->getTokenData();

        if ($data === null) {
            return false;
        }

        if ($this->isTokenExpired($data)) {
            return $this->refresh();
        }

        $response = $this->client->validate($data['token']);

        if (!$response['ok']) {
            $this->isValidCache = false;
            return false;
        }

        $this->isValidCache = true;
        return true;
    }

    public function refresh(): bool
    {
        $data = $this->getTokenData();

        if ($data === null) {
            return false;
        }

        $response = $this->client->refresh($data['token']);

        if (!$response['ok']) {
            $this->cache->forget();
            $this->tokenData = null;
            $this->isValidCache = false;
            return false;
        }

        $tokenData = [
            'token' => $response['token'],
            'expires_at' => $response['expires_at'],
            'license_id' => $data['license_id'],
            'project_id' => $data['project_id'],
            'activated_at' => $data['activated_at'],
            'refreshed_at' => now()->toIso8601String(),
        ];

        $this->cache->put($tokenData);
        $this->tokenData = $tokenData;
        $this->isValidCache = true;

        return true;
    }

    public function getToken(): ?string
    {
        $data = $this->getTokenData();
        return $data['token'] ?? null;
    }

    public function getExpiresAt(): ?Carbon
    {
        $data = $this->getTokenData();

        if (!isset($data['expires_at'])) {
            return null;
        }

        try {
            return Carbon::parse($data['expires_at']);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getLicenseId(): ?int
    {
        $data = $this->getTokenData();
        return $data['license_id'] ?? null;
    }

    public function getProjectId(): ?int
    {
        $data = $this->getTokenData();
        return $data['project_id'] ?? null;
    }

    public function isExpired(): bool
    {
        $data = $this->getTokenData();
        return $data !== null && $this->isTokenExpired($data);
    }

    public function clearCache(): void
    {
        $this->cache->forget();
        $this->tokenData = null;
        $this->isValidCache = null;
    }

    public function getEnvironment(): string
    {
        return $this->environmentDetector->detect();
    }

    public function getPrimaryDomain(): string
    {
        return $this->domainDetector->getPrimaryDomain();
    }

    private function getTokenData(): ?array
    {
        if ($this->tokenData !== null) {
            return $this->tokenData;
        }

        $this->tokenData = $this->cache->get();
        return $this->tokenData;
    }

    private function isTokenExpired(array $data): bool
    {
        if (!isset($data['expires_at'])) {
            return true;
        }

        try {
            $expiresAt = Carbon::parse($data['expires_at']);
            return $expiresAt->isPast();
        } catch (Exception $e) {
            return true;
        }
    }

    private function getProjectIdentifier(): string
    {
        $customId = config('license.project_id');

        if (!empty($customId)) {
            return hash('sha256', 'custom:' . $customId);
        }

        $appKey = config('app.key', '');

        if (!empty($appKey)) {
            return hash('sha256', 'app:' . $appKey . ':' . base_path());
        }

        return hash('sha256', 'path:' . base_path());
    }
}

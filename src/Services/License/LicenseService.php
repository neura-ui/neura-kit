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

    /**
     * Check if license is activated (has valid token data)
     */
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

        // If token is expired, try to refresh it automatically
        if ($this->isTokenExpired($data)) {
            // Try to refresh before giving up
            if ($this->refresh()) {
                $this->isValidCache = true;
                return true;
            }

            $this->isValidCache = false;
            return false;
        }

        $this->isValidCache = true;
        return true;
    }

    /**
     * Activate license with a license key
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
            // Store license data separately from token data
            'license_data' => [
                'license_expires_at' => $response['license_expires_at'] ?? null,
                'plan' => $response['plan'] ?? null,
                'features' => $response['features'] ?? [],
            ],
        ];

        $this->cache->put($tokenData);
        $this->tokenData = $tokenData;
        $this->isValidCache = true;

        return $tokenData;
    }

    /**
     * Validate current token with server
     */
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

    /**
     * Refresh the access token
     */
    public function refresh(): bool
    {
        $data = $this->getTokenData();

        if ($data === null) {
            return false;
        }

        try {
            $response = $this->client->refresh($data['token']);

            if (!$response['ok']) {
                // Check if it's a license expiration vs token issue
                $error = $response['error'] ?? '';

                if (in_array($error, ['LICENSE_EXPIRED', 'LICENSE_REVOKED', 'LICENSE_SUSPENDED'])) {
                    // License itself is expired/invalid - clear cache
                    $this->cache->forget();
                    $this->tokenData = null;
                    $this->isValidCache = false;

                    \Log::error("License error during refresh: {$error}");
                } else {
                    // Just a token refresh issue - might be temporary
                    \Log::warning("Token refresh failed: {$error}");
                }

                return false;
            }

            // Update token data while preserving license information
            $tokenData = [
                'token' => $response['token'],
                'expires_at' => $response['expires_at'],
                'license_id' => $data['license_id'],
                'project_id' => $data['project_id'],
                'activated_at' => $data['activated_at'],
                'refreshed_at' => now()->toIso8601String(),
                // Preserve license data or update if provided
                'license_data' => $response['license_data'] ?? $data['license_data'] ?? [],
            ];

            $this->cache->put($tokenData);
            $this->tokenData = $tokenData;
            $this->isValidCache = true;

            return true;
        } catch (Exception $e) {
            \Log::error('Token refresh exception: ' . $e->getMessage());
            return false;
        }
    }

    public function getToken(): ?string
    {
        $data = $this->getTokenData();
        return $data['token'] ?? null;
    }

    /**
     * Get token expiration date
     */
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

    /**
     * Get LICENSE expiration date (different from token!)
     */
    public function getLicenseExpiresAt(): ?Carbon
    {
        $data = $this->getTokenData();

        if (!isset($data['license_data']['license_expires_at'])) {
            return null;
        }

        try {
            return Carbon::parse($data['license_data']['license_expires_at']);
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

    /**
     * Get license plan
     */
    public function getPlan(): ?string
    {
        $data = $this->getTokenData();
        return $data['license_data']['plan'] ?? null;
    }

    /**
     * Get license features
     */
    public function getFeatures(): array
    {
        $data = $this->getTokenData();
        return $data['license_data']['features'] ?? [];
    }

    /**
     * Check if TOKEN is expired (not the license!)
     */
    public function isExpired(): bool
    {
        $data = $this->getTokenData();
        return $data !== null && $this->isTokenExpired($data);
    }

    /**
     * Check if the actual LICENSE is expired
     */
    public function isLicenseExpired(): bool
    {
        $licenseExpiresAt = $this->getLicenseExpiresAt();

        if ($licenseExpiresAt === null) {
            // If no expiration date, consider license as perpetual
            return false;
        }

        return $licenseExpiresAt->isPast();
    }

    /**
     * Check if library should work (license valid, token can be refreshed)
     */
    public function shouldWork(): bool
    {
        // If license itself is expired, don't work
        if ($this->isLicenseExpired()) {
            return false;
        }

        // If we have a valid token, work
        if ($this->isActivated()) {
            return true;
        }

        // If token is expired but license is valid, we should still work
        // (token will auto-refresh on next request)
        $data = $this->getTokenData();
        if ($data !== null && !$this->isLicenseExpired()) {
            return true;
        }

        return false;
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

    /**
     * Get full license status information
     */
    public function getStatus(): array
    {
        $data = $this->getTokenData();

        return [
            'is_activated' => $this->isActivated(),
            'token_expired' => $this->isExpired(),
            'license_expired' => $this->isLicenseExpired(),
            'should_work' => $this->shouldWork(),
            'token_expires_at' => $this->getExpiresAt()?->toIso8601String(),
            'license_expires_at' => $this->getLicenseExpiresAt()?->toIso8601String(),
            'license_id' => $this->getLicenseId(),
            'project_id' => $this->getProjectId(),
            'plan' => $this->getPlan(),
            'features' => $this->getFeatures(),
            'environment' => $this->getEnvironment(),
            'domain' => $this->getPrimaryDomain(),
        ];
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

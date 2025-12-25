<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Exception;
use Neura\Kit\Contracts\LicenseVerifier;
use Neura\Kit\Exceptions\LicenseException;

final class LicenseService
{
    private ?bool $isActivatedCache = null;
    private ?array $verifiedLicenseCache = null;
    private ?EnvironmentDetector $environmentDetector = null;
    private ?DomainDetector $domainDetector = null;

    public function __construct(
        private LicenseCache $cache,
        private LicenseValidator $validator,
        private ActivationClient $activationClient
    ) {}

    public function isActivated(): bool
    {
        if ($this->isActivatedCache !== null) {
            return $this->isActivatedCache;
        }

        $license = $this->cache->get();

        if (!$license) {
            $this->isActivatedCache = false;
            return false;
        }

        if (!$this->validator->verify($license)) {
            $this->isActivatedCache = false;
            return false;
        }

        if ($this->validator->isExpired($license)) {
            $this->isActivatedCache = false;
            return false;
        }

        $this->verifiedLicenseCache = $license;
        $this->isActivatedCache = true;
        return true;
    }

    public function getLicense(): ?array
    {
        if ($this->verifiedLicenseCache !== null) {
            return $this->verifiedLicenseCache;
        }

        if ($this->isActivatedCache === false) {
            return null;
        }

        $license = $this->cache->get();

        if (!$license || !$this->validator->verify($license)) {
            return null;
        }

        $this->verifiedLicenseCache = $license;
        return $license;
    }

    /**
     * @throws LicenseException
     */
    public function activate(string $licenseKey): array
    {
        $payload = $this->buildActivationPayload($licenseKey);
        $licenseData = $this->activationClient->activate($licenseKey, $payload);

        if (!$this->validator->verify($licenseData)) {
            throw LicenseException::invalidSignature();
        }

        $this->cache->put($licenseData);
        $this->verifiedLicenseCache = $licenseData;
        $this->isActivatedCache = true;

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
        return $license && $this->validator->isExpired($license);
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

    public function getDomains(): array
    {
        $license = $this->getLicense();
        return $license['domains'] ?? [];
    }

    public function getPrimaryDomainFromLicense(): ?string
    {
        $license = $this->getLicense();
        return $license['primary_domain'] ?? null;
    }

    public function isDomainAllowed(string $domain): bool
    {
        $domains = $this->getDomains();
        $domain = strtolower(trim($domain));

        if (empty($domains)) {
            return true;
        }

        foreach ($domains as $allowedDomain) {
            $allowedDomain = strtolower(trim($allowedDomain));

            if ($domain === $allowedDomain) {
                return true;
            }

            if (str_starts_with($allowedDomain, '*.')) {
                $pattern = substr($allowedDomain, 2);
                if (str_ends_with($domain, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isLifetime(): bool
    {
        $license = $this->getLicense();
        return $license['is_lifetime'] ?? false;
    }

    public function clearCache(): void
    {
        $this->cache->forget();
        $this->verifiedLicenseCache = null;
        $this->isActivatedCache = null;
    }

    public function getDomainInfo(): array
    {
        return $this->getDomainDetector()->getDomainInfo();
    }

    public function getDetectedEnvironment(): string
    {
        return $this->getEnvironmentDetector()->detect();
    }

    public function isProduction(): bool
    {
        return $this->getEnvironmentDetector()->isProduction();
    }

    public function isLocalEnvironment(): bool
    {
        return $this->getEnvironmentDetector()->isLocal();
    }

    private function buildActivationPayload(string $licenseKey): array
    {
        $environment = $this->getEnvironment();
        $projectIdentifier = $this->getProjectIdentifier();
        $domainDetector = $this->getDomainDetector();

        return [
            'license_key' => $licenseKey,
            'project_identifier' => $projectIdentifier,
            'environment' => $environment,
            'primary_domain' => $domainDetector->getPrimaryDomain(),
            'domains' => $domainDetector->getAllDomains(),
            'system_metadata' => $this->getSystemMetadata(),
            'package' => [
                'name' => 'neura-ui/neura-kit',
                'version' => $this->getPackageVersion(),
            ],
        ];
    }

    private function getProjectIdentifier(): string
    {
        $appKey = config('app.key', '');
        if (!empty($appKey)) {
            return hash('sha256', $appKey . base_path());
        }
        return hash('sha256', base_path());
    }

    private function getEnvironment(): string
    {
        return $this->getEnvironmentDetector()->detect();
    }

    private function getPrimaryDomain(): string
    {
        return $this->getDomainDetector()->getPrimaryDomain();
    }

    private function getSystemMetadata(): array
    {
        $metadata = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        ];

        try {
            $metadata['ip'] = request()->ip() ?? 'unknown';
            $metadata['user_agent'] = request()->userAgent() ?? 'unknown';
        } catch (Exception $e) {
            $metadata['ip'] = 'unknown';
            $metadata['user_agent'] = 'unknown';
        }

        return $metadata;
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

    private function getEnvironmentDetector(): EnvironmentDetector
    {
        if ($this->environmentDetector === null) {
            $this->environmentDetector = new EnvironmentDetector();
        }
        return $this->environmentDetector;
    }

    private function getDomainDetector(): DomainDetector
    {
        if ($this->domainDetector === null) {
            $this->domainDetector = new DomainDetector($this->getEnvironmentDetector());
        }
        return $this->domainDetector;
    }
}


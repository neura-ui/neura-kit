<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Neura\Kit\Exceptions\LicenseException;

final class LicenseService
{
    private ?bool $isActivatedCache = null;

    private ?array $verifiedLicenseCache = null;

    private ?EnvironmentDetector $environmentDetector = null;

    private ?DomainDetector $domainDetector = null;

    public function __construct(
        private readonly LicenseCache $cache,
        private readonly LicenseValidator $validator,
        private readonly ActivationClient $activationClient
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function isActivated(): bool
    {
        if ($this->isActivatedCache !== null) {
            return $this->isActivatedCache;
        }

        $license = $this->cache->get();

        if (! $license) {
            $this->isActivatedCache = false;

            return false;
        }

        if (! $this->validator->verify($license)) {
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

    /**
     * @throws FileNotFoundException
     */
    public function getLicense(): ?array
    {
        if ($this->verifiedLicenseCache !== null) {
            return $this->verifiedLicenseCache;
        }

        if ($this->isActivatedCache === false) {
            return null;
        }

        $license = $this->cache->get();

        if (! $license || ! $this->validator->verify($license)) {
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

    /**
     * @throws FileNotFoundException
     */
    public function getPlan(): ?string
    {
        $license = $this->getLicense();

        return $license['plan'] ?? null;
    }

    /**
     * @throws FileNotFoundException
     */
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

    /**
     * @throws FileNotFoundException
     */
    public function isExpired(): bool
    {
        $license = $this->cache->get();

        return $license && $this->validator->isExpired($license);
    }

    /**
     * @throws FileNotFoundException
     */
    public function getExpirationMessage(): ?string
    {
        if (! $this->isExpired()) {
            return null;
        }

        $license = $this->cache->get();
        $expiresAt = $license['expires_at'] ?? null;

        if (! $expiresAt) {
            return 'Your Neura Kit license has expired. Please renew to continue receiving updates.';
        }

        return sprintf(
            'Your Neura Kit license expired on %s. Please renew to continue receiving updates.',
            $expiresAt
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function getExpirationDate(): ?string
    {
        $license = $this->getLicense();

        return $license['expires_at'] ?? null;
    }

    /**
     * @throws FileNotFoundException
     */
    public function getProjectLimit(): ?int
    {
        $license = $this->getLicense();

        return $license['project_limit'] ?? null;
    }

    /**
     * @throws FileNotFoundException
     */
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

    /**
     * @throws FileNotFoundException
     */
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

    /**
     * @throws FileNotFoundException
     */
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
        $customId = config('neura-kit.project_id');
        if (!empty($customId)) {
            return hash('sha256', 'custom:'.$customId);
        }

        $gitRemote = $this->getGitRemoteUrl();
        if ($gitRemote !== null) {
            return hash('sha256', 'git:'.$gitRemote);
        }

        $dbIdentifier = $this->getDatabaseIdentifier();
        if ($dbIdentifier !== null) {
            return hash('sha256', 'db:'.$dbIdentifier);
        }

        $composerName = $this->getRootComposerName();
        if ($composerName !== null) {
            return hash('sha256', 'composer:'.$composerName);
        }

        $appKey = config('app.key', '');
        if (!empty($appKey)) {
            return hash('sha256', 'app:'.$appKey.':'.base_path());
        }

        return hash('sha256', 'path:'.base_path());
    }

    private function getGitRemoteUrl(): ?string
    {
        $gitConfigPath = base_path('.git/config');

        if (!file_exists($gitConfigPath)) {
            return null;
        }

        try {
            $gitConfig = file_get_contents($gitConfigPath);

            if (preg_match('/\[remote "origin"].*?url\s*=\s*(.+?)(?:\n|$)/s', $gitConfig, $matches)) {
                $url = trim($matches[1]);

                $url = preg_replace('/^git@([^:]+):/', 'https://$1/', $url);
                $url = preg_replace('/\.git$/', '', $url);

                return strtolower($url);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    private function getDatabaseIdentifier(): ?string
    {
        try {
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            if (!$config) {
                return null;
            }

            $parts = [];

            if (!empty($config['driver'])) {
                $parts[] = $config['driver'];
            }

            if (!empty($config['host'])) {
                $parts[] = $config['host'];
            }

            if (!empty($config['database'])) {
                $parts[] = $config['database'];
            }

            if (!empty($config['port']) && $config['port'] != 3306 && $config['port'] != 5432) {
                $parts[] = $config['port'];
            }

            if (empty($parts)) {
                return null;
            }

            return implode(':', $parts);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get root project composer package name
     */
    private function getRootComposerName(): ?string
    {
        $composerPath = base_path('composer.json');

        if (!file_exists($composerPath)) {
            return null;
        }

        try {
            $composer = json_decode(file_get_contents($composerPath), true);

            if (!empty($composer['name'])) {
                return $composer['name'];
            }
        } catch (Exception $e) {
        }

        return null;
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
        $composerPath = __DIR__.'/../../../composer.json';

        if (! file_exists($composerPath)) {
            return 'unknown';
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        return $composer['version'] ?? 'dev-master';
    }

    private function getEnvironmentDetector(): EnvironmentDetector
    {
        if ($this->environmentDetector === null) {
            $this->environmentDetector = new EnvironmentDetector;
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

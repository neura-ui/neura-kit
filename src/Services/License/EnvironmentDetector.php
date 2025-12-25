<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

/**
 * Détecte automatiquement l'environnement (local, staging, production)
 */
final class EnvironmentDetector
{
    /**
     * Domaines considérés comme locaux
     */
    private const LOCAL_DOMAINS = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    /**
     * Extensions de domaines locaux
     */
    private const LOCAL_EXTENSIONS = [
        '.test',
        '.local',
        '.localhost',
        '.dev',
        '.example',
        '.invalid',
        '.wip',
    ];

    /**
     * Patterns de domaines staging
     */
    private const STAGING_PATTERNS = [
        '/^staging\./i',
        '/^stage\./i',
        '/^preprod\./i',
        '/^pre-prod\./i',
        '/^dev\./i',
        '/^test\./i',
        '/^qa\./i',
        '/^uat\./i',
        '/\.staging\./i',
        '/\.stage\./i',
        '/\.dev\./i',
        '/\.test\./i',
    ];

    public function detect(): string
    {
        $forcedEnv = config('neura-kit.license_environment', 'auto');
        if ($forcedEnv !== 'auto' && in_array($forcedEnv, ['local', 'staging', 'production'], true)) {
            return $forcedEnv;
        }

        // 2. Priorité à la config Laravel (APP_ENV)
        $configEnv = $this->getConfigEnvironment();
        if ($configEnv !== null) {
            return $configEnv;
        }

        // 3. Détection basée sur le domaine
        $domain = $this->getCurrentDomain();
        return $this->detectFromDomain($domain);
    }

    public function isLocal(): bool
    {
        return $this->detect() === 'local';
    }

    public function isStaging(): bool
    {
        return $this->detect() === 'staging';
    }

    public function isProduction(): bool
    {
        return $this->detect() === 'production';
    }

    public function isLocalDomain(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        if (in_array($domain, self::LOCAL_DOMAINS, true)) {
            return true;
        }

        if ($this->isPrivateIp($domain)) {
            return true;
        }

        foreach (self::LOCAL_EXTENSIONS as $extension) {
            if (str_ends_with($domain, $extension)) {
                return true;
            }
        }

        if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $domain)) {
            return true;
        }

        return false;
    }

    public function isStagingDomain(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        if ($this->isLocalDomain($domain)) {
            return false;
        }

        foreach (self::STAGING_PATTERNS as $pattern) {
            if (preg_match($pattern, $domain)) {
                return true;
            }
        }

        return false;
    }

    private function getConfigEnvironment(): ?string
    {
        $env = config('app.env', '');

        if (empty($env)) {
            return null;
        }

        $env = strtolower($env);

        return match ($env) {
            'local', 'development', 'dev' => 'local',
            'staging', 'stage', 'testing', 'test', 'qa', 'uat', 'preprod' => 'staging',
            'production', 'prod', 'live' => 'production',
            default => null,
        };
    }

    private function getCurrentDomain(): string
    {
        $appUrl = config('app.url', '');
        if (!empty($appUrl)) {
            $parsed = parse_url($appUrl);
            if (isset($parsed['host'])) {
                return $parsed['host'];
            }
        }

        try {
            return request()->getHost() ?? 'localhost';
        } catch (\Exception $e) {
            return 'localhost';
        }
    }

    private function detectFromDomain(string $domain): string
    {
        if ($this->isLocalDomain($domain)) {
            return 'local';
        }

        if ($this->isStagingDomain($domain)) {
            return 'staging';
        }

        return 'production';
    }

    private function isPrivateIp(string $ip): bool
    {
        $ip = preg_replace('/:\d+$/', '', $ip);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}


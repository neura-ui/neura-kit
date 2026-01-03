<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

final class EnvironmentDetector
{
    private const array LOCAL_DOMAINS = ['localhost', '127.0.0.1', '::1'];
    private const array LOCAL_EXTENSIONS = ['.local', '.test', '.localhost', '.invalid'];
    private const array STAGING_PATTERNS = [
        '/^(staging|stage|preprod|uat|qa|test|dev)\./i',
        '/\.(staging|stage|preprod|uat|qa|test|dev)\./i',
    ];

    public function isLocal(): bool {
        return $this->detect() === 'local';
    }

    public function detect(): string {
        $configEnv = $this->getConfigEnvironment();

        if ($configEnv !== null) {
            return $configEnv;
        }

        $domain = $this->getCurrentDomain();

        if ($this->isLocalDomain($domain)) {
            return 'local';
        }

        if ($this->isStagingDomain($domain)) {
            return 'staging';
        }

        return 'production';
    }

    private function getConfigEnvironment(): ?string {
        $env = strtolower(config('app.env', ''));

        return match ($env) {
            'local', 'development', 'dev' => 'local',
            'staging', 'stage', 'testing', 'test', 'qa', 'uat', 'preprod' => 'staging',
            'production', 'prod', 'live' => 'production',
            default => null,
        };
    }

    private function getCurrentDomain(): string {
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

    public function isLocalDomain(string $domain): bool {
        $domain = strtolower(trim($domain));

        if (in_array($domain, self::LOCAL_DOMAINS, true)) {
            return true;
        }

        foreach (self::LOCAL_EXTENSIONS as $extension) {
            if (str_ends_with($domain, $extension)) {
                return true;
            }
        }

        return $this->isPrivateIp($domain);
    }

    private function isPrivateIp(string $ip): bool {
        $ip = preg_replace('/:\d+$/', '', $ip);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;
    }

    public function isStagingDomain(string $domain): bool {
        $domain = strtolower(trim($domain));

        foreach (self::STAGING_PATTERNS as $pattern) {
            if (preg_match($pattern, $domain)) {
                return true;
            }
        }

        return false;
    }

    public function isStaging(): bool {
        return $this->detect() === 'staging';
    }

    public function isProduction(): bool {
        return $this->detect() === 'production';
    }
}

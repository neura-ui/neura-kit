<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Exception;

/**
 * Détecte et collecte les domaines du projet
 */
final class DomainDetector
{
    public function __construct(
        private EnvironmentDetector $environmentDetector
    ) {}

    public function getPrimaryDomain(): string
    {
        $appUrl = config('app.url', '');
        if (! empty($appUrl)) {
            $parsed = parse_url($appUrl);
            if (isset($parsed['host'])) {
                return $parsed['host'];
            }
        }

        try {
            $host = request()->getHost();
            if ($host) {
                return $host;
            }
        } catch (Exception $e) {

        }

        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
        }

        return 'unknown';
    }

    public function getAllDomains(): array
    {
        $domains = [];

        $primaryDomain = $this->getPrimaryDomain();
        if ($primaryDomain !== 'unknown') {
            $domains[] = $primaryDomain;
        }

        try {
            $requestHost = request()->getHost();
            if ($requestHost && ! in_array($requestHost, $domains, true)) {
                $domains[] = $requestHost;
            }
        } catch (Exception $e) {

        }

        $additionalDomains = $this->getConfiguredDomains();
        foreach ($additionalDomains as $domain) {
            if (! in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }
        }

        $domains = array_filter($domains, fn ($d) => ! empty($d) && $d !== 'unknown');
        $domains = array_map(fn ($d) => strtolower(trim($d)), $domains);
        $domains = array_unique($domains);

        return array_values($domains);
    }

    public function getConfiguredDomains(): array
    {
        $configDomains = config('neura-kit.license_domains', []);
        if (is_string($configDomains)) {
            $configDomains = array_filter(array_map('trim', explode(',', $configDomains)));
        }

        $envDomains = env('NEURA_KIT_DOMAINS') ?: getenv('NEURA_KIT_DOMAINS');
        if (! empty($envDomains)) {
            $envDomainsArray = array_filter(array_map('trim', explode(',', $envDomains)));
            $configDomains = array_merge($configDomains, $envDomainsArray);
        }

        return array_unique($configDomains);
    }

    public function isProductionDomain(string $domain): bool
    {
        return ! $this->environmentDetector->isLocalDomain($domain)
            && ! $this->environmentDetector->isStagingDomain($domain);
    }

    public function getProductionDomains(): array
    {
        $allDomains = $this->getAllDomains();

        return array_values(array_filter(
            $allDomains,
            fn ($domain) => $this->isProductionDomain($domain)
        ));
    }

    public function getDomainInfo(): array
    {
        $allDomains = $this->getAllDomains();
        $primaryDomain = $this->getPrimaryDomain();

        return [
            'primary_domain' => $primaryDomain,
            'domains' => $allDomains,
            'production_domains' => $this->getProductionDomains(),
            'is_local' => $this->environmentDetector->isLocalDomain($primaryDomain),
            'is_staging' => $this->environmentDetector->isStagingDomain($primaryDomain),
            'is_production' => $this->isProductionDomain($primaryDomain),
        ];
    }
}

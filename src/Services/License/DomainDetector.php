<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

/**
 * Détecte et collecte les domaines du projet
 */
final class DomainDetector
{
    public function __construct(
        private EnvironmentDetector $environmentDetector
    ) {}

    /**
     * Obtient le domaine principal du projet
     */
    public function getPrimaryDomain(): string
    {
        // 1. Depuis APP_URL (priorité)
        $appUrl = config('app.url', '');
        if (!empty($appUrl)) {
            $parsed = parse_url($appUrl);
            if (isset($parsed['host'])) {
                return $parsed['host'];
            }
        }

        // 2. Depuis la requête HTTP
        try {
            $host = request()->getHost();
            if ($host) {
                return $host;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // 3. Depuis SERVER_NAME
        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        // 4. Depuis HTTP_HOST
        if (isset($_SERVER['HTTP_HOST'])) {
            // Supprimer le port si présent
            return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
        }

        return 'unknown';
    }

    /**
     * Collecte tous les domaines possibles du projet
     */
    public function getAllDomains(): array
    {
        $domains = [];

        // Domaine principal depuis APP_URL
        $primaryDomain = $this->getPrimaryDomain();
        if ($primaryDomain !== 'unknown') {
            $domains[] = $primaryDomain;
        }

        // Domaine depuis la requête actuelle
        try {
            $requestHost = request()->getHost();
            if ($requestHost && !in_array($requestHost, $domains, true)) {
                $domains[] = $requestHost;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Domaines depuis les variables d'environnement personnalisées
        $additionalDomains = $this->getConfiguredDomains();
        foreach ($additionalDomains as $domain) {
            if (!in_array($domain, $domains, true)) {
                $domains[] = $domain;
            }
        }

        // Filtrer les domaines vides et nettoyer
        $domains = array_filter($domains, fn($d) => !empty($d) && $d !== 'unknown');
        $domains = array_map(fn($d) => strtolower(trim($d)), $domains);
        $domains = array_unique($domains);

        return array_values($domains);
    }

    /**
     * Obtient les domaines configurés dans le fichier de config
     */
    public function getConfiguredDomains(): array
    {
        // Depuis la config neura-kit
        $configDomains = config('neura-kit.license_domains', []);
        if (is_string($configDomains)) {
            $configDomains = array_filter(array_map('trim', explode(',', $configDomains)));
        }

        // Depuis la variable d'environnement
        $envDomains = env('NEURA_KIT_DOMAINS', '');
        if (!empty($envDomains)) {
            $envDomainsArray = array_filter(array_map('trim', explode(',', $envDomains)));
            $configDomains = array_merge($configDomains, $envDomainsArray);
        }

        return array_unique($configDomains);
    }

    /**
     * Vérifie si un domaine est valide (production)
     */
    public function isProductionDomain(string $domain): bool
    {
        return !$this->environmentDetector->isLocalDomain($domain)
            && !$this->environmentDetector->isStagingDomain($domain);
    }

    /**
     * Filtre les domaines pour ne garder que les domaines de production
     */
    public function getProductionDomains(): array
    {
        $allDomains = $this->getAllDomains();
        return array_values(array_filter(
            $allDomains,
            fn($domain) => $this->isProductionDomain($domain)
        ));
    }

    /**
     * Obtient les informations complètes sur les domaines
     */
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


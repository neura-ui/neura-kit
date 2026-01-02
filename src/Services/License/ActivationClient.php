<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Exception;
use Illuminate\Support\Facades\Http;
use Neura\Kit\Exceptions\LicenseException;

final class ActivationClient
{
    private ?string $token = null;

    public function __construct()
    {
        $this->token = $this->resolveAuthToken();
    }

    /**
     * @throws LicenseException
     */
    public function activate(string $license, array $payload): array
    {
        $url = $this->getApiUrl('/activate');

        try {
            $response = Http::timeout(30)
                ->withHeaders($this->buildHeaders())
                ->post($url, $payload);
        } catch (Exception $e) {
            throw LicenseException::activationFailed(
                'Could not connect to license server. '.$e->getMessage()
            );
        }

        if (! $response->successful()) {
            $status  = $response->status();
            $message = $response->json('message') ?? 'Unknown error';
            $code    = $response->json('error_code') ?? 'UNKNOWN';

            throw LicenseException::activationFailed(
                "HTTP {$status} [{$code}]: {$message}"
            );
        }

        $licenseData = $response->json();

        if (! isset($licenseData['valid']) || ! $licenseData['valid']) {
            throw LicenseException::activationFailed(
                $licenseData['message'] ?? 'License is not valid'
            );
        }

        return $licenseData;
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return $headers;
    }

    private function resolveAuthToken(): ?string
    {
        if ($token = getenv('NEURA_LICENSE_TOKEN')) {
            return $token;
        }

        if ($token = config('neura-kit.license_token')) {
            return $token;
        }

        if ($token = $this->getTokenFromAuthJson()) {
            return $token;
        }

        if ($token = $this->getTokenFromComposerAuth()) {
            return $token;
        }

        return null;
    }

    private function getTokenFromComposerAuth(): ?string
    {
        $composerAuth = getenv('COMPOSER_AUTH') ?: ($_ENV['COMPOSER_AUTH'] ?? $_SERVER['COMPOSER_AUTH'] ?? null);

        if (empty($composerAuth)) {
            return null;
        }

        $data = json_decode($composerAuth, true);

        if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $this->extractTokenFromAuthData($data);
    }

    private function getTokenFromAuthJson(): ?string
    {
        $authJsonPath = base_path('auth.json');

        if (! file_exists($authJsonPath)) {
            $composerHome = getenv('COMPOSER_HOME') ?: (getenv('HOME') ? getenv('HOME').'/.composer' : null);

            if (! $composerHome) {
                return null;
            }

            $globalAuthPath = $composerHome.'/auth.json';

            if (! file_exists($globalAuthPath)) {
                return null;
            }

            $authJsonPath = $globalAuthPath;
        }

        try {
            $content = file_get_contents($authJsonPath);
            $data    = json_decode($content, true);

            if (! is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return $this->extractTokenFromAuthData($data);
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractTokenFromAuthData(array $data): ?string
    {
        $domain = $this->extractDomainFromApiUrl();

        if (empty($domain)) {
            return null;
        }

        if (isset($data['bearer'][$domain])) {
            return $data['bearer'][$domain];
        }

        if (isset($data['http-basic'][$domain]['password'])) {
            return $data['http-basic'][$domain]['password'];
        }

        return null;
    }

    private function extractDomainFromApiUrl(): string
    {
        $url    = $this->getApiUrl();
        $parsed = parse_url($url);

        return $parsed['host'] ?? '';
    }

    private function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = config('neura-kit.license_api_url', 'https://api.neura.test');
        $baseUrl = rtrim($baseUrl, '/');

        if ($endpoint !== '') {
            return $baseUrl.'/'.ltrim($endpoint, '/');
        }

        return $baseUrl;
    }
}

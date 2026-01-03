<?php

namespace Neura\Kit\Services\License;

use Exception;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ActivationClient
{
    public function __construct(
        private string $serverUrl,
    ) {
        $this->serverUrl = rtrim($this->serverUrl, '/');
    }

    public function activate(string $licenseKey, string $projectIdentifier, string $environment, ?string $domain): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders($this->getAuthHeaders())
            ->post("{$this->serverUrl}/activate", [
                'license_key' => $licenseKey,
                'project_identifier' => $projectIdentifier,
                'environment' => $environment,
                'domain' => $domain,
            ]);

        return $this->handleResponse($response);
    }

    public function validate(string $token): array
    {
        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders($this->getAuthHeaders())
            ->post("{$this->serverUrl}/validate", [
                'token' => $token,
            ]);

        return $response->successful() ? $response->json() : [
            'ok' => false,
            'error' => $response->json('error', 'TOKEN_INVALID')
        ];
    }

    public function refresh(string $token): array
    {
        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders($this->getAuthHeaders())
            ->post("{$this->serverUrl}/refresh", [
                'token' => $token,
            ]);

        return $response->successful() ? $response->json() : [
            'ok' => false,
            'error' => $response->json('error', 'REFRESH_FAILED')
        ];
    }

    /**
     * Auto-détecte le Bearer token depuis auth.json ou env
     */
    private function getAuthHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $token = $this->resolveBearerToken();

        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }

    public function getBearerToken(): ?string
    {
        return $this->resolveBearerToken();
    }
    /**
     * Résout le Bearer token depuis plusieurs sources
     */
    private function resolveBearerToken(): ?string
    {
        if ($token = getenv('NEURA_LICENSE_TOKEN')) {
            return $token;
        }

        if ($token = config('neura-kit.license_token')) {
            return $token;
        }

        if ($token = $this->getTokenFromAuthJson(base_path('auth.json'))) {
            return $token;
        }

        $composerHome = getenv('COMPOSER_HOME') ?: getenv('HOME') . '/.composer';
        if ($token = $this->getTokenFromAuthJson($composerHome . '/auth.json')) {
            return $token;
        }

        if ($composerAuth = getenv('COMPOSER_AUTH')) {
            $data = json_decode($composerAuth, true);
            if (is_array($data)) {
                return $this->extractTokenFromAuthData($data);
            }
        }

        return null;
    }

    private function getTokenFromAuthJson(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        try {
            $content = file_get_contents($path);
            $data = json_decode($content, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return $this->extractTokenFromAuthData($data);
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractTokenFromAuthData(array $data): ?string
    {
        $domain = parse_url($this->serverUrl, PHP_URL_HOST);

        if (isset($data['bearer'][$domain])) {
            return $data['bearer'][$domain];
        }

        if (isset($data['http-basic'][$domain]['password'])) {
            return $data['http-basic'][$domain]['password'];
        }

        return null;
    }

    private function handleResponse($response): array
    {
        if (!$response->successful()) {
            $status = $response->status();
            $error = $response->json('error', 'HTTP_' . $status);
            $message = $response->json('message', 'Unknown error');
            throw new RuntimeException("Activation failed [{$error}]: {$message} (HTTP {$status})");
        }

        $data = $response->json();

        if (!isset($data['ok']) || !$data['ok']) {
            throw new RuntimeException("Activation failed: ". ($data['message'] ?? 'Invalid response'));
        }

        return $data;
    }

    public function getEndpoints(): array
    {
        return [
            'activate' => $this->serverUrl . '/activate',
            'validate' => $this->serverUrl . '/validate',
            'refresh' => $this->serverUrl . '/refresh',
            'bearer_token' => $this->resolveBearerToken() ? '✅ Found' : '❌ Missing',
        ];
    }
}

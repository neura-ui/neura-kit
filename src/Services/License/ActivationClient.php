<?php

declare(strict_types=1);

namespace Neura\Kit\Services\License;

use Illuminate\Support\Facades\Http;
use Neura\Kit\Exceptions\LicenseException;

final class ActivationClient
{
    public function activate(string $licenseKey, array $payload): array
    {
        $url = $this->getApiUrl('/activate');

        try {
            $response = Http::timeout(30)->post($url, $payload);
        } catch (\Exception $e) {
            throw LicenseException::activationFailed(
                'Could not connect to license server. ' . $e->getMessage()
            );
        }

        if (!$response->successful()) {
            $status = $response->status();
            $message = $response->json('message') ?? 'Unknown error';
            $body = $response->body();

            throw LicenseException::activationFailed(
                "HTTP {$status}: {$message}. Response: {$body}"
            );
        }

        $licenseData = $response->json();

        if (!isset($licenseData['valid']) || !$licenseData['valid']) {
            throw LicenseException::activationFailed(
                $licenseData['message'] ?? 'License is not valid'
            );
        }

        return $licenseData;
    }

    private function getApiUrl(string $endpoint = ''): string
    {
        $baseUrl = config('neura-kit.license_api_url', 'https://api.neuraui.dev');
        $baseUrl = rtrim($baseUrl, '/');

        if (!empty($endpoint)) {
            $endpoint = ltrim($endpoint, '/');
            if (!str_starts_with($endpoint, 'api/')) {
                $endpoint = 'api/' . $endpoint;
            }
            return $baseUrl . '/' . $endpoint;
        }

        return $baseUrl;
    }
}


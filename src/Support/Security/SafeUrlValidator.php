<?php

namespace Neura\Kit\Support\Security;

use InvalidArgumentException;

/**
 * Validates URLs before server-side fetch to mitigate SSRF.
 */
class SafeUrlValidator
{
    /**
     * @var list<string>
     */
    protected array $allowedSchemes;

    /**
     * @param  list<string>|null  $allowedSchemes
     */
    public function __construct(?array $allowedSchemes = null)
    {
        $this->allowedSchemes = $allowedSchemes ?? ['http', 'https'];
    }

    /**
     * Assert that a URL is safe to fetch from this application.
     *
     * @throws InvalidArgumentException
     */
    public function assertFetchable(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            throw new InvalidArgumentException('Invalid URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, $this->allowedSchemes, true)) {
            throw new InvalidArgumentException('URL scheme is not allowed.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('URLs with credentials are not allowed.');
        }

        $host = strtolower($parts['host']);

        if ($this->isBlockedHost($host)) {
            throw new InvalidArgumentException('URL host is not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        $resolved = $this->resolveHost($host);

        foreach ($resolved as $ip) {
            $this->assertPublicIp($ip);
        }
    }

    /**
     * Whether a URL is safe for use in an HTML href attribute.
     */
    public function isSafeHref(string $href): bool
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '//')) {
            return false;
        }

        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return ! str_contains($href, ':');
        }

        if (preg_match('/^(javascript|data|vbscript|file):/i', $href)) {
            return false;
        }

        try {
            $this->assertFetchable($href);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    protected function isBlockedHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return true;
        }

        return in_array($host, [
            'metadata.google.internal',
            'metadata',
        ], true);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function assertPublicIp(string $ip): void
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('URL resolves to a non-public address.');
        }
    }

    /**
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        $records = [];

        if (function_exists('dns_get_record')) {
            $a = @dns_get_record($host, DNS_A);
            if (is_array($a)) {
                foreach ($a as $record) {
                    if (! empty($record['ip'])) {
                        $records[] = $record['ip'];
                    }
                }
            }

            $aaaa = @dns_get_record($host, DNS_AAAA);
            if (is_array($aaaa)) {
                foreach ($aaaa as $record) {
                    if (! empty($record['ipv6'])) {
                        $records[] = $record['ipv6'];
                    }
                }
            }
        }

        if ($records === []) {
            $fallback = gethostbyname($host);
            if ($fallback !== $host) {
                $records[] = $fallback;
            }
        }

        if ($records === []) {
            throw new InvalidArgumentException('Unable to resolve URL host.');
        }

        return array_values(array_unique($records));
    }
}

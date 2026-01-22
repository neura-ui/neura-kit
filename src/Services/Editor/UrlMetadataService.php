<?php

namespace Neura\Kit\Services\Editor;

use Psr\Log\LoggerInterface;

/**
 * Service for fetching URL metadata (Open Graph, oEmbed, etc.)
 */
class UrlMetadataService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Fetch metadata for a URL
     *
     * @param string $url
     * @return array{title: string, description: string, image: string}
     */
    public function fetch(string $url): array
    {
        try {
            // Basic implementation - extract from URL
            // In production, consider using:
            // - Open Graph Protocol parser
            // - oEmbed endpoints
            // - Third-party services like Iframely or Embed.ly

            return [
                'title' => $this->extractTitle($url),
                'description' => $this->extractDescription($url),
                'image' => $this->extractImage($url),
            ];

        } catch (\Exception $e) {
            $this->logger->warning('Failed to fetch URL metadata', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            // Return basic metadata on failure
            return [
                'title' => $this->extractDomain($url),
                'description' => '',
                'image' => '',
            ];
        }
    }

    /**
     * Extract title from URL
     * 
     * Basic implementation - in production, parse Open Graph tags
     */
    protected function extractTitle(string $url): string
    {
        return $this->extractDomain($url);
    }

    /**
     * Extract description from URL
     * 
     * Basic implementation - in production, parse meta description or Open Graph
     */
    protected function extractDescription(string $url): string
    {
        // Could fetch the page and parse meta tags
        return '';
    }

    /**
     * Extract image from URL
     * 
     * Basic implementation - in production, parse Open Graph image
     */
    protected function extractImage(string $url): string
    {
        // Could fetch the page and parse og:image
        return '';
    }

    /**
     * Extract domain from URL
     */
    protected function extractDomain(string $url): string
    {
        $domain = parse_url($url, PHP_URL_HOST);
        return $domain ?: 'Link';
    }
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Search providers in Spotlight.
 *
 * Search providers handle the "Search" mode and can search
 * through different data sources (pages, users, documents, etc.)
 */
interface SpotlightSearchProvider
{
    /**
     * Search for results based on the query.
     *
     * @param  string  $query  The user's search query
     * @return Collection Collection of SpotlightResult objects
     */
    public function search(string $query): Collection;

    /**
     * Check if this provider can handle the query.
     *
     * @param  string  $query  The user's search query
     */
    public function canHandle(string $query): bool;

    /**
     * Get the provider's priority (higher = searched first).
     */
    public function priority(): int;

    /**
     * Get the provider's unique identifier.
     */
    public function getId(): string;

    /**
     * Get the provider's display name.
     */
    public function getName(): string;
}

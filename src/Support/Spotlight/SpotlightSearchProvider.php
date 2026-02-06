<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Support\Collection;
use Neura\Kit\Support\Spotlight\Contracts\SpotlightSearchProvider as SearchProviderContract;

/**
 * Base class for Spotlight Search providers.
 *
 * SIMPLE EXAMPLE - Pages search:
 * ```php
 * class PagesSearchProvider extends SpotlightSearchProvider
 * {
 *     protected string $id = 'pages';
 *     protected string $name = 'Pages';
 *     
 *     public function search(string $query): Collection
 *     {
 *         return collect([
 *             ['name' => 'Home', 'url' => '/'],
 *             ['name' => 'About', 'url' => '/about'],
 *         ])
 *         ->filter(fn($page) => str_contains(strtolower($page['name']), strtolower($query)))
 *         ->map(fn($page) => SpotlightResult::url(
 *             id: 'page-'.str($page['name'])->slug(),
 *             title: $page['name'],
 *             url: $page['url'],
 *             icon: 'document-text'
 *         ));
 *     }
 * }
 * ```
 *
 * DATABASE EXAMPLE - Users search:
 * ```php
 * class UsersSearchProvider extends SpotlightSearchProvider
 * {
 *     protected string $id = 'users';
 *     protected string $name = 'Users';
 *     protected int $priority = 10;
 *     
 *     public function search(string $query): Collection
 *     {
 *         return User::where('name', 'like', "%{$query}%")
 *             ->limit(5)
 *             ->get()
 *             ->map(fn($user) => SpotlightResult::url(
 *                 id: "user-{$user->id}",
 *                 title: $user->name,
 *                 url: "/users/{$user->id}",
 *                 description: $user->email,
 *                 icon: 'user'
 *             ));
 *     }
 * }
 * ```
 */
abstract class SpotlightSearchProvider implements SearchProviderContract
{
    /**
     * Provider unique identifier.
     */
    protected string $id = '';

    /**
     * Provider display name.
     */
    protected string $name = '';

    /**
     * Provider priority (higher = searched first).
     */
    protected int $priority = 0;

    /**
     * Keywords that trigger this provider.
     * If empty, provider handles all queries.
     */
    protected array $keywords = [];

    /**
     * Minimum query length to trigger search.
     */
    protected int $minQueryLength = 0;

    /**
     * Search for results.
     *
     * @param  string  $query  The user's search query
     * @return Collection Collection of SpotlightResult objects
     */
    abstract public function search(string $query): Collection;

    /**
     * Check if this provider can handle the query.
     */
    public function canHandle(string $query): bool
    {
        // Check minimum query length
        if (strlen($query) < $this->minQueryLength) {
            return false;
        }

        // If no keywords specified, handle all queries
        if (empty($this->keywords)) {
            return true;
        }

        $queryLower = strtolower($query);

        foreach ($this->keywords as $keyword) {
            if (str_contains($queryLower, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the provider's priority.
     */
    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * Get the provider's unique identifier.
     */
    public function getId(): string
    {
        return $this->id ?: class_basename($this);
    }

    /**
     * Get the provider's display name.
     */
    public function getName(): string
    {
        return $this->name ?: $this->getId();
    }
}

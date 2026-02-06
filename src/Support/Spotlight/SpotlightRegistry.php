<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Neura\Kit\Support\Spotlight\Contracts\SpotlightCommand as SpotlightCommandContract;
use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;
use Neura\Kit\Support\Spotlight\Enums\SpotlightMode;

/**
 * Registry for Spotlight commands and search providers.
 *
 * This singleton manages all registered commands and search providers,
 * providing search functionality and command execution.
 *
 * @example
 * ```php
 * // Register a command
 * SpotlightRegistry::register(MyCommand::class);
 *
 * // Register multiple commands
 * SpotlightRegistry::registerMany([
 *     CommandA::class,
 *     CommandB::class,
 * ]);
 *
 * // Register a custom search provider
 * SpotlightRegistry::registerSearchProvider('users', function ($query) {
 *     return User::search($query)->get()->map(fn ($user) =>
 *         SpotlightResult::url("user-{$user->id}", $user->name, "/users/{$user->id}")
 *     );
 * });
 *
 * // Search
 * $results = SpotlightRegistry::search('john', SpotlightMode::Search);
 * ```
 */
final class SpotlightRegistry
{
    /**
     * @var array<string, class-string<SpotlightCommandContract>>
     */
    private static array $commands = [];

    /**
     * @var array<string, callable(string): Collection<int, SpotlightResult>>
     */
    private static array $searchProviders = [];

    /**
     * @var array<string, class-string<Contracts\SpotlightSearchProvider>>
     */
    private static array $searchProviderClasses = [];

    /**
     * @var array<string, class-string<Contracts\SpotlightAiProvider>>
     */
    private static array $aiProviders = [];

    private static ?SpotlightConfig $config = null;

    /* =========================================================================
     | Configuration
     |========================================================================= */

    /**
     * Set the global configuration.
     */
    public static function configure(SpotlightConfig $config): void
    {
        self::$config = $config;
    }

    /**
     * Get the global configuration.
     */
    public static function getConfig(): SpotlightConfig
    {
        return self::$config ??= new SpotlightConfig;
    }

    /* =========================================================================
     | Command Registration
     |========================================================================= */

    /**
     * Register a command class.
     *
     * @param  class-string<SpotlightCommandContract>  $commandClass
     *
     * @throws InvalidArgumentException If class doesn't implement SpotlightCommand
     */
    public static function register(string $commandClass): void
    {
        if (! is_subclass_of($commandClass, SpotlightCommandContract::class)) {
            throw new InvalidArgumentException(
                "Command [{$commandClass}] must implement ".SpotlightCommandContract::class
            );
        }

        $command = app($commandClass);
        self::$commands[$command->getId()] = $commandClass;
    }

    /**
     * Register multiple command classes.
     *
     * @param  array<class-string<SpotlightCommandContract>>  $commands
     */
    public static function registerMany(array $commands): void
    {
        foreach ($commands as $command) {
            self::register($command);
        }
    }

    /**
     * Unregister a command by ID.
     */
    public static function unregister(string $commandId): bool
    {
        if (isset(self::$commands[$commandId])) {
            unset(self::$commands[$commandId]);

            return true;
        }

        return false;
    }

    /**
     * Check if a command is registered.
     */
    public static function has(string $commandId): bool
    {
        return isset(self::$commands[$commandId]);
    }

    /* =========================================================================
     | Search Provider Registration
     |========================================================================= */

    /**
     * Register a search provider callback (legacy method).
     *
     * @param  callable(string): Collection<int, SpotlightResult>  $provider
     */
    public static function registerSearchProvider(string $id, callable $provider): void
    {
        self::$searchProviders[$id] = $provider;
    }

    /**
     * Register a search provider class.
     *
     * @param  class-string<Contracts\SpotlightSearchProvider>  $providerClass
     */
    public static function registerSearchProviderClass(string $providerClass): void
    {
        if (! is_subclass_of($providerClass, Contracts\SpotlightSearchProvider::class)) {
            throw new InvalidArgumentException(
                "Search Provider [{$providerClass}] must implement ".Contracts\SpotlightSearchProvider::class
            );
        }

        $provider = app($providerClass);
        self::$searchProviderClasses[$provider->getId()] = $providerClass;
    }

    /**
     * Register multiple search provider classes.
     *
     * @param  array<class-string<Contracts\SpotlightSearchProvider>>  $providers
     */
    public static function registerSearchProviderClasses(array $providers): void
    {
        foreach ($providers as $provider) {
            self::registerSearchProviderClass($provider);
        }
    }

    /**
     * Get all registered search provider instances.
     *
     * @return Collection<int, Contracts\SpotlightSearchProvider>
     */
    public static function getSearchProviders(): Collection
    {
        return collect(self::$searchProviderClasses)
            ->map(fn (string $class) => app($class))
            ->sortByDesc(fn (Contracts\SpotlightSearchProvider $provider) => $provider->priority())
            ->values();
    }

    /**
     * Unregister a search provider.
     */
    public static function unregisterSearchProvider(string $id): bool
    {
        $removed = false;

        if (isset(self::$searchProviders[$id])) {
            unset(self::$searchProviders[$id]);
            $removed = true;
        }

        if (isset(self::$searchProviderClasses[$id])) {
            unset(self::$searchProviderClasses[$id]);
            $removed = true;
        }

        return $removed;
    }

    /* =========================================================================
     | Retrieval Methods
     |========================================================================= */

    /**
     * Get a single command by ID.
     */
    public static function get(string $commandId): ?SpotlightCommandContract
    {
        if (! isset(self::$commands[$commandId])) {
            return null;
        }

        return app(self::$commands[$commandId]);
    }

    /**
     * Get all registered commands.
     *
     * @return Collection<string, SpotlightCommandContract>
     */
    public static function getCommands(): Collection
    {
        return collect(self::$commands)
            ->map(fn (string $class) => app($class))
            ->filter(fn (SpotlightCommandContract $cmd) => $cmd->shouldBeShown())
            ->sortByDesc(fn (SpotlightCommandContract $cmd) => $cmd->getPriority());
    }

    /**
     * Get commands grouped by category.
     *
     * @return Collection<string, Collection<int, SpotlightCommandContract>>
     */
    public static function getGroupedCommands(): Collection
    {
        return self::getCommands()
            ->groupBy(fn (SpotlightCommandContract $cmd) => $cmd->getGroup()->value)
            ->sortKeys();
    }

    /**
     * Get commands for a specific group.
     *
     * @return Collection<int, SpotlightCommandContract>
     */
    public static function getCommandsByGroup(SpotlightGroup $group): Collection
    {
        return self::getCommands()
            ->filter(fn (SpotlightCommandContract $cmd) => $cmd->getGroup() === $group)
            ->values();
    }

    /**
     * Get all commands as results (for command palette mode).
     *
     * @return Collection<int, SpotlightResult>
     */
    public static function getCommandsAsResults(): Collection
    {
        return self::getCommands()
            ->map(fn (SpotlightCommandContract $cmd) => $cmd instanceof SpotlightCommand
                ? $cmd->toResult()
                : SpotlightResult::command(
                    id: $cmd->getId(),
                    title: $cmd->getName(),
                    commandId: $cmd->getId(),
                    description: $cmd->getDescription(),
                    icon: $cmd->getIcon(),
                    shortcut: $cmd->getShortcut(),
                    group: $cmd->getGroup(),
                    priority: $cmd->getPriority(),
                )
            )
            ->values();
    }

    /* =========================================================================
     | Search Methods
     |========================================================================= */

    /**
     * Search all commands and providers.
     *
     * @return Collection<int, SpotlightResult>
     */
    public static function search(string $query, ?SpotlightMode $mode = null): Collection
    {
        $results = collect();
        $config = self::getConfig();
        $mode ??= $config->defaultMode;

        // Different search behavior based on mode
        if ($mode === SpotlightMode::Command) {
            return self::searchCommands($query);
        }

        if ($mode === SpotlightMode::Ai) {
            // AI mode doesn't search, just returns empty
            return collect();
        }

        // Search mode: use registered commands' search methods and providers
        $results = self::searchWithProviders($query);

        return $results
            ->unique('id')
            ->sortByDesc('priority')
            ->take($config->maxResults)
            ->values();
    }

    /**
     * Search only within commands.
     *
     * @return Collection<int, SpotlightResult>
     */
    public static function searchCommands(string $query): Collection
    {
        $config = self::getConfig();
        $commands = self::getCommands();

        if (empty($query)) {
            return $commands
                ->map(fn (SpotlightCommandContract $cmd) => $cmd instanceof SpotlightCommand
                    ? $cmd->toResult()
                    : SpotlightResult::command(
                        id: $cmd->getId(),
                        title: $cmd->getName(),
                        commandId: $cmd->getId(),
                        description: $cmd->getDescription(),
                        icon: $cmd->getIcon(),
                        shortcut: $cmd->getShortcut(),
                        group: $cmd->getGroup(),
                        priority: $cmd->getPriority(),
                    )
                )
                ->take($config->maxResults)
                ->values();
        }

        return $commands
            ->filter(fn (SpotlightCommandContract $cmd) => $cmd instanceof SpotlightCommand
                ? $cmd->matches($query)
                : self::commandMatchesQuery($cmd, $query)
            )
            ->sortByDesc(fn (SpotlightCommandContract $cmd) => $cmd instanceof SpotlightCommand
                ? $cmd->getMatchScore($query)
                : $cmd->getPriority()
            )
            ->map(fn (SpotlightCommandContract $cmd) => $cmd instanceof SpotlightCommand
                ? $cmd->toResult()
                : SpotlightResult::command(
                    id: $cmd->getId(),
                    title: $cmd->getName(),
                    commandId: $cmd->getId(),
                    description: $cmd->getDescription(),
                    icon: $cmd->getIcon(),
                    shortcut: $cmd->getShortcut(),
                    group: $cmd->getGroup(),
                    priority: $cmd->getPriority(),
                )
            )
            ->take($config->maxResults)
            ->values();
    }

    /**
     * Search using registered providers.
     *
     * @return Collection<int, SpotlightResult>
     */
    private static function searchWithProviders(string $query): Collection
    {
        $results = collect();

        // Search through search provider classes (new system)
        foreach (self::getSearchProviders() as $provider) {
            if ($provider->canHandle($query)) {
                try {
                    $providerResults = $provider->search($query);
                    if ($providerResults->isNotEmpty()) {
                        $results = $results->merge($providerResults);
                    }
                } catch (\Throwable $e) {
                    // Log error but continue
                    report($e);
                }
            }
        }

        // Search through command's search methods
        foreach (self::getCommands() as $command) {
            $commandResults = $command->search($query);
            if ($commandResults->isNotEmpty()) {
                $results = $results->merge($commandResults);
            }
        }

        // Search through custom providers (legacy callbacks)
        foreach (self::$searchProviders as $provider) {
            try {
                $providerResults = $provider($query);
                if ($providerResults instanceof Collection && $providerResults->isNotEmpty()) {
                    $results = $results->merge($providerResults);
                }
            } catch (\Throwable $e) {
                // Log error but continue with other providers
                report($e);
            }
        }

        return $results;
    }

    /**
     * Check if a command matches a query (for non-SpotlightCommand implementations).
     */
    private static function commandMatchesQuery(SpotlightCommandContract $cmd, string $query): bool
    {
        $query = strtolower($query);

        return str_contains(strtolower($cmd->getName()), $query)
            || str_contains(strtolower($cmd->getDescription()), $query);
    }

    /* =========================================================================
     | Execution Methods
     |========================================================================= */

    /**
     * Execute a command by ID.
     *
     * @param  array<mixed>  $params
     */
    public static function execute(string $commandId, array $params = []): mixed
    {
        $command = self::get($commandId);

        if ($command === null) {
            return null;
        }

        return $command->execute($params);
    }

    /* =========================================================================
     | AI Provider Registration
     |========================================================================= */

    /**
     * Register an AI provider.
     *
     * @param  class-string<Contracts\SpotlightAiProvider>  $providerClass
     */
    public static function registerAiProvider(string $providerClass): void
    {
        if (! is_subclass_of($providerClass, Contracts\SpotlightAiProvider::class)) {
            throw new InvalidArgumentException(
                "AI Provider [{$providerClass}] must implement ".Contracts\SpotlightAiProvider::class
            );
        }

        self::$aiProviders[$providerClass] = $providerClass;
    }

    /**
     * Register multiple AI providers.
     *
     * @param  array<class-string<Contracts\SpotlightAiProvider>>  $providers
     */
    public static function registerAiProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            self::registerAiProvider($provider);
        }
    }

    /**
     * Get all registered AI providers sorted by priority.
     *
     * @return Collection<int, Contracts\SpotlightAiProvider>
     */
    public static function getAiProviders(): Collection
    {
        return collect(self::$aiProviders)
            ->map(fn (string $class) => app($class))
            ->sortByDesc(fn (Contracts\SpotlightAiProvider $provider) => $provider->priority())
            ->values();
    }

    /**
     * Handle an AI query using registered providers.
     *
     * @param  string  $query  The user's question
     * @param  callable(string): void  $stream  Callback to stream response chunks
     * @return string|null Final response or null
     */
    public static function handleAiQuery(string $query, callable $stream): ?string
    {
        foreach (self::getAiProviders() as $provider) {
            if ($provider->canHandle($query)) {
                return $provider->handle($query, $stream);
            }
        }

        // No provider found, return a default message
        $stream(__('noAiProvider', [], 'en') === 'noAiProvider'
            ? 'No AI provider configured. Please register an AI provider in your SpotlightServiceProvider.'
            : __('noAiProvider')
        );

        return null;
    }

    /* =========================================================================
     | Utility Methods
     |========================================================================= */

    /**
     * Clear all registered commands, providers, and AI providers.
     *
     * Useful for testing.
     */
    public static function clear(): void
    {
        self::$commands = [];
        self::$searchProviders = [];
        self::$aiProviders = [];
        self::$config = null;
    }

    /**
     * Get statistics about registered items.
     *
     * @return array{commands: int, providers: int, aiProviders: int, groups: int}
     */
    public static function stats(): array
    {
        return [
            'commands' => count(self::$commands),
            'providers' => count(self::$searchProviders),
            'aiProviders' => count(self::$aiProviders),
            'groups' => self::getGroupedCommands()->count(),
        ];
    }

    /**
     * Export all commands for debugging.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function export(): array
    {
        return self::getCommands()
            ->mapWithKeys(fn (SpotlightCommandContract $cmd) => [
                $cmd->getId() => $cmd->toArray(),
            ])
            ->toArray();
    }
}

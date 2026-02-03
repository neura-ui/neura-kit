<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Neura\Kit\Support\Spotlight\Contracts\SpotlightCommand as SpotlightCommandContract;

class SpotlightRegistry
{
    /**
     * @var array<string, class-string<SpotlightCommandContract>>
     */
    protected static array $commands = [];

    /**
     * @var array<string, callable>
     */
    protected static array $searchProviders = [];

    /**
     * Register a command class.
     *
     * @param  class-string<SpotlightCommandContract>  $commandClass
     */
    public static function register(string $commandClass): void
    {
        if (!is_subclass_of($commandClass, SpotlightCommandContract::class)) {
            throw new InvalidArgumentException(
                "Command [{$commandClass}] must implement " . SpotlightCommandContract::class
            );
        }

        $command = app($commandClass);
        static::$commands[$command->getId()] = $commandClass;
    }

    /**
     * Register multiple command classes.
     *
     * @param  array<class-string<SpotlightCommandContract>>  $commands
     */
    public static function registerMany(array $commands): void
    {
        foreach ($commands as $command) {
            static::register($command);
        }
    }

    /**
     * Register a search provider callback.
     */
    public static function registerSearchProvider(string $id, callable $provider): void
    {
        static::$searchProviders[$id] = $provider;
    }

    /**
     * Get all registered commands.
     *
     * @return Collection<SpotlightCommandContract>
     */
    public static function getCommands(): Collection
    {
        return collect(static::$commands)
            ->map(fn ($class) => app($class))
            ->filter(fn (SpotlightCommandContract $cmd) => $cmd->shouldBeShown())
            ->sortByDesc(fn (SpotlightCommand $cmd) => $cmd->getPriority());
    }

    /**
     * Get commands grouped by category.
     */
    public static function getGroupedCommands(): Collection
    {
        return static::getCommands()
            ->groupBy(fn (SpotlightCommandContract $cmd) => $cmd->getGroup());
    }

    /**
     * Search all commands and providers.
     */
    public static function search(string $query): Collection
    {
        $results = collect();

        // Search registered commands
        foreach (static::getCommands() as $command) {
            $commandResults = $command->search($query);
            if ($commandResults->isNotEmpty()) {
                $results = $results->merge($commandResults);
            }
        }

        // Search custom providers
        foreach (static::$searchProviders as $provider) {
            $providerResults = $provider($query);
            if ($providerResults instanceof Collection && $providerResults->isNotEmpty()) {
                $results = $results->merge($providerResults);
            }
        }

        return $results->sortByDesc('priority');
    }

    /**
     * Execute a command by ID.
     */
    public static function execute(string $commandId, array $params = []): mixed
    {
        if (!isset(static::$commands[$commandId])) {
            return null;
        }

        $command = app(static::$commands[$commandId]);

        return $command->execute($params);
    }

    /**
     * Clear all registered commands (useful for testing).
     */
    public static function clear(): void
    {
        static::$commands = [];
        static::$searchProviders = [];
    }
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Support\Collection;
use Neura\Kit\Support\Spotlight\Contracts\SpotlightCommand as SpotlightCommandContract;
use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;

/**
 * Base class for Spotlight commands.
 *
 * SIMPLE EXAMPLE - Navigate to a page:
 * ```php
 * class MyPageCommand extends SpotlightCommand
 * {
 *     protected string $name = 'Go to Dashboard';
 *     protected string $description = 'Open the dashboard';
 *     protected ?string $icon = 'home';
 *
 *     public function execute(array $params = []): mixed
 *     {
 *         return $this->goTo('/dashboard');
 *     }
 * }
 * ```
 *
 * SIMPLE EXAMPLE - Trigger an event:
 * ```php
 * class ToggleThemeCommand extends SpotlightCommand
 * {
 *     protected string $name = 'Toggle Dark Mode';
 *
 *     public function execute(array $params = []): mixed
 *     {
 *         return $this->emit('theme:toggle');
 *     }
 * }
 * ```
 */
abstract class SpotlightCommand implements SpotlightCommandContract
{
    /* =========================================================================
     | REQUIRED: Define these in your command
     |========================================================================= */

    /** Command name shown in Spotlight */
    protected string $name = '';

    /** Brief description */
    protected string $description = '';

    /* =========================================================================
     | OPTIONAL: Customize if needed
     |========================================================================= */

    /** Unique ID (auto-generated from class name if empty) */
    protected string $id = '';

    /** Heroicon name (e.g., 'home', 'user', 'cog') */
    protected ?string $icon = null;

    /** Keyboard shortcut (e.g., '⌘K') */
    protected ?string $shortcut = null;

    /** Category for grouping */
    protected SpotlightGroup $group = SpotlightGroup::General;

    /** Higher = shows first */
    protected int $priority = 0;

    /** Extra searchable words */
    protected array $keywords = [];

    /* =========================================================================
     | HELPER METHODS - Use these in execute()
     |========================================================================= */

    /**
     * Navigate to a URL.
     *
     * @example return $this->goTo('/dashboard');
     * @example return $this->goTo('https://google.com');
     */
    protected function goTo(string $url): SpotlightResult
    {
        return SpotlightResult::url(
            id: $this->getId(),
            title: $this->name,
            url: $url,
            description: $this->description,
            icon: $this->icon,
            group: $this->group,
        );
    }

    /**
     * Emit a browser event.
     *
     * @example return $this->emit('theme:toggle');
     * @example return $this->emit('user:logout');
     */
    protected function emit(string $event): SpotlightResult
    {
        return SpotlightResult::dispatch(
            id: $this->getId(),
            title: $this->name,
            event: $event,
            description: $this->description,
            icon: $this->icon,
            group: $this->group,
        );
    }

    /**
     * Call a Livewire method.
     *
     * @example return $this->wire('saveDocument');
     * @example return $this->wire('deleteItem', [123]);
     */
    protected function wire(string $method, array $params = []): SpotlightResult
    {
        return SpotlightResult::wire(
            id: $this->getId(),
            title: $this->name,
            method: $method,
            params: $params,
            description: $this->description,
            icon: $this->icon,
            group: $this->group,
        );
    }

    /**
     * Copy text to clipboard.
     *
     * @example return $this->copy('Some text');
     */
    protected function copy(string $text): SpotlightResult
    {
        return SpotlightResult::copy(
            id: $this->getId(),
            title: $this->name,
            text: $text,
            description: $this->description,
            icon: $this->icon ?? 'clipboard-document',
            group: $this->group,
        );
    }

    /**
     * Open a modal.
     *
     * @example return $this->modal('confirm-delete');
     */
    protected function modal(string $name, array $params = []): SpotlightResult
    {
        return SpotlightResult::modal(
            id: $this->getId(),
            title: $this->name,
            modalName: $name,
            params: $params,
            description: $this->description,
            icon: $this->icon,
            group: $this->group,
        );
    }

    /* =========================================================================
     | SEARCH HELPERS - Use these to define searchable items
     |========================================================================= */

    /**
     * Define a list of links (pages/URLs).
     * Use this in search() for navigation commands.
     *
     * @example
     * protected array $pages = [
     *     ['name' => 'Home', 'url' => '/', 'icon' => 'home'],
     *     ['name' => 'Settings', 'url' => '/settings', 'icon' => 'cog'],
     * ];
     *
     * public function search(string $query): Collection
     * {
     *     return $this->searchLinks($this->pages, $query);
     * }
     */
    protected function searchLinks(array $links, string $query): Collection
    {
        if (empty($query)) {
            return collect();
        }

        $q = strtolower($query);

        return collect($links)
            ->filter(fn ($link) => str_contains(strtolower($link['name'].($link['description'] ?? '')), $q))
            ->map(fn ($link) => SpotlightResult::url(
                id: $this->getId().'-'.str($link['name'])->slug(),
                title: $link['name'],
                url: $link['url'],
                description: $link['description'] ?? null,
                icon: $link['icon'] ?? $this->icon,
                group: $this->group,
                priority: $this->priority,
            ));
    }

    /**
     * Define a list of actions (events/methods).
     * Use this in search() for action commands.
     *
     * @example
     * protected array $actions = [
     *     ['name' => 'Save', 'event' => 'document:save', 'icon' => 'check'],
     *     ['name' => 'Export', 'event' => 'document:export', 'icon' => 'download'],
     * ];
     *
     * public function search(string $query): Collection
     * {
     *     return $this->searchActions($this->actions, $query);
     * }
     */
    protected function searchActions(array $actions, string $query): Collection
    {
        if (empty($query)) {
            return collect();
        }

        $q = strtolower($query);

        return collect($actions)
            ->filter(fn ($a) => str_contains(strtolower($a['name'].($a['description'] ?? '')), $q))
            ->map(fn ($a) => SpotlightResult::dispatch(
                id: $this->getId().'-'.str($a['name'])->slug(),
                title: $a['name'],
                event: $a['event'],
                description: $a['description'] ?? null,
                icon: $a['icon'] ?? $this->icon,
                group: $this->group,
                priority: $this->priority,
            ));
    }

    /* =========================================================================
     | OVERRIDE THESE IF NEEDED
     |========================================================================= */

    /**
     * What happens when this command is selected.
     * Override this to define the command behavior.
     */
    public function execute(array $params = []): mixed
    {
        return null;
    }

    /**
     * Return search results for a query.
     * Override this if your command provides searchable items.
     */
    public function search(string $query): Collection
    {
        return collect();
    }

    /**
     * Return false to hide this command.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }

    /* =========================================================================
     | INTERNAL - You don't need to touch these
     |========================================================================= */

    public function getId(): string
    {
        return $this->id ?: str(class_basename(static::class))
            ->replace('Command', '')
            ->kebab()
            ->toString();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    public function getGroup(): SpotlightGroup
    {
        return $this->group;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function matches(string $query): bool
    {
        if (empty($query)) {
            return true;
        }

        $q = strtolower($query);
        $text = strtolower($this->name.' '.$this->description.' '.implode(' ', $this->keywords));

        return str_contains($text, $q);
    }

    public function toResult(): SpotlightResult
    {
        return SpotlightResult::command(
            id: $this->getId(),
            title: $this->getName(),
            commandId: $this->getId(),
            description: $this->getDescription(),
            icon: $this->getIcon(),
            shortcut: $this->getShortcut(),
            group: $this->getGroup(),
            priority: $this->getPriority(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'shortcut' => $this->getShortcut(),
            'group' => $this->getGroup()->value,
            'priority' => $this->getPriority(),
            'keywords' => $this->getKeywords(),
        ];
    }
}

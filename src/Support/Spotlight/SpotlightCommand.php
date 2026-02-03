<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Support\Collection;
use Neura\Kit\Support\Spotlight\Contracts\SpotlightCommand as SpotlightCommandContract;

abstract class SpotlightCommand implements SpotlightCommandContract
{
    /**
     * Command unique identifier.
     */
    protected string $id = '';

    /**
     * Command display name.
     */
    protected string $name = '';

    /**
     * Command description.
     */
    protected string $description = '';

    /**
     * Heroicon name for the command.
     */
    protected ?string $icon = null;

    /**
     * Keyboard shortcut.
     */
    protected ?string $shortcut = null;

    /**
     * Command group/category.
     */
    protected string $group = 'general';

    /**
     * Priority for ordering (higher = first).
     */
    protected int $priority = 0;

    public function getId(): string
    {
        return $this->id ?: class_basename(static::class);
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

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Override to add custom visibility logic.
     */
    public function shouldBeShown(): bool
    {
        return true;
    }

    /**
     * Override to provide searchable results.
     * Return a collection of SpotlightResult objects.
     */
    public function search(string $query): Collection
    {
        return collect();
    }

    /**
     * Override to handle command execution.
     */
    public function execute(array $params = []): mixed
    {
        return null;
    }

    /**
     * Convert command to array for JSON.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'shortcut' => $this->getShortcut(),
            'group' => $this->getGroup(),
            'priority' => $this->getPriority(),
        ];
    }
}

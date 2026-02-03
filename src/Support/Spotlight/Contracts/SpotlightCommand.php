<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Contracts;

use Illuminate\Support\Collection;

interface SpotlightCommand
{
    /**
     * Get the unique identifier for this command.
     */
    public function getId(): string;

    /**
     * Get the display name of the command.
     */
    public function getName(): string;

    /**
     * Get the description of the command.
     */
    public function getDescription(): string;

    /**
     * Get the icon name (Heroicons).
     */
    public function getIcon(): ?string;

    /**
     * Get keyboard shortcut (e.g., "Cmd+K", "Ctrl+Shift+P").
     */
    public function getShortcut(): ?string;

    /**
     * Get the group/category this command belongs to.
     */
    public function getGroup(): string;

    /**
     * Check if this command should be shown.
     */
    public function shouldBeShown(): bool;

    /**
     * Search within this command's results.
     */
    public function search(string $query): Collection;

    /**
     * Execute the command with optional parameters.
     */
    public function execute(array $params = []): mixed;
}

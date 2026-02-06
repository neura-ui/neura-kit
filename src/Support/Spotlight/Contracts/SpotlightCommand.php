<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Contracts;

use Illuminate\Support\Collection;
use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;

/**
 * Contract for Spotlight commands.
 *
 * Commands are registered with the SpotlightRegistry and can be searched,
 * displayed, and executed by the Spotlight component.
 */
interface SpotlightCommand
{
    /**
     * Get the unique identifier for this command.
     *
     * This ID is used to reference the command when executing it.
     */
    public function getId(): string;

    /**
     * Get the display name of the command.
     *
     * This is shown to users in the Spotlight results.
     */
    public function getName(): string;

    /**
     * Get the description of the command.
     *
     * Optional additional context shown below the command name.
     */
    public function getDescription(): string;

    /**
     * Get the icon name (Heroicons).
     *
     * Used to display an icon next to the command.
     */
    public function getIcon(): ?string;

    /**
     * Get keyboard shortcut (e.g., "Cmd+K", "Ctrl+Shift+P").
     *
     * Displayed as a hint in the command palette.
     */
    public function getShortcut(): ?string;

    /**
     * Get the group this command belongs to.
     *
     * Used for organizing and grouping commands in the UI.
     */
    public function getGroup(): SpotlightGroup;

    /**
     * Get the priority for sorting (higher = first).
     */
    public function getPriority(): int;

    /**
     * Check if this command should be shown.
     *
     * Use this for permission checks or conditional visibility.
     */
    public function shouldBeShown(): bool;

    /**
     * Search within this command's results.
     *
     * Return a collection of SpotlightResult objects matching the query.
     *
     * @return Collection<int, \Neura\Kit\Support\Spotlight\SpotlightResult>
     */
    public function search(string $query): Collection;

    /**
     * Execute the command with optional parameters.
     *
     * @param  array<mixed>  $params
     */
    public function execute(array $params = []): mixed;

    /**
     * Convert command to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

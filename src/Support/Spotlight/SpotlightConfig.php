<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;
use Neura\Kit\Support\Spotlight\Enums\SpotlightMode;

/**
 * Configuration container for the Spotlight component.
 *
 * This class provides a type-safe way to configure the Spotlight
 * with sensible defaults and validation.
 */
final class SpotlightConfig
{
    /**
     * @param  SpotlightMode  $defaultMode  Default mode when opening
     * @param  int  $debounceMs  Debounce delay for search in milliseconds
     * @param  int  $maxResults  Maximum number of results to display
     * @param  bool  $showModes  Whether to show mode tabs in header
     * @param  bool  $showFooter  Whether to show keyboard hints footer
     * @param  bool  $closeOnEscape  Whether ESC key closes the spotlight
     * @param  bool  $closeOnClickOutside  Whether clicking outside closes the spotlight
     * @param  bool  $trapFocus  Whether to trap focus within the spotlight
     * @param  bool  $enableKeyboardNavigation  Enable arrow key navigation
     * @param  bool  $enableGroupHeaders  Show group headers in results
     * @param  array<SpotlightMode>  $enabledModes  Which modes are enabled
     * @param  array<SpotlightGroup>  $enabledGroups  Which groups are enabled
     * @param  array<string, string>  $placeholders  Custom placeholders per mode
     * @param  array<string, string>  $shortcuts  Keyboard shortcuts config
     * @param  string|null  $aiPlaceholder  Custom AI mode placeholder
     * @param  string|null  $aiView  Custom Blade view for AI response rendering
     * @param  string|null  $emptyStateMessage  Message when no results
     * @param  bool  $persistRecentSearches  Store recent searches
     * @param  int  $maxRecentSearches  Max number of recent searches to store
     * @param  bool  $showRecentOnEmpty  Show recent searches when query is empty
     * @param  string  $panelPosition  Panel position: 'top' or 'center'
     * @param  string  $panelSize  Panel size: 'sm', 'md', 'lg', 'xl'
     */
    public function __construct(
        public readonly SpotlightMode $defaultMode = SpotlightMode::Search,
        public readonly int $debounceMs = 150,
        public readonly int $maxResults = 20,
        public readonly bool $showModes = true,
        public readonly bool $showFooter = true,
        public readonly bool $closeOnEscape = true,
        public readonly bool $closeOnClickOutside = true,
        public readonly bool $trapFocus = true,
        public readonly bool $enableKeyboardNavigation = true,
        public readonly bool $enableGroupHeaders = true,
        public readonly array $enabledModes = [],
        public readonly array $enabledGroups = [],
        public readonly array $placeholders = [],
        public readonly array $shortcuts = [],
        public readonly ?string $aiPlaceholder = null,
        public readonly ?string $aiView = null,
        public readonly ?string $emptyStateMessage = null,
        public readonly bool $persistRecentSearches = false,
        public readonly int $maxRecentSearches = 5,
        public readonly bool $showRecentOnEmpty = true,
        public readonly string $panelPosition = 'top',
        public readonly string $panelSize = 'lg',
    ) {}

    /**
     * Create from array (useful for config files).
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            defaultMode: isset($config['default_mode'])
                ? SpotlightMode::from($config['default_mode'])
                : SpotlightMode::Search,
            debounceMs: $config['debounce_ms'] ?? 150,
            maxResults: $config['max_results'] ?? 20,
            showModes: $config['show_modes'] ?? true,
            showFooter: $config['show_footer'] ?? true,
            closeOnEscape: $config['close_on_escape'] ?? true,
            closeOnClickOutside: $config['close_on_click_outside'] ?? true,
            trapFocus: $config['trap_focus'] ?? true,
            enableKeyboardNavigation: $config['enable_keyboard_navigation'] ?? true,
            enableGroupHeaders: $config['enable_group_headers'] ?? true,
            enabledModes: array_map(
                fn ($m) => $m instanceof SpotlightMode ? $m : SpotlightMode::from($m),
                $config['enabled_modes'] ?? SpotlightMode::cases()
            ),
            enabledGroups: array_map(
                fn ($g) => $g instanceof SpotlightGroup ? $g : SpotlightGroup::from($g),
                $config['enabled_groups'] ?? SpotlightGroup::cases()
            ),
            placeholders: $config['placeholders'] ?? [],
            shortcuts: $config['shortcuts'] ?? self::defaultShortcuts(),
            aiPlaceholder: $config['ai_placeholder'] ?? null,
            aiView: $config['ai_view'] ?? null,
            emptyStateMessage: $config['empty_state_message'] ?? null,
            persistRecentSearches: $config['persist_recent_searches'] ?? false,
            maxRecentSearches: $config['max_recent_searches'] ?? 5,
            showRecentOnEmpty: $config['show_recent_on_empty'] ?? true,
            panelPosition: $config['panel_position'] ?? 'top',
            panelSize: $config['panel_size'] ?? 'lg',
        );
    }

    /**
     * Get default keyboard shortcuts.
     *
     * @return array<string, string>
     */
    public static function defaultShortcuts(): array
    {
        return [
            'open' => 'cmd+k',
            'openCommand' => 'cmd+p',
            'openAi' => 'cmd+i',
            'close' => 'escape',
            'selectNext' => 'ArrowDown',
            'selectPrev' => 'ArrowUp',
            'execute' => 'Enter',
            'nextMode' => 'Tab',
        ];
    }

    /**
     * Get the placeholder for a specific mode.
     */
    public function getPlaceholder(SpotlightMode $mode): string
    {
        // Check custom placeholders array first
        if (! empty($this->placeholders[$mode->value])) {
            return $this->placeholders[$mode->value];
        }

        // Check dedicated aiPlaceholder for AI mode
        if ($mode === SpotlightMode::Ai && $this->aiPlaceholder !== null) {
            return $this->aiPlaceholder;
        }

        return $mode->defaultPlaceholder();
    }

    /**
     * Check if a mode is enabled.
     */
    public function isModeEnabled(SpotlightMode $mode): bool
    {
        if (empty($this->enabledModes)) {
            return true;
        }

        return in_array($mode, $this->enabledModes, true);
    }

    /**
     * Check if a group is enabled.
     */
    public function isGroupEnabled(SpotlightGroup $group): bool
    {
        if (empty($this->enabledGroups)) {
            return true;
        }

        return in_array($group, $this->enabledGroups, true);
    }

    /**
     * Get enabled modes.
     *
     * @return array<SpotlightMode>
     */
    public function getEnabledModes(): array
    {
        return empty($this->enabledModes)
            ? SpotlightMode::cases()
            : $this->enabledModes;
    }

    /**
     * Get the panel max-width class based on size.
     */
    public function getPanelSizeClass(): string
    {
        return match ($this->panelSize) {
            'sm' => 'max-w-md',
            'md' => 'max-w-lg',
            'lg' => 'max-w-2xl',
            'xl' => 'max-w-3xl',
            'full' => 'max-w-full mx-4',
            default => 'max-w-2xl',
        };
    }

    /**
     * Get the panel position class.
     */
    public function getPanelPositionClass(): string
    {
        return match ($this->panelPosition) {
            'top' => 'pt-[15vh]',
            'center' => 'items-center',
            default => 'pt-[15vh]',
        };
    }

    /**
     * Convert to array for JavaScript.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'defaultMode' => $this->defaultMode->value,
            'debounceMs' => $this->debounceMs,
            'maxResults' => $this->maxResults,
            'showModes' => $this->showModes,
            'showFooter' => $this->showFooter,
            'closeOnEscape' => $this->closeOnEscape,
            'closeOnClickOutside' => $this->closeOnClickOutside,
            'trapFocus' => $this->trapFocus,
            'enableKeyboardNavigation' => $this->enableKeyboardNavigation,
            'enableGroupHeaders' => $this->enableGroupHeaders,
            'enabledModes' => array_map(fn ($m) => $m->value, $this->getEnabledModes()),
            'shortcuts' => $this->shortcuts ?: self::defaultShortcuts(),
            'panelPosition' => $this->panelPosition,
            'panelSize' => $this->panelSize,
            'modes' => SpotlightMode::toArray(),
            'groups' => SpotlightGroup::toArray(),
        ];
    }

    /**
     * Convert to JSON for JavaScript.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

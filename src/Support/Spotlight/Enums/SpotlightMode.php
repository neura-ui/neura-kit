<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Enums;

/**
 * Defines the available modes for the Spotlight component.
 */
enum SpotlightMode: string
{
    case Search = 'search';
    case Command = 'command';
    case Ai = 'ai';

    /**
     * Get the placeholder text for this mode (translated).
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::Search => __('searchPlaceholder'),
            self::Command => __('commandPlaceholder'),
            self::Ai => __('aiPlaceholder'),
        };
    }

    /**
     * Get the default placeholder text.
     */
    public function defaultPlaceholder(): string
    {
        $translated = $this->placeholder();
        
        // If translation key is returned (not found), use fallback
        if (in_array($translated, ['searchPlaceholder', 'commandPlaceholder', 'aiPlaceholder'])) {
            return match ($this) {
                self::Search => 'Search anything...',
                self::Command => 'Type a command...',
                self::Ai => 'Ask AI anything...',
            };
        }
        
        return $translated;
    }

    /**
     * Get the icon name for this mode.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Search => 'magnifying-glass',
            self::Command => 'command-line',
            self::Ai => 'sparkles',
        };
    }

    /**
     * Get the keyboard shortcut for this mode.
     */
    public function shortcut(): string
    {
        return match ($this) {
            self::Search => 'Cmd+K',
            self::Command => 'Cmd+P',
            self::Ai => 'Cmd+I',
        };
    }

    /**
     * Get the label for this mode (translated).
     */
    public function label(): string
    {
        return match ($this) {
            self::Search => __('search'),
            self::Command => __('commands'),
            self::Ai => __('ai'),
        };
    }

    /**
     * Get the default label.
     */
    public function defaultLabel(): string
    {
        $translated = $this->label();
        
        // If translation key is returned (not found), use fallback
        if (in_array($translated, ['search', 'commands', 'ai'])) {
            return match ($this) {
                self::Search => 'Search',
                self::Command => 'Commands',
                self::Ai => 'AI',
            };
        }
        
        return $translated;
    }

    /**
     * Check if this mode supports search.
     */
    public function supportsSearch(): bool
    {
        return match ($this) {
            self::Search, self::Command => true,
            self::Ai => false,
        };
    }

    /**
     * Check if this mode should show results list.
     */
    public function showsResults(): bool
    {
        return match ($this) {
            self::Search, self::Command => true,
            self::Ai => false,
        };
    }

    /**
     * Get the next mode in rotation.
     */
    public function next(): self
    {
        return match ($this) {
            self::Search => self::Command,
            self::Command => self::Ai,
            self::Ai => self::Search,
        };
    }

    /**
     * Get all modes as array for frontend.
     *
     * @return array<string, array{value: string, label: string, icon: string, shortcut: string}>
     */
    public static function toArray(): array
    {
        return array_map(
            fn (self $mode) => [
                'value' => $mode->value,
                'label' => $mode->defaultLabel(),
                'icon' => $mode->icon(),
                'shortcut' => $mode->shortcut(),
            ],
            self::cases()
        );
    }
}

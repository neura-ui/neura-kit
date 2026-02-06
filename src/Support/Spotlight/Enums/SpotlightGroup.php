<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Enums;

/**
 * Defines the available groups/categories for spotlight results.
 */
enum SpotlightGroup: string
{
    case General = 'general';
    case Navigation = 'navigation';
    case Commands = 'commands';
    case Actions = 'actions';
    case Settings = 'settings';
    case Content = 'content';
    case Users = 'users';
    case Recent = 'recent';
    case Favorites = 'favorites';
    case Help = 'help';

    /**
     * Get the display label for this group.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => __('neura-kit::spotlight.group.general'),
            self::Navigation => __('neura-kit::spotlight.group.navigation'),
            self::Commands => __('neura-kit::spotlight.group.commands'),
            self::Actions => __('neura-kit::spotlight.group.actions'),
            self::Settings => __('neura-kit::spotlight.group.settings'),
            self::Content => __('neura-kit::spotlight.group.content'),
            self::Users => __('neura-kit::spotlight.group.users'),
            self::Recent => __('neura-kit::spotlight.group.recent'),
            self::Favorites => __('neura-kit::spotlight.group.favorites'),
            self::Help => __('neura-kit::spotlight.group.help'),
        };
    }

    /**
     * Get the default label (fallback without translation).
     */
    public function defaultLabel(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Navigation => 'Navigation',
            self::Commands => 'Commands',
            self::Actions => 'Quick Actions',
            self::Settings => 'Settings',
            self::Content => 'Content',
            self::Users => 'Users',
            self::Recent => 'Recent',
            self::Favorites => 'Favorites',
            self::Help => 'Help',
        };
    }

    /**
     * Get the icon for this group.
     */
    public function icon(): string
    {
        return match ($this) {
            self::General => 'squares-2x2',
            self::Navigation => 'arrow-right-circle',
            self::Commands => 'command-line',
            self::Actions => 'bolt',
            self::Settings => 'cog-6-tooth',
            self::Content => 'document-text',
            self::Users => 'users',
            self::Recent => 'clock',
            self::Favorites => 'star',
            self::Help => 'question-mark-circle',
        };
    }

    /**
     * Get the priority for sorting (higher = first).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Recent => 100,
            self::Favorites => 90,
            self::Actions => 80,
            self::Commands => 70,
            self::Navigation => 60,
            self::Content => 50,
            self::Users => 40,
            self::Settings => 30,
            self::Help => 20,
            self::General => 10,
        };
    }

    /**
     * Create a group from string, with fallback to General.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::General;
    }

    /**
     * Get all groups sorted by priority.
     *
     * @return array<self>
     */
    public static function sorted(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $b->priority() <=> $a->priority());

        return $cases;
    }

    /**
     * Get all groups as array for frontend.
     *
     * @return array<string, array{value: string, label: string, icon: string, priority: int}>
     */
    public static function toArray(): array
    {
        return array_map(
            fn (self $group) => [
                'value' => $group->value,
                'label' => $group->defaultLabel(),
                'icon' => $group->icon(),
                'priority' => $group->priority(),
            ],
            self::cases()
        );
    }
}

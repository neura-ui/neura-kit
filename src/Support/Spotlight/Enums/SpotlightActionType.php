<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Enums;

/**
 * Defines the types of actions a spotlight result can perform.
 */
enum SpotlightActionType: string
{
    /**
     * Navigate to a URL (internal or external).
     */
    case Url = 'url';

    /**
     * Execute a registered command.
     */
    case Command = 'command';

    /**
     * Dispatch a Livewire/browser event.
     */
    case Dispatch = 'dispatch';

    /**
     * Call a Livewire wire method.
     */
    case Wire = 'wire';

    /**
     * Execute JavaScript code.
     */
    case Javascript = 'js';

    /**
     * Copy text to clipboard.
     */
    case Copy = 'copy';

    /**
     * Open a modal.
     */
    case Modal = 'modal';

    /**
     * Custom callback action.
     */
    case Callback = 'callback';

    /**
     * Get the action prefix used in result strings.
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Url => '',
            self::Command => 'command:',
            self::Dispatch => 'dispatch:',
            self::Wire => 'wire:',
            self::Javascript => 'js:',
            self::Copy => 'copy:',
            self::Modal => 'modal:',
            self::Callback => 'callback:',
        };
    }

    /**
     * Parse an action string and return the type and value.
     *
     * @return array{type: self, value: string}
     */
    public static function parse(string $action): array
    {
        foreach (self::cases() as $type) {
            $prefix = $type->prefix();
            if ($prefix !== '' && str_starts_with($action, $prefix)) {
                return [
                    'type' => $type,
                    'value' => substr($action, strlen($prefix)),
                ];
            }
        }

        // Default to URL if no prefix matched
        return [
            'type' => self::Url,
            'value' => $action,
        ];
    }

    /**
     * Create an action string from type and value.
     */
    public function createAction(string $value): string
    {
        return $this->prefix().$value;
    }

    /**
     * Check if this action type requires closing the spotlight.
     */
    public function shouldCloseSpotlight(): bool
    {
        return match ($this) {
            self::Url, self::Modal => true,
            self::Command, self::Dispatch, self::Wire, self::Copy => true,
            self::Javascript, self::Callback => false,
        };
    }

    /**
     * Get the description for this action type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Url => 'Navigate to URL',
            self::Command => 'Execute command',
            self::Dispatch => 'Dispatch event',
            self::Wire => 'Call Livewire method',
            self::Javascript => 'Execute JavaScript',
            self::Copy => 'Copy to clipboard',
            self::Modal => 'Open modal',
            self::Callback => 'Execute callback',
        };
    }
}

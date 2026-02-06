<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Neura\Kit\Support\Spotlight\Enums\SpotlightActionType;
use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;

/**
 * Represents a single result item in the Spotlight.
 *
 * @implements Arrayable<string, mixed>
 */
final class SpotlightResult implements Arrayable, JsonSerializable
{
    /**
     * @param  string  $id  Unique identifier for this result
     * @param  string  $title  Display title
     * @param  string|null  $description  Optional description text
     * @param  string|null  $icon  Heroicon name
     * @param  string|null  $url  URL for navigation actions
     * @param  SpotlightActionType|null  $actionType  The type of action to perform
     * @param  string|null  $actionValue  The value for the action (command id, event name, etc.)
     * @param  array<mixed>  $params  Additional parameters for the action
     * @param  SpotlightGroup  $group  The category/group this result belongs to
     * @param  int  $priority  Priority for sorting (higher = first)
     * @param  array<string, mixed>  $meta  Additional metadata
     * @param  string|null  $shortcut  Keyboard shortcut hint
     * @param  string|null  $badge  Optional badge text
     * @param  bool  $disabled  Whether this result is disabled
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly ?string $url = null,
        public readonly ?SpotlightActionType $actionType = null,
        public readonly ?string $actionValue = null,
        public readonly array $params = [],
        public readonly SpotlightGroup $group = SpotlightGroup::General,
        public readonly int $priority = 0,
        public readonly array $meta = [],
        public readonly ?string $shortcut = null,
        public readonly ?string $badge = null,
        public readonly bool $disabled = false,
    ) {}

    /* =========================================================================
     | Factory Methods
     |========================================================================= */

    /**
     * Create a result that navigates to a URL.
     */
    public static function url(
        string $id,
        string $title,
        string $url,
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Navigation,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'arrow-top-right-on-square',
            url: $url,
            actionType: SpotlightActionType::Url,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Create a result that executes a command.
     *
     * @param  array<mixed>  $params
     */
    public static function command(
        string $id,
        string $title,
        string $commandId,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
        ?string $shortcut = null,
        SpotlightGroup $group = SpotlightGroup::Commands,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'command-line',
            actionType: SpotlightActionType::Command,
            actionValue: $commandId,
            params: $params,
            group: $group,
            priority: $priority,
            shortcut: $shortcut,
        );
    }

    /**
     * Create a result that dispatches an event.
     *
     * @param  array<mixed>  $params
     */
    public static function dispatch(
        string $id,
        string $title,
        string $event,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Actions,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'bolt',
            actionType: SpotlightActionType::Dispatch,
            actionValue: $event,
            params: $params,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Create a result that calls a Livewire method.
     *
     * @param  array<mixed>  $params
     */
    public static function wire(
        string $id,
        string $title,
        string $method,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Actions,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'cursor-arrow-rays',
            actionType: SpotlightActionType::Wire,
            actionValue: $method,
            params: $params,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Create a result that copies text to clipboard.
     */
    public static function copy(
        string $id,
        string $title,
        string $text,
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Actions,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description ?? 'Copy to clipboard',
            icon: $icon ?? 'clipboard-document',
            actionType: SpotlightActionType::Copy,
            actionValue: $text,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Create a result that opens a modal.
     *
     * @param  array<mixed>  $params
     */
    public static function modal(
        string $id,
        string $title,
        string $modalName,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Actions,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'rectangle-stack',
            actionType: SpotlightActionType::Modal,
            actionValue: $modalName,
            params: $params,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Create a result that executes JavaScript.
     */
    public static function javascript(
        string $id,
        string $title,
        string $code,
        ?string $description = null,
        ?string $icon = null,
        SpotlightGroup $group = SpotlightGroup::Actions,
        int $priority = 0,
    ): self {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon ?? 'code-bracket',
            actionType: SpotlightActionType::Javascript,
            actionValue: $code,
            group: $group,
            priority: $priority,
        );
    }

    /**
     * Legacy factory method for backwards compatibility.
     *
     * @param  array<mixed>  $params
     *
     * @deprecated Use specific factory methods instead
     */
    public static function action(
        string $id,
        string $title,
        string $action,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
    ): self {
        $parsed = SpotlightActionType::parse($action);

        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon,
            actionType: $parsed['type'],
            actionValue: $parsed['value'],
            params: $params,
        );
    }

    /**
     * Legacy factory method for events.
     *
     * @param  array<mixed>  $params
     *
     * @deprecated Use dispatch() instead
     */
    public static function event(
        string $id,
        string $title,
        string $event,
        array $params = [],
        ?string $description = null,
        ?string $icon = null,
    ): self {
        return self::dispatch(
            id: $id,
            title: $title,
            event: $event,
            params: $params,
            description: $description,
            icon: $icon,
        );
    }

    /* =========================================================================
     | Builder Methods
     |========================================================================= */

    /**
     * Create a copy with a different group.
     */
    public function withGroup(SpotlightGroup $group): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            icon: $this->icon,
            url: $this->url,
            actionType: $this->actionType,
            actionValue: $this->actionValue,
            params: $this->params,
            group: $group,
            priority: $this->priority,
            meta: $this->meta,
            shortcut: $this->shortcut,
            badge: $this->badge,
            disabled: $this->disabled,
        );
    }

    /**
     * Create a copy with a different priority.
     */
    public function withPriority(int $priority): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            icon: $this->icon,
            url: $this->url,
            actionType: $this->actionType,
            actionValue: $this->actionValue,
            params: $this->params,
            group: $this->group,
            priority: $priority,
            meta: $this->meta,
            shortcut: $this->shortcut,
            badge: $this->badge,
            disabled: $this->disabled,
        );
    }

    /**
     * Create a copy with a badge.
     */
    public function withBadge(string $badge): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            icon: $this->icon,
            url: $this->url,
            actionType: $this->actionType,
            actionValue: $this->actionValue,
            params: $this->params,
            group: $this->group,
            priority: $this->priority,
            meta: $this->meta,
            shortcut: $this->shortcut,
            badge: $badge,
            disabled: $this->disabled,
        );
    }

    /**
     * Create a disabled copy.
     */
    public function disabled(bool $disabled = true): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            icon: $this->icon,
            url: $this->url,
            actionType: $this->actionType,
            actionValue: $this->actionValue,
            params: $this->params,
            group: $this->group,
            priority: $this->priority,
            meta: $this->meta,
            shortcut: $this->shortcut,
            badge: $this->badge,
            disabled: $disabled,
        );
    }

    /* =========================================================================
     | Helpers
     |========================================================================= */

    /**
     * Get the full action string for this result.
     */
    public function getAction(): ?string
    {
        if ($this->url !== null) {
            return $this->url;
        }

        if ($this->actionType !== null && $this->actionValue !== null) {
            return $this->actionType->createAction($this->actionValue);
        }

        return null;
    }

    /**
     * Check if this result should close the spotlight when executed.
     */
    public function shouldCloseSpotlight(): bool
    {
        if ($this->url !== null) {
            return true;
        }

        return $this->actionType?->shouldCloseSpotlight() ?? true;
    }

    /* =========================================================================
     | Serialization
     |========================================================================= */

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'url' => $this->url,
            'action' => $this->getAction(),
            'actionType' => $this->actionType?->value,
            'params' => $this->params ?: null,
            'group' => $this->group->value,
            'priority' => $this->priority,
            'meta' => $this->meta ?: null,
            'shortcut' => $this->shortcut,
            'badge' => $this->badge,
            'disabled' => $this->disabled ?: null,
        ], fn ($v) => $v !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

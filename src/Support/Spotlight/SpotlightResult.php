<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class SpotlightResult implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly ?string $url = null,
        public readonly ?string $action = null,
        public readonly array $params = [],
        public readonly ?string $group = null,
        public readonly int $priority = 0,
        public readonly array $meta = [],
    ) {}

    /**
     * Create a result that navigates to a URL.
     */
    public static function url(string $id, string $title, string $url, ?string $description = null, ?string $icon = null): self
    {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon,
            url: $url,
        );
    }

    /**
     * Create a result that executes a Livewire action.
     */
    public static function action(string $id, string $title, string $action, array $params = [], ?string $description = null, ?string $icon = null): self
    {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon,
            action: $action,
            params: $params,
        );
    }

    /**
     * Create a result that dispatches a browser event.
     */
    public static function event(string $id, string $title, string $event, array $params = [], ?string $description = null, ?string $icon = null): self
    {
        return new self(
            id: $id,
            title: $title,
            description: $description,
            icon: $icon,
            action: "dispatch:{$event}",
            params: $params,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'url' => $this->url,
            'action' => $this->action,
            'params' => $this->params ?: null,
            'group' => $this->group,
            'priority' => $this->priority,
            'meta' => $this->meta ?: null,
        ], fn ($v) => $v !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

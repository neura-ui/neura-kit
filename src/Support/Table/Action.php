<?php

namespace Neura\Kit\Support\Table;

use Closure;
use Illuminate\Contracts\View\View;

class Action
{
    public function __construct(
        public string $label,
        public ?string $key = null,
        public ?string $icon = null,
        public ?string $route = null,
        public ?string $action = null,
        public ?string $url = null,
        public ?string $wireClick = null,
        public ?string $tooltip = null,
        public string $variant = 'primary',
        public string $size = 'sm',
        public array|Closure|null $params = null,
        public Closure|bool|null $visible = null,
        public ?Closure $queryCondition = null,
    ) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function key(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function icon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function route(string $route, array|Closure|null $params = null): self
    {
        $this->route = $route;

        if ($params !== null) {
            $this->params = $params;
        }

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function wireClick(string $wireClick, array|Closure|null $params = null): self
    {
        $this->wireClick = $wireClick;

        if ($params !== null) {
            $this->params = $params;
        }

        return $this;
    }

    public function tooltip(string $tooltip): self
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function variant(string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }

    public function size(string $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function params(array|Closure $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function visible(Closure|bool $condition): self
    {
        $this->visible = $condition;
        return $this;
    }

    public function queryCondition(Closure $condition): self
    {
        $this->queryCondition = $condition;
        return $this;
    }

    public function getKey(): string
    {
        return $this->key ?? strtolower(str_replace(' ', '_', $this->label));
    }

    public function getAction(): string
    {
        if ($this->wireClick !== null) {
            return $this->wireClick;
        }

        $key = $this->getKey();

        return match($key) {
            'delete_selected', 'delete' => 'bulkDelete',
            default => 'bulk' . str_replace('_', '', ucwords($key, '_'))
        };
    }

    public function toArray(): array
    {
        return array_filter([
            'key' => $this->getKey(),
            'label' => $this->label,
            'variant' => $this->variant,
            'size' => $this->size,
            'wireClick' => $this->wireClick,
            'action' => $this->action ?? $this->getAction(),
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'tooltip' => $this->tooltip,
            'params' => $this->params,
            'visible' => $this->visible,
            'queryCondition' => $this->queryCondition,
        ], fn($value) => $value !== null);
    }

    public function toView(): View
    {
        return view('neura::table.action', $this->toArray());
    }

    public function render(): string
    {
        return $this->toView()->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}

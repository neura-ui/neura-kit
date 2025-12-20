<?php

namespace Neura\Kit\Support\Table;

use Closure;
use Illuminate\Contracts\View\View;

class Action
{
    public string $label;

    public ?string $icon = null;

    public ?string $route = null;

    public ?string $url = null;

    public ?string $wireClick = null;

    public string $variant = 'primary';

    public string $size = 'sm';

    public ?array $params = null;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public static function make(string $label): self
    {
        return new static($label);
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

    public function route(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function wireClick(string $wireClick): self
    {
        $this->wireClick = $wireClick;

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

    public function toView(): View
    {
        $data = [
            'label' => $this->label,
            'icon' => $this->icon,
            'route' => $this->route,
            'url' => $this->url,
            'wireClick' => $this->wireClick,
            'variant' => $this->variant,
            'size' => $this->size,
        ];

        if ($this->params !== null) {
            $data['params'] = $this->params;
        }

        return view('neura::table.action', $data);
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


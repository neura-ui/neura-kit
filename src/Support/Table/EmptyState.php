<?php

namespace Neura\Kit\Support\Table;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EmptyState
{
    public ?string $message = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $actionLabel = null;

    public ?string $actionUrl = null;

    public ?string $actionWireClick = null;

    public ?string $view = null;

    public ?string $html = null;

    public static function make(): static
    {
        return new static();
    }

    public function message(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the action with a JavaScript expression for x-on:click
     */
    public function action(string $label, $action): static
    {
        $this->actionLabel = $label;
        $this->actionUrl = $action;
        return $this;
    }

    /**
     * Set the action with a Livewire method name (cleaner syntax)
     */
    public function wireClick(string $label, string $method): static
    {
        $this->actionLabel = $label;
        $this->actionWireClick = $method;
        return $this;
    }

    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function render(): string|View|Htmlable
    {
        if ($this->view) {
            return view($this->view);
        }

        if ($this->html) {
            $html = Blade::render($this->html);
            return new HtmlString($html);
        }

        $html = Blade::render('
            <div class="text-center py-8">
                @if($title)
                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2">{{ $title }}</h3>
                @endif
                <p class="text-neutral-500 dark:text-neutral-400 mb-4">
                    {{ $message ?? $description ?? "No results found." }}
                </p>
                @if($actionLabel && $actionWireClick)
                    <neura::button wire:click="{{ $actionWireClick }}">
                        {{ $actionLabel }}
                    </neura::button>
                @elseif($actionLabel && $actionUrl)
                    <neura::button href="{{ $actionUrl }}">
                        {{ $actionLabel }}
                    </neura::button>
                @endif
            </div>
        ', [
            'title' => $this->title,
            'message' => $this->message,
            'description' => $this->description,
            'actionLabel' => $this->actionLabel,
            'actionUrl' => $this->actionUrl,
            'actionWireClick' => $this->actionWireClick,
        ]);

        return new HtmlString($html);
    }
}


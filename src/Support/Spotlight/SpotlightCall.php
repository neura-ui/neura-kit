<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Livewire\Component;
use Neura\Kit\Support\Spotlight\Enums\SpotlightMode;

/**
 * Fluent API for controlling the Spotlight from Livewire components.
 *
 * @example
 * ```php
 * // In a Livewire component
 * $this->spotlight()->command()->open();
 * $this->spotlight()->search()->placeholder('Search users...')->open();
 * $this->spotlight()->ai()->open('How do I...');
 * ```
 */
final class SpotlightCall
{
    private SpotlightMode $mode = SpotlightMode::Search;

    private ?string $placeholder = null;

    private ?string $query = null;

    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct(
        private readonly Component $caller,
    ) {}

    /* =========================================================================
     | Mode Configuration
     |========================================================================= */

    /**
     * Set the spotlight mode.
     */
    public function mode(SpotlightMode|string $mode): self
    {
        $this->mode = $mode instanceof SpotlightMode
            ? $mode
            : SpotlightMode::from($mode);

        return $this;
    }

    /**
     * Set search mode.
     */
    public function search(): self
    {
        $this->mode = SpotlightMode::Search;

        return $this;
    }

    /**
     * Set command mode.
     */
    public function command(): self
    {
        $this->mode = SpotlightMode::Command;

        return $this;
    }

    /**
     * Set AI mode.
     */
    public function ai(): self
    {
        $this->mode = SpotlightMode::Ai;

        return $this;
    }

    /* =========================================================================
     | Configuration
     |========================================================================= */

    /**
     * Set placeholder text.
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set initial query.
     */
    public function query(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set custom configuration.
     *
     * @param  array<string, mixed>  $config
     */
    public function config(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /* =========================================================================
     | Actions
     |========================================================================= */

    /**
     * Open the spotlight.
     */
    public function open(?string $initialQuery = null, bool $dispatch = true): string
    {
        $options = $this->buildOptions($initialQuery);

        $js = sprintf(
            'NeuraKitSpotlight.open(%s)',
            json_encode($options, JSON_THROW_ON_ERROR)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Close the spotlight.
     */
    public function close(bool $dispatch = true): string
    {
        $js = 'NeuraKitSpotlight.close()';

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Toggle the spotlight.
     */
    public function toggle(bool $dispatch = true): string
    {
        $options = $this->buildOptions();

        $js = sprintf(
            'NeuraKitSpotlight.toggle(%s)',
            json_encode($options, JSON_THROW_ON_ERROR)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Stream AI response content.
     */
    public function stream(string $content, bool $append = true, bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitSpotlight.stream(%s, %s)',
            json_encode($content, JSON_THROW_ON_ERROR),
            $append ? 'true' : 'false'
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Set results programmatically.
     *
     * @param  array<SpotlightResult|array<string, mixed>>  $results
     */
    public function setResults(array $results, bool $dispatch = true): string
    {
        $normalized = array_map(
            fn ($r) => $r instanceof SpotlightResult ? $r->toArray() : $r,
            $results
        );

        $js = sprintf(
            'NeuraKitSpotlight.setResults(%s)',
            json_encode($normalized, JSON_THROW_ON_ERROR)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Show loading state.
     */
    public function loading(bool $isLoading = true, bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitSpotlight.setLoading(%s)',
            $isLoading ? 'true' : 'false'
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Complete AI response (stop loading).
     */
    public function complete(bool $dispatch = true): string
    {
        return $this->loading(false, $dispatch);
    }

    /**
     * Execute a command programmatically.
     *
     * @param  array<mixed>  $params
     */
    public function execute(string $commandId, array $params = [], bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitSpotlight.execute(%s, %s)',
            json_encode($commandId, JSON_THROW_ON_ERROR),
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Navigate to a URL.
     */
    public function navigate(string $url, bool $dispatch = true): string
    {
        $js = sprintf(
            'Livewire.navigate(%s)',
            json_encode($url, JSON_THROW_ON_ERROR)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /* =========================================================================
     | Helpers
     |========================================================================= */

    /**
     * Build options array for JavaScript.
     *
     * @return array<string, mixed>
     */
    private function buildOptions(?string $initialQuery = null): array
    {
        return array_filter([
            'mode' => $this->mode->value,
            'placeholder' => $this->placeholder,
            'query' => $initialQuery ?? $this->query,
            ...$this->config,
        ], fn ($v) => $v !== null);
    }

    /**
     * Get the current mode.
     */
    public function getMode(): SpotlightMode
    {
        return $this->mode;
    }

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->mode = SpotlightMode::Search;
        $this->placeholder = null;
        $this->query = null;
        $this->config = [];

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Livewire\Component;

final class SpotlightCall
{
    private string $mode = 'search';
    private string $placeholder = '';
    private array $config = [];

    public function __construct(
        private readonly Component $caller,
    ) {}

    /* -------------------------------------------------------------
     | Configuration
     |------------------------------------------------------------- */

    /**
     * Set the spotlight mode.
     */
    public function mode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Set search mode.
     */
    public function search(): self
    {
        $this->mode = 'search';
        return $this;
    }

    /**
     * Set command mode.
     */
    public function command(): self
    {
        $this->mode = 'command';
        return $this;
    }

    /**
     * Set AI mode.
     */
    public function ai(): self
    {
        $this->mode = 'ai';
        return $this;
    }

    /**
     * Set placeholder text.
     */
    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Set custom configuration.
     */
    public function config(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /* -------------------------------------------------------------
     | Actions
     |------------------------------------------------------------- */

    /**
     * Open the spotlight.
     */
    public function open(?string $initialQuery = null, bool $dispatch = true): string
    {
        $options = array_filter([
            'mode' => $this->mode,
            'placeholder' => $this->placeholder ?: null,
            'query' => $initialQuery,
            ...$this->config,
        ], fn ($v) => $v !== null);

        $js = sprintf(
            'NeuraKitSpotlight.open(%s)',
            json_encode($options)
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
        $options = array_filter([
            'mode' => $this->mode,
            'placeholder' => $this->placeholder ?: null,
            ...$this->config,
        ], fn ($v) => $v !== null);

        $js = sprintf(
            'NeuraKitSpotlight.toggle(%s)',
            json_encode($options)
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Stream AI response (for AI mode).
     */
    public function stream(string $content, bool $append = true, bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitSpotlight.stream(%s, %s)',
            json_encode($content),
            $append ? 'true' : 'false'
        );

        if ($dispatch) {
            $this->caller->js($js);
        }

        return $js;
    }

    /**
     * Set results programmatically.
     */
    public function setResults(array $results, bool $dispatch = true): string
    {
        $js = sprintf(
            'NeuraKitSpotlight.setResults(%s)',
            json_encode($results)
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
}

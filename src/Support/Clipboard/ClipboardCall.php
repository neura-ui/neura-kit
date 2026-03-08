<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Clipboard;

use JsonException;
use Livewire\Component;

final class ClipboardCall
{
    public function __construct(
        private readonly Component $caller,
        private readonly ?string $text = null
    ) {}

    /**
     * Copy text to the clipboard via injected JS.
     *
     * Works in all contexts: pages, modals, sideovers.
     * The JS module handles focus-trap-aware fallbacks automatically.
     *
     * @throws JsonException
     */
    public function copy(?string $text = null): void
    {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $encoded = json_encode($textToCopy, JSON_THROW_ON_ERROR);

        $this->caller->js("window.Clipboard?.copy({$encoded})");
    }

    /**
     * Copy text then call a Livewire method on success.
     *
     * @throws JsonException
     */
    public function copyWithCallback(?string $text = null, string $method = '', mixed ...$params): void
    {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $encoded = json_encode($textToCopy, JSON_THROW_ON_ERROR);
        $paramsJson = empty($params) ? '' : ', ' . json_encode($params, JSON_THROW_ON_ERROR);

        $this->caller->js(
            "window.Clipboard?.copy({$encoded}).then(() => { \$wire.call('{$method}'{$paramsJson}) })"
        );
    }

    /**
     * Copy text with explicit success/error Livewire callbacks.
     *
     * @throws JsonException
     */
    public function copyWithErrorHandling(
        ?string $text = null,
        ?string $successMethod = null,
        ?string $errorMethod = null,
        mixed ...$params
    ): void {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $encoded = json_encode($textToCopy, JSON_THROW_ON_ERROR);
        $paramsJson = empty($params) ? '' : ', ' . json_encode($params, JSON_THROW_ON_ERROR);

        $jsSuccess = $successMethod
            ? "\$wire.call('{$successMethod}'{$paramsJson});"
            : '';

        $jsError = $errorMethod
            ? "\$wire.call('{$errorMethod}'{$paramsJson});"
            : 'console.error("Clipboard copy failed:", err);';

        $this->caller->js(
            "window.Clipboard?.copy({$encoded}).then(() => { {$jsSuccess} }).catch((err) => { {$jsError} })"
        );
    }
}

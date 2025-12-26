<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Clipboard;

use JsonException;
use Livewire\Component;

final class ClipboardCall
{
    public function __construct(
        private readonly Component $caller,
        private readonly ?string   $text = null
    )
    {
    }

    /**
     * Copier le texte dans le presse-papier via JS
     * @throws JsonException
     */
    public function copy(?string $text = null): void
    {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $this->caller->js(sprintf(
            "window.Clipboard?.copy(%s)",
            json_encode($textToCopy, JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * Copier avec callback
     * @throws JsonException
     */
    public function copyWithCallback(?string $text = null, string $method = '', mixed ...$params): void
    {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $paramsJson = empty($params) ? '' : ', ' . json_encode($params, JSON_THROW_ON_ERROR);

        $this->caller->js(sprintf(
            "window.Clipboard?.copy(%s).then(() => { \$wire.call('%s'%s) })",
            json_encode($textToCopy, JSON_THROW_ON_ERROR),
            $method,
            $paramsJson
        ));
    }

    /**
     * Copier avec gestion des erreurs
     * @throws JsonException
     */
    public function copyWithErrorHandling(?string $text = null, ?string $successMethod = null, ?string $errorMethod = null, mixed   ...$params): void
    {
        $textToCopy = $text ?? $this->text;

        if ($textToCopy === null || $textToCopy === '') {
            return;
        }

        $paramsJson = empty($params) ? '' : ', ' . json_encode($params, JSON_THROW_ON_ERROR);

        $jsSuccess = $successMethod ? sprintf("\$wire.call('%s'%s);", $successMethod, $paramsJson) : '';

        $jsError = $errorMethod ? sprintf("\$wire.call('%s'%s);", $errorMethod, $paramsJson) : 'console.error("Clipboard copy failed:", err);';

        $this->caller->js(sprintf(
            "window.Clipboard?.copy(%s).then(() => { %s }).catch((err) => { %s })",
            json_encode($textToCopy, JSON_THROW_ON_ERROR),
            $jsSuccess,
            $jsError
        ));
    }
}

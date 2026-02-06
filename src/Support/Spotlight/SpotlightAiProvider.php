<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Neura\Kit\Support\Spotlight\Contracts\SpotlightAiProvider as AiProviderContract;

/**
 * Base class for Spotlight AI providers.
 *
 * SIMPLE EXAMPLE - Echo provider:
 * ```php
 * class EchoAiProvider extends SpotlightAiProvider
 * {
 *     public function handle(string $query, callable $stream): ?string
 *     {
 *         $stream("You asked: {$query}");
 *         return null;
 *     }
 * }
 * ```
 *
 * STREAMING EXAMPLE - Word by word:
 * ```php
 * class StreamingAiProvider extends SpotlightAiProvider
 * {
 *     public function handle(string $query, callable $stream): ?string
 *     {
 *         $response = "This is my response to: {$query}";
 *
 *         foreach (explode(' ', $response) as $word) {
 *             $stream($word . ' ');
 *             usleep(100000); // 100ms delay
 *         }
 *
 *         return null;
 *     }
 * }
 * ```
 *
 * OPENAI EXAMPLE:
 * ```php
 * class OpenAiProvider extends SpotlightAiProvider
 * {
 *     public function handle(string $query, callable $stream): ?string
 *     {
 *         $result = OpenAI::chat()->createStreamed([
 *             'model' => 'gpt-4',
 *             'messages' => [['role' => 'user', 'content' => $query]],
 *         ]);
 *
 *         foreach ($result as $response) {
 *             $stream($response->choices[0]->delta->content ?? '');
 *         }
 *
 *         return null;
 *     }
 * }
 * ```
 */
abstract class SpotlightAiProvider implements AiProviderContract
{
    /**
     * Provider priority (higher = checked first).
     */
    protected int $priority = 0;

    /**
     * Keywords that trigger this provider.
     * If empty, provider handles all queries.
     */
    protected array $keywords = [];

    /**
     * Handle the AI query.
     *
     * Use $stream() to send chunks in real-time to the browser.
     * Livewire's wire:stream handles the DOM updates automatically.
     *
     * @param  string  $query  The user's question
     * @param  callable(string): void  $stream  Callback to stream response chunks via Livewire
     * @return string|null Final response (or null if streaming)
     */
    abstract public function handle(string $query, callable $stream): ?string;

    /**
     * Check if this provider can handle the query.
     */
    public function canHandle(string $query): bool
    {
        if (empty($this->keywords)) {
            return true;
        }

        $queryLower = strtolower($query);

        foreach ($this->keywords as $keyword) {
            if (str_contains($queryLower, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the provider's priority.
     */
    public function priority(): int
    {
        return $this->priority;
    }
}

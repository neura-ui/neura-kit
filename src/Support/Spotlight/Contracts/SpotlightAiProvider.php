<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight\Contracts;

/**
 * Contract for AI providers in Spotlight.
 *
 * Implement this interface to create custom AI providers
 * that can answer questions in Spotlight's AI mode.
 *
 * The $stream callback uses Livewire's native stream() under the hood,
 * sending each chunk directly to the browser via wire:stream.
 */
interface SpotlightAiProvider
{
    /**
     * Handle an AI query and return a response.
     *
     * Use $stream() to send chunks in real-time to the browser.
     * Each call to $stream('text') appends to the AI response area.
     * Livewire's wire:stream handles the DOM updates.
     *
     * @param  string  $query  The user's question
     * @param  callable(string): void  $stream  Callback to stream response chunks via Livewire
     * @return string|null Final response (or null if streaming)
     */
    public function handle(string $query, callable $stream): ?string;

    /**
     * Check if this provider can handle the query.
     *
     * @param  string  $query  The user's question
     */
    public function canHandle(string $query): bool;

    /**
     * Get the provider's priority (higher = checked first).
     */
    public function priority(): int;
}

<?php

declare(strict_types=1);

namespace Neura\Kit\Support\Spotlight;

use Closure;

/**
 * Execution context handed to AI providers.
 *
 * Carries the user query, the conversation history, and the channels a
 * provider can use to talk back to the Spotlight UI:
 * - stream() pushes response chunks to the browser in real time
 * - suggest() attaches actionable Spotlight results rendered under the response
 *
 * @example
 * ```php
 * public function handleWithContext(SpotlightAiContext $context): ?string
 * {
 *     $context->stream("You said: {$context->query}");
 *
 *     if ($context->isFollowUp()) {
 *         $context->stream("\n\n_Continuing our conversation..._");
 *     }
 *
 *     $context->suggest(SpotlightResult::url('docs', 'Open documentation', '/docs'));
 *
 *     return null;
 * }
 * ```
 */
final class SpotlightAiContext
{
    /**
     * @param  array<int, array{role: string, content: string}>  $history  Previous messages, oldest first
     */
    public function __construct(
        public readonly string $query,
        public readonly array $history,
        private readonly Closure $streamCallback,
        private readonly Closure $suggestCallback,
    ) {}

    /**
     * Stream a response chunk to the browser in real time.
     */
    public function stream(string $chunk): void
    {
        ($this->streamCallback)($chunk);
    }

    /**
     * Attach an actionable result under the AI response.
     *
     * Accepts a SpotlightResult or a raw result array; the user can click it
     * and it runs through the normal Spotlight action pipeline (url, command,
     * dispatch, wire, copy, modal, js).
     */
    public function suggest(SpotlightResult|array $action): void
    {
        ($this->suggestCallback)(
            $action instanceof SpotlightResult ? $action->toArray() : $action
        );
    }

    /**
     * Get the stream callback as a plain callable (legacy handle() signature).
     */
    public function streamer(): callable
    {
        return $this->streamCallback;
    }

    /**
     * Whether this query continues an existing conversation.
     */
    public function isFollowUp(): bool
    {
        return $this->history !== [];
    }

    /**
     * The last assistant message, if any.
     */
    public function lastAssistantMessage(): ?string
    {
        foreach (array_reverse($this->history) as $message) {
            if (($message['role'] ?? null) === 'assistant') {
                return $message['content'] ?? null;
            }
        }

        return null;
    }

    /**
     * Full conversation (history + current query) as chat messages,
     * ready to feed to an LLM API.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function toMessages(?string $systemPrompt = null): array
    {
        $messages = [];

        if ($systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($this->history as $message) {
            $messages[] = [
                'role' => (string) ($message['role'] ?? 'user'),
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $this->query];

        return $messages;
    }

    /**
     * Conversation history rendered as plain text (for prompt building).
     */
    public function historyAsText(int $maxMessages = 10): string
    {
        return collect($this->history)
            ->take(-$maxMessages)
            ->map(fn (array $m) => strtoupper((string) ($m['role'] ?? 'user')).': '.($m['content'] ?? ''))
            ->implode("\n");
    }
}

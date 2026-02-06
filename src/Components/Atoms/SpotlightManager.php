<?php

declare(strict_types=1);

namespace Neura\Kit\Components\Atoms;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Neura\Kit\Support\Spotlight\Enums\SpotlightActionType;
use Neura\Kit\Support\Spotlight\Enums\SpotlightGroup;
use Neura\Kit\Support\Spotlight\Enums\SpotlightMode;
use Neura\Kit\Support\Spotlight\SpotlightRegistry;
use Neura\Kit\Support\Spotlight\SpotlightResult;

/**
 * Spotlight/Command Palette Component.
 *
 * Architecture: Single Source of Truth Pattern
 * - Livewire manages all state
 * - Alpine syncs via @entangle
 * - No duplicate events
 */
class SpotlightManager extends Component
{
    /* =========================================================================
     | State
     |========================================================================= */

    public bool $isOpen = false;

    public string $query = '';

    public string $mode = 'search';

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    public bool $isLoading = false;

    public string $aiResponse = '';

    public int $selectedIndex = 0;

    public ?string $placeholder = null;

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $commands = [];

    #[Locked]
    public array $configData = [];

    /* =========================================================================
     | Lifecycle
     |========================================================================= */

    public function mount(): void
    {
        $this->commands = $this->loadCommands();
        $this->configData = $this->buildConfigData();
    }

    public function hydrate(): void
    {
        $this->commands = $this->loadCommands();
    }

    protected function buildConfigData(): array
    {
        $config = SpotlightRegistry::getConfig()->toArray();

        // Override enabledModes with actually available modes
        $availableModes = SpotlightRegistry::getAvailableModes();
        $config['enabledModes'] = array_map(fn (SpotlightMode $m) => $m->value, $availableModes);

        return $config;
    }

    protected function loadCommands(): array
    {
        return SpotlightRegistry::getCommands()
            ->map(fn ($cmd) => $cmd->toArray())
            ->values()
            ->toArray();
    }

    /* =========================================================================
     | Open / Close / Toggle
     |========================================================================= */

    public function open(array $options = []): void
    {
        $availableModes = SpotlightRegistry::getAvailableModes();

        // No modes available = nothing to show
        if (empty($availableModes)) {
            return;
        }

        $requestedMode = $options['mode'] ?? null;
        $modeEnum = $requestedMode !== null ? SpotlightMode::tryFrom($requestedMode) : null;

        // If requested mode is not available, fallback to first available
        if ($modeEnum === null || ! in_array($modeEnum, $availableModes, true)) {
            $modeEnum = $availableModes[0];
        }

        // Guard: Already open with same mode
        if ($this->isOpen && $this->mode === $modeEnum->value) {
            return;
        }

        $this->isOpen = true;
        $this->resetState();
        $this->mode = $modeEnum->value;
        $this->placeholder = $options['placeholder'] ?? null;
        $this->query = $options['query'] ?? '';

        // Initialize results
        $this->results = empty($this->query)
            ? $this->getInitialResults()
            : $this->performSearch();

        $this->dispatch('spotlight-opened');
    }

    public function close(): void
    {
        if (! $this->isOpen) {
            return;
        }

        $this->isOpen = false;
        $this->resetState();
        $this->dispatch('spotlight-closed');
    }

    public function toggle(array $options = []): void
    {
        if (! $this->isOpen) {
            $this->open($options);

            return;
        }

        $requestedMode = $options['mode'] ?? null;

        // Same mode or no mode = close
        if ($requestedMode === null || $requestedMode === $this->mode) {
            $this->close();
        } else {
            $this->setMode($requestedMode);
        }
    }

    protected function resetState(): void
    {
        $this->query = '';
        $this->results = [];
        $this->aiResponse = '';
        $this->selectedIndex = 0;
        $this->isLoading = false;
        $this->placeholder = null;
    }

    /* =========================================================================
     | Mode
     |========================================================================= */

    public function setMode(string $mode): void
    {
        $modeEnum = SpotlightMode::tryFrom($mode);
        if ($modeEnum === null) {
            return;
        }

        // Only allow switching to available modes
        $availableModes = SpotlightRegistry::getAvailableModes();
        if (! empty($availableModes) && ! in_array($modeEnum, $availableModes, true)) {
            return;
        }

        $this->mode = $modeEnum->value;
        $this->query = '';
        $this->aiResponse = '';
        $this->selectedIndex = 0;
        $this->results = $this->getInitialResults();
    }

    public function nextMode(): void
    {
        $availableModes = SpotlightRegistry::getAvailableModes();

        if (count($availableModes) <= 1) {
            return;
        }

        $current = $this->currentMode();
        $currentIndex = array_search($current, $availableModes, true);
        $nextIndex = ($currentIndex === false ? 0 : $currentIndex + 1) % count($availableModes);

        $this->setMode($availableModes[$nextIndex]->value);
    }

    protected function currentMode(): SpotlightMode
    {
        return SpotlightMode::tryFrom($this->mode) ?? SpotlightMode::Search;
    }

    protected function getInitialResults(): array
    {
        return match ($this->currentMode()) {
            SpotlightMode::Command => $this->commandsToResults(),
            default => [],
        };
    }

    protected function commandsToResults(): array
    {
        return collect($this->commands)
            ->map(fn (array $cmd) => [
                'id' => $cmd['id'],
                'title' => $cmd['name'],
                'description' => $cmd['description'] ?? null,
                'icon' => $cmd['icon'] ?? 'command-line',
                'action' => SpotlightActionType::Command->createAction($cmd['id']),
                'actionType' => SpotlightActionType::Command->value,
                'group' => $cmd['group'] ?? SpotlightGroup::Commands->value,
                'shortcut' => $cmd['shortcut'] ?? null,
                'priority' => $cmd['priority'] ?? 0,
            ])
            ->sortByDesc('priority')
            ->values()
            ->toArray();
    }

    /* =========================================================================
     | Search
     |========================================================================= */

    public function updatedQuery(): void
    {
        $this->selectedIndex = 0;

        if ($this->currentMode() === SpotlightMode::Ai) {
            return;
        }

        $this->results = $this->performSearch();
    }

    public function search(string $query = ''): void
    {
        if (! empty($query)) {
            $this->query = $query;
        }
        $this->results = $this->performSearch();
    }

    protected function performSearch(): array
    {
        $query = trim($this->query);

        if (empty($query)) {
            $this->isLoading = false;

            return $this->getInitialResults();
        }

        $this->isLoading = true;

        try {
            $results = SpotlightRegistry::search($query, $this->currentMode())
                ->map(fn ($r) => $r instanceof SpotlightResult ? $r->toArray() : $r)
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            report($e);
            $results = [];
        }

        $this->isLoading = false;

        return $results;
    }

    /* =========================================================================
     | Navigation
     |========================================================================= */

    public function moveUp(): void
    {
        $count = count($this->results);
        if ($count === 0) {
            return;
        }
        $this->selectedIndex = $this->selectedIndex > 0
            ? $this->selectedIndex - 1
            : $count - 1;
    }

    public function moveDown(): void
    {
        $count = count($this->results);
        if ($count === 0) {
            return;
        }
        $this->selectedIndex = $this->selectedIndex < $count - 1
            ? $this->selectedIndex + 1
            : 0;
    }

    public function selectResult(int $index): void
    {
        if ($index >= 0 && $index < count($this->results)) {
            $this->selectedIndex = $index;
        }
    }

    /* =========================================================================
     | Execution
     |========================================================================= */

    public function executeSelected(): void
    {
        if ($this->currentMode() === SpotlightMode::Ai) {
            if (! empty($this->query)) {
                $this->submitAiQuery();
            }

            return;
        }

        $result = $this->results[$this->selectedIndex] ?? null;
        if ($result !== null) {
            $this->handleResult($result);
        }
    }

    public function handleResult(array $result): void
    {
        // URL takes priority
        if (! empty($result['url'])) {
            $this->navigateToUrl($result['url']);

            return;
        }

        // Action string
        if (! empty($result['action'])) {
            $this->executeAction($result['action'], $result['params'] ?? []);

            return;
        }

        // Action type fallback
        if (! empty($result['actionType'])) {
            $actionType = SpotlightActionType::tryFrom($result['actionType']);
            $actionValue = $result['actionValue'] ?? $result['id'] ?? null;

            if ($actionType !== null && $actionValue !== null) {
                $this->executeAction(
                    $actionType->createAction($actionValue),
                    $result['params'] ?? []
                );

                return;
            }
        }

        $this->close();
    }

    protected function executeAction(string $action, array $params = []): void
    {
        $parsed = SpotlightActionType::parse($action);

        match ($parsed['type']) {
            SpotlightActionType::Command => $this->executeCommand($parsed['value'], $params),
            SpotlightActionType::Dispatch => $this->dispatchEvent($parsed['value'], $params),
            SpotlightActionType::Wire => $this->callWireMethod($parsed['value'], $params),
            SpotlightActionType::Copy => $this->copyToClipboard($parsed['value']),
            SpotlightActionType::Modal => $this->openModal($parsed['value'], $params),
            SpotlightActionType::Javascript => $this->executeJavascript($parsed['value']),
            SpotlightActionType::Url => $this->navigateToUrl($parsed['value']),
            default => $this->close(),
        };
    }

    /* =========================================================================
     | Action Handlers
     |========================================================================= */

    protected function executeCommand(string $commandId, array $params = []): void
    {
        $result = SpotlightRegistry::execute($commandId, $params);

        if ($result instanceof SpotlightResult) {
            $this->handleResult($result->toArray());

            return;
        }

        $this->close();
    }

    protected function dispatchEvent(string $event, array $params = []): void
    {
        $this->dispatch($event, ...$params);
        $this->close();
    }

    protected function callWireMethod(string $method, array $params = []): void
    {
        if (method_exists($this, $method)) {
            $this->{$method}(...$params);
        } else {
            $this->dispatch('spotlight:wire-action', method: $method, params: $params);
        }
        $this->close();
    }

    protected function copyToClipboard(string $text): void
    {
        $this->js('navigator.clipboard.writeText('.json_encode($text).')');
        $this->dispatch('spotlight:copied', text: $text);
        $this->close();
    }

    protected function openModal(string $modalName, array $params = []): void
    {
        $this->dispatch('modal:open', name: $modalName, params: $params);
        $this->close();
    }

    protected function executeJavascript(string $code): void
    {
        $this->js($code);
    }

    protected function navigateToUrl(string $url): void
    {
        $this->close();

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $this->js('window.open('.json_encode($url).", '_blank')");

            return;
        }

        $this->redirect($url, navigate: true);
    }

    /* =========================================================================
     | AI Mode
     |========================================================================= */

    public function submitAiQuery(): void
    {
        if (empty($this->query)) {
            return;
        }

        $this->isLoading = true;
        $this->aiResponse = '';

        // Use registered AI providers
        $providers = SpotlightRegistry::getAiProviders();

        if ($providers->isEmpty()) {
            $this->dispatch('spotlight:ai-query', query: $this->query);
            $this->isLoading = false;

            return;
        }

        try {
            SpotlightRegistry::handleAiQuery($this->query, function (string $chunk) {
                // Stream for real-time async providers (OpenAI, etc.)
                // For sync providers, the final x-html render handles display
                try {
                    $this->stream(
                        to: 'spotlightAiStream',
                        content: $chunk,
                        replace: false,
                    );
                } catch (\Throwable) {
                    // Silently ignore stream errors
                }

                $this->aiResponse .= $chunk;
            });
        } catch (\Throwable $e) {
            $this->aiResponse = '**Error**: '.$e->getMessage();
            report($e);
        }

        $this->isLoading = false;
    }

    #[On('spotlight:stream')]
    public function streamAiResponse(string $content, bool $append = true): void
    {
        if ($append) {
            $this->aiResponse .= $content;
        } else {
            $this->aiResponse = $content;
        }

        try {
            $this->stream(to: 'spotlightAiStream', content: $content, replace: ! $append);
        } catch (\Throwable) {
            // Silently ignore stream errors
        }
    }

    #[On('spotlight:ai-complete')]
    public function aiComplete(): void
    {
        $this->isLoading = false;
    }

    /* =========================================================================
     | Public Setters
     |========================================================================= */

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function setLoading(bool $loading): void
    {
        $this->isLoading = $loading;
    }

    /* =========================================================================
     | Computed
     |========================================================================= */

    #[Computed]
    public function groupedResults(): array
    {
        return collect($this->results)
            ->groupBy(fn (array $r) => $r['group'] ?? SpotlightGroup::General->value)
            ->sortBy(fn ($items, $group) => -1 * (SpotlightGroup::tryFrom($group)?->priority() ?? 0))
            ->map(fn ($items, $group) => [
                'group' => SpotlightGroup::tryFrom($group) ?? SpotlightGroup::General,
                'label' => SpotlightGroup::tryFrom($group)?->defaultLabel() ?? 'Other',
                'items' => $items->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function currentPlaceholder(): string
    {
        // JS-provided placeholder takes priority (from open() options)
        if ($this->placeholder !== null) {
            return $this->placeholder;
        }

        // Then check SpotlightConfig (placeholders array + aiPlaceholder)
        return SpotlightRegistry::getConfig()->getPlaceholder($this->currentMode());
    }

    #[Computed]
    public function modeConfig(): array
    {
        $mode = $this->currentMode();

        return [
            'value' => $mode->value,
            'label' => $mode->defaultLabel(),
            'icon' => $mode->icon(),
            'shortcut' => $mode->shortcut(),
            'supportsSearch' => $mode->supportsSearch(),
            'showsResults' => $mode->showsResults(),
        ];
    }

    #[Computed]
    public function availableModes(): array
    {
        $available = SpotlightRegistry::getAvailableModes();

        if (empty($available)) {
            return [];
        }

        return array_map(fn (SpotlightMode $mode) => [
            'value' => $mode->value,
            'label' => $mode->defaultLabel(),
            'icon' => $mode->icon(),
            'shortcut' => $mode->shortcut(),
        ], $available);
    }

    /**
     * Get the custom AI view name, if configured.
     */
    public function aiViewName(): ?string
    {
        $config = SpotlightRegistry::getConfig();

        if ($config->aiView && view()->exists($config->aiView)) {
            return $config->aiView;
        }

        return null;
    }

    /* =========================================================================
     | Render
     |========================================================================= */

    public function render(): View
    {
        if (view()->exists('neura::spotlight-manager.index')) {
            return view('neura::spotlight-manager.index');
        }

        $viewPath = realpath(__DIR__.'/../../resources/views/neura/spotlight-manager/index.blade.php');
        if ($viewPath && file_exists($viewPath)) {
            return view()->file($viewPath);
        }

        return view('neura-kit::neura.spotlight-manager.index');
    }
}

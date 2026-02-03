<?php

namespace Neura\Kit\Components\Atoms;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Neura\Kit\Support\Spotlight\SpotlightRegistry;
use Neura\Kit\Support\Spotlight\SpotlightResult;

class SpotlightManager extends Component
{
    public bool $isOpen = false;
    
    public string $query = '';
    
    public string $mode = 'search'; // search, command, ai
    
    public array $results = [];
    
    public array $commands = [];
    
    public bool $isLoading = false;
    
    public string $aiResponse = '';
    
    public int $selectedIndex = 0;
    
    public ?string $placeholder = null;
    
    protected $listeners = [
        'spotlight:open' => 'open',
        'spotlight:close' => 'close',
        'spotlight:toggle' => 'toggle',
        'spotlight:search' => 'search',
        'spotlight:execute' => 'executeCommand',
        'spotlight:stream' => 'streamAiResponse',
    ];

    public function mount(): void
    {
        $this->loadCommands();
    }

    public function loadCommands(): void
    {
        $this->commands = SpotlightRegistry::getCommands()
            ->map(fn ($cmd) => $cmd->toArray())
            ->values()
            ->toArray();
    }

    #[On('spotlight:open')]
    public function open(array $options = []): void
    {
        $this->isOpen = true;
        $this->mode = $options['mode'] ?? 'search';
        $this->placeholder = $options['placeholder'] ?? null;
        $this->query = $options['query'] ?? '';
        $this->results = [];
        $this->aiResponse = '';
        $this->selectedIndex = 0;
        $this->isLoading = false;

        if ($this->query) {
            $this->search($this->query);
        }

        $this->dispatch('spotlight-opened');
    }

    #[On('spotlight:close')]
    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
        $this->aiResponse = '';
        $this->selectedIndex = 0;
        $this->isLoading = false;

        $this->dispatch('spotlight-closed');
    }

    #[On('spotlight:toggle')]
    public function toggle(array $options = []): void
    {
        if ($this->isOpen) {
            $this->close();
        } else {
            $this->open($options);
        }
    }

    public function updatedQuery(): void
    {
        $this->selectedIndex = 0;
        
        if ($this->mode === 'ai') {
            // AI mode doesn't auto-search
            return;
        }

        $this->search($this->query);
    }

    #[On('spotlight:search')]
    public function search(string $query = ''): void
    {
        $query = $query ?: $this->query;
        
        if (empty($query)) {
            $this->results = [];
            return;
        }

        $this->isLoading = true;

        try {
            // Search using registry
            $searchResults = SpotlightRegistry::search($query);

            // Also filter commands by name/description
            $matchedCommands = collect($this->commands)
                ->filter(function ($cmd) use ($query) {
                    $q = strtolower($query);
                    return str_contains(strtolower($cmd['name']), $q)
                        || str_contains(strtolower($cmd['description'] ?? ''), $q);
                })
                ->map(fn ($cmd) => [
                    'id' => $cmd['id'],
                    'title' => $cmd['name'],
                    'description' => $cmd['description'] ?? null,
                    'icon' => $cmd['icon'] ?? null,
                    'action' => 'command:' . $cmd['id'],
                    'group' => $cmd['group'] ?? 'commands',
                ]);

            $this->results = $searchResults
                ->merge($matchedCommands)
                ->unique('id')
                ->take(20)
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            $this->results = [];
        }

        $this->isLoading = false;
    }

    #[On('spotlight:execute')]
    public function executeCommand(string $commandId, array $params = []): void
    {
        $result = SpotlightRegistry::execute($commandId, $params);
        
        if ($result instanceof SpotlightResult) {
            $this->handleResult($result->toArray());
        }
        
        $this->close();
    }

    public function selectResult(int $index): void
    {
        $this->selectedIndex = $index;
    }

    public function executeSelected(): void
    {
        if (empty($this->results)) {
            // In AI mode, submit the query
            if ($this->mode === 'ai' && $this->query) {
                $this->submitAiQuery();
            }
            return;
        }

        $result = $this->results[$this->selectedIndex] ?? null;
        
        if ($result) {
            $this->handleResult($result);
        }
    }

    public function handleResult(array $result): void
    {
        if (isset($result['url'])) {
            // Navigate to URL
            $this->dispatch('spotlight:navigate', url: $result['url']);
            $this->close();
            return;
        }

        if (isset($result['action'])) {
            $action = $result['action'];
            $params = $result['params'] ?? [];

            if (str_starts_with($action, 'command:')) {
                $commandId = substr($action, 8);
                $this->executeCommand($commandId, $params);
                return;
            }

            if (str_starts_with($action, 'dispatch:')) {
                $event = substr($action, 9);
                $this->dispatch($event, ...$params);
                $this->close();
                return;
            }

            if (str_starts_with($action, 'wire:')) {
                $method = substr($action, 5);
                $this->dispatch('spotlight:wire-action', method: $method, params: $params);
                $this->close();
                return;
            }
        }

        $this->close();
    }

    public function moveUp(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        } else {
            $this->selectedIndex = max(0, count($this->results) - 1);
        }
    }

    public function moveDown(): void
    {
        if ($this->selectedIndex < count($this->results) - 1) {
            $this->selectedIndex++;
        } else {
            $this->selectedIndex = 0;
        }
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
        $this->results = [];
        $this->aiResponse = '';
        $this->query = '';
    }

    public function submitAiQuery(): void
    {
        if (empty($this->query)) {
            return;
        }

        $this->isLoading = true;
        $this->aiResponse = '';
        
        // Dispatch event for parent component to handle AI processing
        $this->dispatch('spotlight:ai-query', query: $this->query);
    }

    #[On('spotlight:stream')]
    public function streamAiResponse(string $content, bool $append = true): void
    {
        if ($append) {
            $this->aiResponse .= $content;
        } else {
            $this->aiResponse = $content;
        }
    }

    #[On('spotlight:ai-complete')]
    public function aiComplete(): void
    {
        $this->isLoading = false;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function setLoading(bool $loading): void
    {
        $this->isLoading = $loading;
    }

    public function getGroupedResultsProperty(): array
    {
        return collect($this->results)
            ->groupBy('group')
            ->toArray();
    }

    public function render(): View
    {
        if (view()->exists('neura::spotlight-manager.index')) {
            return view('neura::spotlight-manager.index');
        }

        $viewPath = realpath(__DIR__ . '/../../resources/views/neura/spotlight-manager/index.blade.php');
        if ($viewPath && file_exists($viewPath)) {
            return view()->file($viewPath);
        }

        return view('neura-kit::neura.spotlight-manager.index');
    }
}

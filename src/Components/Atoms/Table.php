<?php

namespace Neura\Kit\Components\Atoms;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Neura\Kit\Support\Table\EmptyState;

abstract class Table extends Component
{
    use WithPagination;

    public int $perPage = 10;
    public string $sortBy = '';

    /* -----------------------------------------------------------------
     | Table state
     |----------------------------------------------------------------- */
    public string $sortDirection = 'asc';
    public string $search = '';
    public array $filters = [];
    public array $visibleColumns = [];
    public array $columnWidths = [];
    /** @var array<int,string> */
    public array $selected = [];
    public bool $selectPage;

    /* -----------------------------------------------------------------
     | Bulk selection
     |----------------------------------------------------------------- */
    /** @var array<int,object> */
    protected array $cachedColumns = [];
    protected ?LengthAwarePaginator $cachedData = null;

    public bool $selectAll = false;

    public function selectAllRows(): void
    {
        $this->selectAll = true;
        $this->selectPage = true;

        // Get ALL row IDs (not just current page)
        $this->selected = $this->getAllRowIds();
    }

    public function deselectAllRows(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->selected = [];
    }

    protected function getAllRowIds(): array
    {
        return $this->applySorting(
            $this->applyFilters(
                $this->applySearch(
                    $this->query()
                )
            )
        )
            ->pluck($this->getRowKey())
            ->map(fn($id) => (string)$id)
            ->values()
            ->all();
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function getTotalRowsProperty(): int
    {
        return $this->applySorting(
            $this->applyFilters(
                $this->applySearch(
                    $this->query()
                )
            )
        )->count();
    }

    public function updatedSelectPage($value): void
    {
        if ($value) {
            // Select all on current page
            $this->selected = $this->currentPageRowIds();
        } else {
            // Deselect all
            $this->selectAll = false;
            $pageIds = $this->currentPageRowIds();
            $this->selected = array_values(
                array_filter(
                    $this->selected,
                    fn($id) => !in_array($id, $pageIds, true)
                )
            );
        }

        // Force array re-index to avoid Livewire __rm__ markers
        $this->selected = array_values($this->selected);
    }

    public function updatedSelected($value): void
    {
        // Ensure it's an array and re-indexed
        $this->selected = array_values(
            collect(is_array($value) ? $value : [])
                ->filter(fn($v) => $v !== '__rm__') // Filter out Livewire removal markers
                ->map(fn($id) => (string)$id)
                ->unique()
                ->all()
        );

        $pageIds = $this->currentPageRowIds();

        // selectPage should be checked only when ALL current page rows are selected
        $this->selectPage = !empty($pageIds)
            && count(array_diff($pageIds, $this->selected)) === 0;

        // Check if all rows are selected (not just current page)
        if ($this->selectAll && count($this->selected) < $this->totalRows) {
            $this->selectAll = false;
        }
    }
    /* -----------------------------------------------------------------
     | Required overrides
     |----------------------------------------------------------------- */

    public function actions(): array
    {
        return [];
    }

    public function hasBulkActions(): bool
    {
        return !empty($this->bulkActions());
    }

    /* -----------------------------------------------------------------
     | Optional overrides
     |----------------------------------------------------------------- */

    public function bulkActions(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->initializeColumns();
    }

    protected function initializeColumns(): void
    {
        foreach ($this->getColumns() as $column) {
            if (!isset($column->key)) {
                continue;
            }

            $this->visibleColumns[$column->key] ??= true;

            if (!empty($column->width)) {
                $this->columnWidths[$column->key] ??= (int)$column->width;
            }
        }
    }

    protected function getColumns(): array
    {
        return $this->cachedColumns
            ?: $this->cachedColumns = $this->columns();
    }

    /**
     * Expected: key, label, sortable, searchable, filterable, width, etc.
     *
     * @return array<int,object>
     */
    abstract protected function columns(): array;

    /* -----------------------------------------------------------------
     | Lifecycle
     |----------------------------------------------------------------- */

    #[On('table-refresh')]
    public function handleRefresh(): void
    {
        $this->refreshTable();
    }

    public function refreshTable(bool $resetPage = true): void
    {
        if ($resetPage) {
            $this->resetPage();
        }

        $this->cachedData = null;
        $this->selected = [];
        $this->selectPage = false;
    }

    public function visibleColumns(): array
    {
        if ($this->hasActiveFilters() || filled($this->search)) {
            return $this->getColumns();
        }

        return array_values(array_filter(
            $this->getColumns(),
            fn($col) => $this->visibleColumns[$col->key] ?? true
        ));
    }

    public function hasActiveFilters(): bool
    {
        return count(array_filter($this->filters)) > 0;
    }

    /* -----------------------------------------------------------------
     | Column helpers
     |----------------------------------------------------------------- */

    public function sort(string $key): void
    {
        if (!in_array($key, $this->sortableKeys(), true)) {
            return;
        }

        $this->resetPage();
        $this->cachedData = null;

        $this->sortDirection = $this->sortBy === $key
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortBy = $key;
    }

    protected function sortableKeys(): array
    {
        return array_map(
            fn($c) => $c->key,
            array_filter($this->getColumns(), fn($c) => $c->sortable ?? false)
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->cachedData = null;
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
        $this->cachedData = null;
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';
        $this->refreshTable();
    }

    /* -----------------------------------------------------------------
     | Query pipeline
     |----------------------------------------------------------------- */

    public function hasSearchableColumns(): bool
    {
        return !empty($this->searchableKeys());
    }

    protected function searchableKeys(): array
    {
        return array_map(
            fn($c) => $c->key,
            array_filter($this->getColumns(), fn($c) => $c->searchable ?? false)
        );
    }

    public function hasFilterableColumns(): bool
    {
        return !empty($this->getFilterableColumns());
    }

    /* -----------------------------------------------------------------
     | Data
     |----------------------------------------------------------------- */

    public function getFilterableColumns(): array
    {
        return array_values(array_filter(
            $this->getColumns(),
            fn($col) => $col->filterable ?? false
        ));
    }

    /* -----------------------------------------------------------------
     | Sorting / filters
     |----------------------------------------------------------------- */

    /**
     * IDs on the current page (as strings).
     *
     * @return array<int,string>
     */
    protected function currentPageRowIds(): array
    {
        return $this->data()
            ->getCollection()
            ->pluck($this->getRowKey())
            ->map(fn($id) => (string)$id)
            ->values()
            ->all();
    }

    public function data(): LengthAwarePaginator
    {
        return $this->cachedData ??= $this->applySorting(
            $this->applyFilters(
                $this->applySearch(
                    $this->query()
                )
            )
        )->paginate($this->perPage);
    }

    protected function applySorting($query)
    {
        if (!in_array($this->sortBy, $this->sortableKeys(), true)) {
            return $query;
        }

        return $query->orderBy(
            $this->sortBy,
            $this->sortDirection === 'desc' ? 'desc' : 'asc'
        );
    }

    protected function applyFilters($query)
    {
        if (empty($this->filters)) {
            return $query;
        }

        $columns = collect($this->getColumns())->keyBy('key');

        foreach ($this->filters as $key => $value) {
            if (blank($value)) {
                continue;
            }

            $column = $columns->get($key);
            if (!$column || !($column->filterable ?? false)) {
                continue;
            }

            if (!empty($column->filterQuery) && is_callable($column->filterQuery)) {
                ($column->filterQuery)($query, $value, $key);
                continue;
            }

            $wrapped = $query->getGrammar()->wrap($key);

            is_string($value)
                ? $query->whereRaw("LOWER($wrapped) LIKE ?", ['%' . Str::lower($value) . '%'])
                : $query->where($key, $value);
        }

        return $query;
    }

    protected function applySearch($query)
    {
        if (!filled($this->search)) {
            return $query;
        }

        $columns = $this->searchableKeys();
        if (empty($columns)) {
            return $query;
        }

        $term = Str::lower($this->search);

        return $query->where(function ($q) use ($columns, $term) {
            foreach ($columns as $col) {
                $wrapped = $q->getGrammar()->wrap($col);
                $q->orWhereRaw("LOWER($wrapped) LIKE ?", ["%{$term}%"]);
            }
        });
    }

    abstract protected function query(): Builder|QueryBuilder;

    protected function getRowKey(): string
    {
        return 'id';
    }

    public function runBulkAction(string $key): void
    {
        if (empty($this->selected)) {
            return;
        }

        $action = collect($this->bulkActions())
            ->firstWhere('key', $key);

        if (!$action || !method_exists($this, $action['action'])) {
            return;
        }

        $this->{$action['action']}($this->selected);

        $this->refreshTable();
    }

    public function emptyStateHtml(): string
    {
        $state = $this->emptyState();

        return match (true) {
            $state instanceof EmptyState => $state->render(),
            $state instanceof View => $state->render(),
            $state instanceof Htmlable => $state->toHtml(),
            is_string($state) => $state,
            default => neura_trans('noResultsFound'),
        };
    }

    /* -----------------------------------------------------------------
     | Empty state
     |----------------------------------------------------------------- */

    public function emptyState(): string|View|Htmlable|EmptyState|null
    {
        return null;
    }

    public function render(): View
    {
        return view('neura::table.index');
    }
}

<?php

namespace Neura\Kit\Components\Atoms;
 
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Livewire\Component;
use Livewire\WithPagination;

abstract class Table extends Component
{
    use WithPagination;

    public $perPage = 10;

    public $sortBy = '';

    public $sortDirection = 'asc';

    public $search = '';

    public $filters = [];

    public $visibleColumns = [];

    public $columnWidths = [];

    public abstract function query(): Builder|QueryBuilder;

    public abstract function columns(): array;

    public function actions(): array
    {
        return [];
    }

    public function mount()
    {
        $this->initializeVisibleColumns();
        $this->initializeColumnWidths();
    }

    protected function initializeColumnWidths()
    {
        foreach ($this->columns() as $column) {
            if (!isset($this->columnWidths[$column->key]) && $column->width) {
                $this->columnWidths[$column->key] = $column->width;
            }
        }
    }

    protected function initializeVisibleColumns()
    {
        if (empty($this->visibleColumns)) {
            foreach ($this->columns() as $column) {
                $this->visibleColumns[$column->key] = true;
            }
        }
    }

    public function getVisibleColumns()
    {
        return array_filter($this->columns(), function ($column) {
            return $this->visibleColumns[$column->key] ?? true;
        });
    }

    public function toggleColumn($key)
    {
        $this->visibleColumns[$key] = !($this->visibleColumns[$key] ?? true);
    }

    public function updateColumnWidth($key, $width)
    {
        $this->columnWidths[$key] = $width;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilters()
    {
        $this->resetPage();
    }

    protected function applySearch(Builder|QueryBuilder $query)
    {
        if (empty($this->search)) {
            return $query;
        }

        $searchableColumns = $this->getSearchableColumns();
        
        if (empty($searchableColumns)) {
            return $query;
        }

        $searchTerm = strtolower($this->search);

        return $query->where(function ($q) use ($searchableColumns, $searchTerm) {
            foreach ($searchableColumns as $index => $column) {
                if ($index === 0) {
                    $q->whereRaw('LOWER(' . $q->getGrammar()->wrap($column) . ') LIKE ?', ['%' . $searchTerm . '%']);
                } else {
                    $q->orWhereRaw('LOWER(' . $q->getGrammar()->wrap($column) . ') LIKE ?', ['%' . $searchTerm . '%']);
                }
            }
        });
    }

    protected function getSearchableColumns()
    {
        $columns = [];
        foreach ($this->columns() as $column) {
            if ($column->searchable) {
                $columns[] = $column->key;
            }
        }
        return $columns;
    }

    public function getFilterableColumns()
    {
        return array_filter($this->columns(), fn($col) => $col->filterable ?? false);
    }

    public function hasActiveFilters()
    {
        return count(array_filter($this->filters ?? [])) > 0;
    }

    protected function applyFilters(Builder|QueryBuilder $query)
    {
        foreach ($this->filters as $key => $value) {
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                continue;
            }

            $column = collect($this->columns())->firstWhere('key', $key);
            
            if (!$column || !$column->filterable) {
                continue;
            }

            if ($column->filterQuery) {
                ($column->filterQuery)($query, $value, $key);
            } else {
                $filterTerm = strtolower($value);
                $query->whereRaw('LOWER(' . $query->getGrammar()->wrap($key) . ') LIKE ?', ['%' . $filterTerm . '%']);
            }
        }

        return $query;
    }

    public function data()
    {
        $query = $this->query();
        
        $query = $this->applySearch($query);
        $query = $this->applyFilters($query);
        
        return $query
            ->when($this->sortBy !== '', function ($q) {
                $q->orderBy($this->sortBy, $this->sortDirection);
            })
            ->paginate($this->perPage);
    }

    public function sort($key)
    {
        $this->resetPage();

        if ($this->sortBy === $key) {
            $direction = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->sortDirection = $direction;

            return;
        }

        $this->sortBy = $key;
        $this->sortDirection = 'asc';
    }

    public function clearFilters()
    {
        $this->filters = [];
        $this->search = '';
        $this->resetPage();
    }

    public function render(): View
    {
        return view('neura::table.index');
    }
}


<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait WithTableControls
{
    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $perPage = '10';

    public array $pageSizeOptions = ['10', '25', '50', '100', 'all'];

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSortFields(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function allowedSortFields(): array
    {
        return [];
    }

    protected function applySorting(Builder $query, string $fallbackField = 'created_at'): Builder
    {
        $field = in_array($this->sortField, $this->allowedSortFields(), true)
            ? $this->sortField
            : $fallbackField;
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($field, $direction);
    }

    protected function paginateQuery(Builder|Relation $query, string $pageName = 'page'): LengthAwarePaginator
    {
        $perPage = $this->perPage === 'all' ? max($query->count(), 1) : (int) $this->perPage;

        return $query->paginate($perPage, ['*'], $pageName);
    }
}

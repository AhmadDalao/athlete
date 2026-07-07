<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

trait WithTableControls
{
    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $perPage = '10';

    public array $pageSizeOptions = ['10', '25', '50', '100', 'all'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    protected function paginateQuery(Builder $query): LengthAwarePaginator
    {
        $perPage = $this->perPage === 'all' ? max($query->count(), 1) : (int) $this->perPage;

        return $query->paginate($perPage);
    }
}

<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BaseRepositoryInterface
{
    public function all(): Collection;
    
    public function find(string $id): ?Model;
    
    public function findOrFail(string $id): Model;
    
    public function create(array $data): Model;
    
    public function update(string $id, array $data): Model;
    
    public function delete(string $id): bool;
    
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}

<?php 

namespace App\Repositories\ProductManagement\BaseRepo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function getAll(int $perPage = 10, array $relations = []): LengthAwarePaginator;
    public function findById(int $id, array $relations = []): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): Model;
    public function delete(int $id): void;
}
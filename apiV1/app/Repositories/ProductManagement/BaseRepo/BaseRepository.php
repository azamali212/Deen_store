<?php

namespace App\Repositories\ProductManagement\BaseRepo;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface{

    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    //Get all records
    public function getAll(int $perPage = 10, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage);
    }
    //Find record by id
    public function findById(int $id, array $relations = []): ?Model
    {
        return $this->model->with($relations)->findOrFail($id);
    }
    //Create record
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }
    //Update record
    public function update(int $id, array $data): Model
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
        return $record;
    }
    //Delete record
    public function delete(int $id): void
    {
        $this->model->findOrFail($id)->delete();
    }
}
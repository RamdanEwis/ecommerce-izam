<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The model instance
     *
     * @var Model
     */
    protected Model $model;

    /**
     * The query builder instance
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * BaseRepository constructor
     */
    public function __construct()
    {
        $this->model = $this->getModel();
        $this->query = $this->model->newQuery();
    }

    /**
     * Get the model instance
     *
     * @return Model
     */
    abstract protected function getModel(): Model;

    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
    }

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query->paginate($perPage, $columns);
    }

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->query->find($id, $columns);
    }

    /**
     * Find a record by ID or fail
     *
     * @param int $id
     * @param array $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->query->findOrFail($id, $columns);
    }

    /**
     * Find a record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->query->where($field, $value)->first($columns);
    }

    /**
     * Find records by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Collection
     */
    public function findWhereField(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->query->where($field, $value)->get($columns);
    }

    /**
     * Find records by multiple conditions
     *
     * @param array $conditions
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $conditions, array $columns = ['*']): Collection
    {
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $this->query->whereIn($field, $value);
            } else {
                $this->query->where($field, $value);
            }
        }

        return $this->query->get($columns);
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record;
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    /**
     * Delete multiple records by IDs
     *
     * @param array $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * Get count of records
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->count();
    }

    /**
     * Check if record exists
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->exists();
    }

    /**
     * Get records with relationships
     *
     * @param array $relations
     * @param array $columns
     * @return Collection
     */
    public function with(array $relations, array $columns = ['*']): Collection
    {
        return $this->query->with($relations)->get($columns);
    }

    /**
     * Order records by field
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->query->orderBy($field, $direction);
        return $this;
    }

    /**
     * Limit the number of records
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Add where condition
     *
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $field, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $this->query->where($field, $operator);
        } else {
            $this->query->where($field, $operator, $value);
        }
        return $this;
    }

    /**
     * Get the query builder
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Reset the query builder
     *
     * @return $this
     */
    public function resetQuery(): static
    {
        $this->query = $this->model->newQuery();
        return $this;
    }

    /**
     * Create a new query instance
     *
     * @return Builder
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Get a fresh query builder instance
     *
     * @return Builder
     */
    protected function getFreshQuery(): Builder
    {
        return $this->model->newQuery();
    }
}

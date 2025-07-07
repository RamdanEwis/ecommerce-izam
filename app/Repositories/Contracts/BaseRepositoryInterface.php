<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Get paginated records
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a record by ID
     *
     * @param int $id
     * @param array $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail
     *
     * @param int $id
     * @param array $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Find a record by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Model|null
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model;

    /**
     * Find records by field
     *
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @return Collection
     */
    public function findWhereField(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Find records by multiple conditions
     *
     * @param array $conditions
     * @param array $columns
     * @return Collection
     */
    public function findWhere(array $conditions, array $columns = ['*']): Collection;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Delete multiple records by IDs
     *
     * @param array $ids
     * @return int
     */
    public function deleteMany(array $ids): int;

    /**
     * Get count of records
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * Check if record exists
     *
     * @param int $id
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Get records with relationships
     *
     * @param array $relations
     * @param array $columns
     * @return Collection
     */
    public function with(array $relations, array $columns = ['*']): Collection;

    /**
     * Order records by field
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $field, string $direction = 'asc'): static;

    /**
     * Limit the number of records
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static;

    /**
     * Add where condition
     *
     * @param string $field
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $field, mixed $operator, mixed $value = null): static;

    /**
     * Get the query builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery(): \Illuminate\Database\Eloquent\Builder;

    /**
     * Reset the query builder
     *
     * @return $this
     */
    public function resetQuery(): static;
}

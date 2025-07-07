<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find user by email
     *
     * @param string $email
     * @param array $columns
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User;

    /**
     * Find user by email or fail
     *
     * @param string $email
     * @param array $columns
     * @return User
     */
    public function findByEmailOrFail(string $email, array $columns = ['*']): User;

    /**
     * Create user with hashed password
     *
     * @param array $data
     * @return User
     */
    public function createWithHashedPassword(array $data): User;

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $password
     * @return User
     */
    public function updatePassword(int $userId, string $password): User;

    /**
     * Get users with orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrders(array $columns = ['*']): Collection;

    /**
     * Get users by registration date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $columns
     * @return Collection
     */
    public function getByRegistrationDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection;

    /**
     * Get recently registered users
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecentlyRegistered(int $days = 7, array $columns = ['*']): Collection;

    /**
     * Get users by name pattern
     *
     * @param string $name
     * @param array $columns
     * @return Collection
     */
    public function getByNamePattern(string $name, array $columns = ['*']): Collection;

    /**
     * Get active users (users with orders)
     *
     * @param array $columns
     * @return Collection
     */
    public function getActiveUsers(array $columns = ['*']): Collection;

    /**
     * Get users with order count
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrderCount(array $columns = ['*']): Collection;

    /**
     * Get users with total spent
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithTotalSpent(array $columns = ['*']): Collection;

    /**
     * Get top customers by order value
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10, array $columns = ['*']): Collection;

    /**
     * Get users statistics
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Get monthly registration statistics
     *
     * @param int $year
     * @return array
     */
    public function getMonthlyRegistrationStatistics(int $year): array;

    /**
     * Search users by name or email
     *
     * @param string $query
     * @param array $columns
     * @return Collection
     */
    public function searchByNameOrEmail(string $query, array $columns = ['*']): Collection;

    /**
     * Get users with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Check if email exists
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;

    /**
     * Get user's order summary
     *
     * @param int $userId
     * @return array
     */
    public function getOrderSummary(int $userId): array;

    /**
     * Get users without orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getUsersWithoutOrders(array $columns = ['*']): Collection;

    /**
     * Update user's last login
     *
     * @param int $userId
     * @return User
     */
    public function updateLastLogin(int $userId): User;
}

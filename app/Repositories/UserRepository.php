<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Get the model instance
     *
     * @return Model
     */
    protected function getModel(): Model
    {
        return new User();
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @param array $columns
     * @return User|null
     */
    public function findByEmail(string $email, array $columns = ['*']): ?User
    {
        return $this->findByField('email', $email, $columns);
    }

    /**
     * Find user by email or fail
     *
     * @param string $email
     * @param array $columns
     * @return User
     */
    public function findByEmailOrFail(string $email, array $columns = ['*']): User
    {
        $user = $this->findByEmail($email, $columns);

        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * Create user with hashed password
     *
     * @param array $data
     * @return User
     */
    public function createWithHashedPassword(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->create($data);
    }

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $password
     * @return User
     */
    public function updatePassword(int $userId, string $password): User
    {
        return $this->update($userId, ['password' => Hash::make($password)]);
    }

    /**
     * Get users with orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrders(array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()->with('orders')->get($columns);
    }

    /**
     * Get users by registration date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $columns
     * @return Collection
     */
    public function getByRegistrationDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get($columns);
    }

    /**
     * Get recently registered users
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecentlyRegistered(int $days = 7, array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Get users by name pattern
     *
     * @param string $name
     * @param array $columns
     * @return Collection
     */
    public function getByNamePattern(string $name, array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->where('name', 'like', '%' . $name . '%')
            ->get($columns);
    }

    /**
     * Get active users (users with orders)
     *
     * @param array $columns
     * @return Collection
     */
    public function getActiveUsers(array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->whereHas('orders')
            ->get($columns);
    }

    /**
     * Get users with order count
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrderCount(array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->withCount('orders')
            ->get($columns);
    }

    /**
     * Get users with total spent
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithTotalSpent(array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->withSum('orders as total_spent', 'total_amount')
            ->get($columns);
    }

    /**
     * Get top customers by order value
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10, array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->withSum('orders as total_spent', 'total_amount')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Get users statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $query = $this->getFreshQuery();

        return [
            'total_users' => $query->count(),
            'active_users' => $query->whereHas('orders')->count(),
            'recent_registrations' => $query->where('created_at', '>=', now()->subDays(30))->count(),
            'users_with_orders' => $query->whereHas('orders')->count(),
            'users_without_orders' => $query->whereDoesntHave('orders')->count(),
        ];
    }

    /**
     * Get monthly registration statistics
     *
     * @param int $year
     * @return array
     */
    public function getMonthlyRegistrationStatistics(int $year): array
    {
        return $this->getFreshQuery()
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total_registrations')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Search users by name or email
     *
     * @param string $query
     * @param array $columns
     * @return Collection
     */
    public function searchByNameOrEmail(string $query, array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->get($columns);
    }

    /**
     * Get users with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->getFreshQuery();

        if (isset($filters['name']) && $filters['name']) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['email']) && $filters['email']) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (isset($filters['has_orders']) && $filters['has_orders']) {
            if ($filters['has_orders']) {
                $query->whereHas('orders');
            } else {
                $query->whereDoesntHave('orders');
            }
        }

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Check if email exists
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->getFreshQuery()->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get user's order summary
     *
     * @param int $userId
     * @return array
     */
    public function getOrderSummary(int $userId): array
    {
        $user = $this->getFreshQuery()
            ->where('id', $userId)
            ->withCount([
                'orders',
                'orders as completed_orders_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'orders as pending_orders_count' => function ($query) {
                    $query->where('status', 'pending');
                },
                'orders as cancelled_orders_count' => function ($query) {
                    $query->where('status', 'cancelled');
                }
            ])
            ->withSum([
                'orders as total_spent' => function ($query) {
                    $query->where('status', 'completed');
                }
            ], 'total_amount')
            ->first();

        if (!$user) {
            return [];
        }

        return [
            'total_orders' => $user->orders_count ?? 0,
            'completed_orders' => $user->completed_orders_count ?? 0,
            'pending_orders' => $user->pending_orders_count ?? 0,
            'cancelled_orders' => $user->cancelled_orders_count ?? 0,
            'total_spent' => $user->total_spent ?? 0,
        ];
    }

    /**
     * Get users without orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getUsersWithoutOrders(array $columns = ['*']): Collection
    {
        return $this->getFreshQuery()
            ->whereDoesntHave('orders')
            ->get($columns);
    }

    /**
     * Update user's last login
     *
     * @param int $userId
     * @return User
     */
    public function updateLastLogin(int $userId): User
    {
        return $this->update($userId, ['last_login_at' => now()]);
    }
}

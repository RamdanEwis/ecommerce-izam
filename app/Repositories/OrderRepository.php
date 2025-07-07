<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
	 /**
     * Get the model instance
     *
     * @return Model
     */
    protected function getModel(): Model
    {
        return new Order();
    }

    /**
     * Get orders by user ID
     *
     * @param int $userId
     * @param array $columns
     * @return Collection
     */
    public function getByUser(int $userId, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()->where('user_id', $userId)->get($columns);
    }

    /**
     * Get paginated orders by user ID
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getPaginatedByUser(int $userId, int $perPage = 15, array $columns = ['id', 'user_id', 'total_amount', 'status']): LengthAwarePaginator
    {
        return $this->getFreshQuery()->where('user_id', $userId)->paginate($perPage, $columns);
    }

    /**
     * Get orders by status
     *
     * @param string $status
     * @param array $columns
     * @return Collection
     */
    public function getByStatus(string $status, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()->where('status', $status)->get($columns);
    }

    /**
     * Get orders by user and status
     *
     * @param int $userId
     * @param string $status
     * @param array $columns
     * @return Collection
     */
    public function getByUserAndStatus(int $userId, string $status, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->get($columns);
    }

    /**
     * Get orders with products
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithProducts(array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()->with('products')->get($columns);
    }

    /**
     * Get orders with order products
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrderProducts(array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()->with('orderProducts')->get($columns);
    }

    /**
     * Get orders within date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $columns
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get($columns);
    }

     /**
     * Get orders by total amount range
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @param array $columns
     * @return Collection
     */
    public function getByTotalAmountRange(float $minAmount, float $maxAmount, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()
            ->whereBetween('total_amount', [$minAmount, $maxAmount])
            ->get($columns);
    }

    /**
     * Get recent orders
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecent(int $days = 7, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Get pending orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getPending(array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getByStatus('pending', $columns);
    }

    /**
     * Get completed orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getCompleted(array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getByStatus('completed', $columns);
    }

    /**
     * Get cancelled orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getCancelled(array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getByStatus('cancelled', $columns);
    }

     /**
     * Update order status
     *
     * @param int $orderId
     * @param string $status
     * @return Order
     */
    public function updateStatus(int $orderId, string $status): Order
    {
        $order = $this->findOrFail($orderId);
        $order->update(['status' => $status]);
        return $order;
    }

    /**
     * Update order total amount
     *
     * @param int $orderId
     * @param float $totalAmount
     * @return Order
     */
    public function updateTotalAmount(int $orderId, float $totalAmount): Order
    {
        $order = $this->findOrFail($orderId);
        $order->update(['total_amount' => $totalAmount]);
        return $order;
    }

    /**
     * Get order statistics
     *
     * @param int|null $userId
     * @return array
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = $this->getFreshQuery();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $baseQuery = clone $query;

        return [
            'total_orders' => $baseQuery->count(),
            'pending_orders' => (clone $query)->where('status', 'pending')->count(),
            'completed_orders' => (clone $query)->where('status', 'completed')->count(),
            'cancelled_orders' => (clone $query)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total_amount'),
            'average_order_value' => (clone $query)->avg('total_amount'),
            'highest_order_value' => (clone $query)->max('total_amount'),
            'lowest_order_value' => (clone $query)->min('total_amount'),
        ];
    }

    /**
     * Get monthly order statistics
     *
     * @param int $year
     * @param int|null $userId
     * @return array
     */
    public function getMonthlyStatistics(int $year, ?int $userId = null): array
    {
        $query = $this->getFreshQuery()
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total_orders, SUM(total_amount) as total_revenue')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get orders with filters
     *
     * @param array $filters
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15, array $columns = ['id', 'user_id', 'total_amount', 'status']): LengthAwarePaginator
    {
        $query = $this->getFreshQuery();

        // Apply filters
        if (!empty($filters)) {
            // User filter
            if (isset($filters['user_id']) && $filters['user_id']) {
                $query->where('user_id', $filters['user_id']);
            }

            // Status filter
            if (isset($filters['status']) && $filters['status']) {
                $query->where('status', $filters['status']);
            }

            // Amount range filter
            if (isset($filters['min_amount']) && $filters['min_amount']) {
                $query->where('total_amount', '>=', $filters['min_amount']);
            }

            if (isset($filters['max_amount']) && $filters['max_amount']) {
                $query->where('total_amount', '<=', $filters['max_amount']);
            }

            // Date range filter
            if (isset($filters['start_date']) && $filters['start_date']) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && $filters['end_date']) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDirection = $filters['sort_direction'] ?? 'desc';

            if (in_array($sortBy, ['total_amount', 'status', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
        }

        return $query->paginate($perPage, $columns);
    }

    /**
     * Get top customers
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        return $this->getFreshQuery()
            ->select('user_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(total_amount) as total_spent')
            ->groupBy('user_id')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get orders by product
     *
     * @param int $productId
     * @param array $columns
     * @return Collection
     */
    public function getByProduct(int $productId, array $columns = ['id', 'user_id', 'total_amount', 'status']): Collection
    {
        return $this->getFreshQuery()
            ->whereHas('products', function ($query) use ($productId) {
                $query->where('products.id', $productId);
            })
            ->get($columns);
    }

    /**
     * Calculate total revenue
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    public function calculateTotalRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->getFreshQuery()->where('status', 'completed');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return (float) $query->sum('total_amount');
    }
}

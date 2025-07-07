<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get orders by user ID
     *
     * @param int $userId
     * @param array $columns
     * @return Collection
     */
    public function getByUser(int $userId, array $columns = ['*']): Collection;

    /**
     * Get paginated orders by user ID
     *
     * @param int $userId
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getPaginatedByUser(int $userId, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Get orders by status
     *
     * @param string $status
     * @param array $columns
     * @return Collection
     */
    public function getByStatus(string $status, array $columns = ['*']): Collection;

    /**
     * Get orders by user and status
     *
     * @param int $userId
     * @param string $status
     * @param array $columns
     * @return Collection
     */
    public function getByUserAndStatus(int $userId, string $status, array $columns = ['*']): Collection;

    /**
     * Get orders with products
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithProducts(array $columns = ['*']): Collection;

    /**
     * Get orders with order products
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrderProducts(array $columns = ['*']): Collection;

    /**
     * Get orders within date range
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $columns
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate, array $columns = ['*']): Collection;

    /**
     * Get orders by total amount range
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @param array $columns
     * @return Collection
     */
    public function getByTotalAmountRange(float $minAmount, float $maxAmount, array $columns = ['*']): Collection;

    /**
     * Get recent orders
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecent(int $days = 7, array $columns = ['*']): Collection;

    /**
     * Get pending orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getPending(array $columns = ['*']): Collection;

    /**
     * Get completed orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getCompleted(array $columns = ['*']): Collection;

    /**
     * Get cancelled orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getCancelled(array $columns = ['*']): Collection;

    /**
     * Update order status
     *
     * @param int $orderId
     * @param string $status
     * @return Order
     */
    public function updateStatus(int $orderId, string $status): Order;

    /**
     * Update order total amount
     *
     * @param int $orderId
     * @param float $totalAmount
     * @return Order
     */
    public function updateTotalAmount(int $orderId, float $totalAmount): Order;

    /**
     * Get order statistics
     *
     * @param int|null $userId
     * @return array
     */
    public function getStatistics(?int $userId = null): array;

    /**
     * Get monthly order statistics
     *
     * @param int $year
     * @param int|null $userId
     * @return array
     */
    public function getMonthlyStatistics(int $year, ?int $userId = null): array;

    /**
     * Get orders with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get top customers by order value
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10): Collection;

    /**
     * Get orders by product
     *
     * @param int $productId
     * @param array $columns
     * @return Collection
     */
    public function getByProduct(int $productId, array $columns = ['*']): Collection;

    /**
     * Calculate total revenue
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    public function calculateTotalRevenue(?string $startDate = null, ?string $endDate = null): float;
}

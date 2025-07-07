<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search products using Laravel Scout
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get products with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get products in stock
     *
     * @param array $columns
     * @return Collection
     */
    public function getInStock(array $columns = ['*']): Collection;

    /**
     * Get products out of stock
     *
     * @param array $columns
     * @return Collection
     */
    public function getOutOfStock(array $columns = ['*']): Collection;

    /**
     * Get products by price range
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @param array $columns
     * @return Collection
     */
    public function getByPriceRange(float $minPrice, float $maxPrice, array $columns = ['*']): Collection;

    /**
     * Get products by name pattern
     *
     * @param string $name
     * @param array $columns
     * @return Collection
     */
    public function getByNamePattern(string $name, array $columns = ['*']): Collection;

    /**
     * Update product stock
     *
     * @param int $productId
     * @param int $quantity
     * @param string $operation (increment|decrement)
     * @return Product
     */
    public function updateStock(int $productId, int $quantity, string $operation = 'decrement'): Product;

    /**
     * Get low stock products
     *
     * @param int $threshold
     * @param array $columns
     * @return Collection
     */
    public function getLowStock(int $threshold = 10, array $columns = ['*']): Collection;

    /**
     * Get products with orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrders(array $columns = ['*']): Collection;

    /**
     * Get popular products (most ordered)
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getPopular(int $limit = 10, array $columns = ['*']): Collection;

    /**
     * Get recently added products
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecentlyAdded(int $days = 7, array $columns = ['*']): Collection;

    /**
     * Bulk update stock
     *
     * @param array $stockUpdates [['product_id' => 1, 'quantity' => 10], ...]
     * @return bool
     */
    public function bulkUpdateStock(array $stockUpdates): bool;

    /**
     * Get product statistics
     *
     * @return array
     */
    public function getStatistics(): array;
}

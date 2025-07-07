<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
	 /**
     * Get the model instance
     *
     * @return Model
     */
    protected function getModel(): Model
    {
        return new Product();
    }

    /**
     * Search products using Laravel Scout
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $searchQuery = Product::search($query);

        // Apply filters to Scout query
        if (!empty($filters)) {
            $searchQuery->where(function ($builder) use ($filters) {
                foreach ($filters as $field => $value) {
                    if ($value !== null) {
                        switch ($field) {
                            case 'min_price':
                                $builder->where('price', '>=', $value);
                                break;
                            case 'max_price':
                                $builder->where('price', '<=', $value);
                                break;
                            case 'in_stock':
                                if ($value) {
                                    $builder->where('stock', '>', 0);
                                }
                                break;
                            default:
                                $builder->where($field, $value);
                        }
                    }
                }
            });
        }

        return $searchQuery->paginate($perPage);
    }

    /**
     * Get products with filters
     *
     * @param array $filters
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function getWithFilters(array $filters = [], int $perPage = 15, array $columns = ['id', 'name', 'description', 'price', 'stock']): LengthAwarePaginator
    {
        $query = $this->getFreshQuery();

        // Apply filters
        if (!empty($filters)) {
            // Name filter
            if (isset($filters['name']) && $filters['name']) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            // Price range filter
            if (isset($filters['min_price']) && $filters['min_price']) {
                $query->where('price', '>=', $filters['min_price']);
            }

            if (isset($filters['max_price']) && $filters['max_price']) {
                $query->where('price', '<=', $filters['max_price']);
            }

            // Stock filter
            if (isset($filters['in_stock']) && $filters['in_stock']) {
                $query->where('stock', '>', 0);
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDirection = $filters['sort_direction'] ?? 'desc';

            if (in_array($sortBy, ['name', 'price', 'stock', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }
        }

        return $query->paginate($perPage, $columns);
    }

    /**
     * Get products in stock
     *
     * @param array $columns
     * @return Collection
     */
    public function getInStock(array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()->where('stock', '>', 0)->get($columns);
    }

    /**
     * Get products out of stock
     *
     * @param array $columns
     * @return Collection
     */
    public function getOutOfStock(array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()->where('stock', '=', 0)->get($columns);
    }

     /**
     * Get products by price range
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @param array $columns
     * @return Collection
     */
    public function getByPriceRange(float $minPrice, float $maxPrice, array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->get($columns);
    }

    /**
     * Get products by name pattern
     *
     * @param string $name
     * @param array $columns
     * @return Collection
     */
    public function getByNamePattern(string $name, array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->where('name', 'like', '%' . $name . '%')
            ->get($columns);
    }

    /**
     * Update product stock
     *
     * @param int $productId
     * @param int $quantity
     * @param string $operation
     * @return Product
     */
    public function updateStock(int $productId, int $quantity, string $operation = 'decrement'): Product
    {
        $product = $this->findOrFail($productId);

        if ($operation === 'increment') {
            $product->increment('stock', $quantity);
        } elseif ($operation === 'decrement') {
            $product->decrement('stock', $quantity);
        }

        return $product->fresh();
    }

     /**
     * Get low stock products
     *
     * @param int $threshold
     * @param array $columns
     * @return Collection
     */
    public function getLowStock(int $threshold = 10, array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->where('stock', '<=', $threshold)
            ->where('stock', '>', 0)
            ->get($columns);
    }

    /**
     * Get products with orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithOrders(array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->whereHas('orders')
            ->get($columns);
    }

    /**
     * Get products without orders
     *
     * @param array $columns
     * @return Collection
     */
    public function getWithoutOrders(array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->whereDoesntHave('orders')
            ->get($columns);
    }

    /**
     * Get most popular products (by order count)
     *
     * @param int $limit
     * @param array $columns
     * @return Collection
     */
    public function getPopular(int $limit = 10, array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->select($columns)
            ->selectRaw('COUNT(order_products.id) as orders_count')
            ->leftJoin('order_products', 'products.id', '=', 'order_products.product_id')
            ->groupBy('products.id')
            ->orderBy('orders_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently added products
     *
     * @param int $days
     * @param array $columns
     * @return Collection
     */
    public function getRecentlyAdded(int $days = 7, array $columns = ['id', 'name', 'description', 'price', 'stock']): Collection
    {
        return $this->getFreshQuery()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get($columns);
    }

    /**
     * Bulk update stock
     *
     * @param array $stockUpdates
     * @return bool
     */
    public function bulkUpdateStock(array $stockUpdates): bool
    {
        try {
            DB::beginTransaction();

            foreach ($stockUpdates as $update) {
                $product = $this->findOrFail($update['product_id']);
                $product->update(['stock' => $update['quantity']]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get product statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_products' => $this->count(),
            'in_stock_products' => $this->getFreshQuery()->where('stock', '>', 0)->count(),
            'out_of_stock_products' => $this->getFreshQuery()->where('stock', '=', 0)->count(),
            'low_stock_products' => $this->getFreshQuery()->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'average_price' => $this->getFreshQuery()->avg('price'),
            'total_stock_value' => $this->getFreshQuery()->sum(DB::raw('price * stock')),
            'highest_price' => $this->getFreshQuery()->max('price'),
            'lowest_price' => $this->getFreshQuery()->min('price'),
            'total_stock_quantity' => $this->getFreshQuery()->sum('stock'),
        ];
    }
}

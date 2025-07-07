<?php
namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\CacheService;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
	/**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * ProductService constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get all products with filters, search, and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Create cache key
        $cacheKey = CacheService::generateProductsCacheKey([
            'filters' => $filters,
            'per_page' => $perPage,
            'page' => request()->get('page', 1)
        ]);

        // If search query is provided, use Scout search
        if (isset($filters['search']) && !empty($filters['search'])) {
            return $this->searchProducts($filters, $perPage);
        }

        // Cache regular filters
        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($filters, $perPage) {
                return $this->productRepository->getWithFilters($filters, $perPage);
            },
            ['products']
        );
    }

    /**
     * Search products using Scout with caching
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $filters['search'] ?? '';

        // Create cache key for search
        $cacheKey = CacheService::generateProductsCacheKey([
            'search' => $query,
            'filters' => $filters,
            'per_page' => $perPage,
            'page' => request()->get('page', 1)
        ]);

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.search.ttl', 300), // 5 minutes
            function () use ($query, $filters, $perPage) {
                return $this->productRepository->searchWithFilters($query, $filters, $perPage);
            },
            ['search', 'products']
        );
    }

    /**
     * Get product by ID with caching
     *
     * @param int $productId
     * @return Product
     */
    public function getProductById(int $productId): Product
    {
        $cacheKey = "product:{$productId}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($productId) {
                return $this->productRepository->findOrFail($productId);
            },
            ['products']
        );
    }

    /**
     * Create a new product
     *
     * @param array $data
     * @return Product
     */
    public function createProduct(array $data): Product
    {
        return $this->productRepository->create($data);
    }

    /**
     * Update an existing product
     *
     * @param int $productId
     * @param array $data
     * @return Product
     */
    public function updateProduct(int $productId, array $data): Product
    {
        $product = $this->productRepository->findOrFail($productId);

        return $this->productRepository->update($productId, $data);
    }

    /**
     * Delete a product
     *
     * @param int $productId
     * @return bool
     */
    public function deleteProduct(int $productId): bool
    {
        $product = $this->productRepository->findOrFail($productId);

        return $this->productRepository->delete($productId);
    }

    /**
     * Get products with low stock (cached)
     *
     * @param int $threshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts(int $threshold = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:low_stock:{$threshold}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($threshold) {
                return $this->productRepository->getLowStock($threshold);
            },
            ['products']
        );
    }

    /**
     * Get products by price range (cached)
     *
     * @param float $minPrice
     * @param float $maxPrice
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsByPriceRange(float $minPrice, float $maxPrice): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:price_range:{$minPrice}:{$maxPrice}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($minPrice, $maxPrice) {
                return $this->productRepository->getByPriceRange($minPrice, $maxPrice);
            },
            ['products']
        );
    }

    /**
     * Get popular products (cached)
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularProducts(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:popular:{$limit}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($limit) {
                return $this->productRepository->getPopular($limit);
            },
            ['products']
        );
    }

    /**
     * Get featured products (cached)
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFeaturedProducts(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:featured:{$limit}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($limit) {
                return $this->productRepository->getFeatured($limit);
            },
            ['products']
        );
    }

    /**
     * Get products statistics (cached)
     *
     * @return array
     */
    public function getProductsStatistics(): array
    {
        $cacheKey = "products:statistics";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () {
                return $this->productRepository->getStatistics();
            },
            ['products']
        );
    }

    /**
     * Update product stock
     *
     * @param int $productId
     * @param int $quantity
     * @return Product
     */
    public function updateProductStock(int $productId, int $quantity): Product
    {
        return $this->productRepository->updateStock($productId, $quantity);
    }

    /**
     * Increase product stock
     *
     * @param int $productId
     * @param int $quantity
     * @return Product
     */
    public function increaseProductStock(int $productId, int $quantity): Product
    {
        return $this->productRepository->increaseStock($productId, $quantity);
    }

    /**
     * Decrease product stock
     *
     * @param int $productId
     * @param int $quantity
     * @return Product
     */
    public function decreaseProductStock(int $productId, int $quantity): Product
    {
        return $this->productRepository->decreaseStock($productId, $quantity);
    }

    /**
     * Check if product has sufficient stock
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function hasStock(int $productId, int $quantity): bool
    {
        return $this->productRepository->hasStock($productId, $quantity);
    }

    /**
     * Get products by category (cached)
     *
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:category:{$category}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($category) {
                return $this->productRepository->getByCategory($category);
            },
            ['products']
        );
    }

    /**
     * Get in-stock products (cached)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:in_stock";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () {
                return $this->productRepository->getInStock();
            },
            ['products']
        );
    }

    /**
     * Get out-of-stock products (cached)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:out_of_stock";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () {
                return $this->productRepository->getOutOfStock();
            },
            ['products']
        );
    }

    /**
     * Get products with filters for API response
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductsForApi(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAllProducts($filters, $perPage);
    }

    /**
     * Get single product for API response
     *
     * @param int $productId
     * @return ProductResource
     */
    public function getProductForApi(int $productId): ProductResource
    {
        $product = $this->getProductById($productId);

        return new ProductResource($product);
    }

    /**
     * Bulk update products
     *
     * @param array $updates
     * @return bool
     */
    public function bulkUpdate(array $updates): bool
    {
        return $this->productRepository->bulkUpdate($updates);
    }

    /**
     * Get recently added products (cached)
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentlyAddedProducts(int $days = 7, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:recent:{$days}:{$limit}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($days, $limit) {
                return $this->productRepository->getRecentlyAdded($days, $limit);
            },
            ['products']
        );
    }

    /**
     * Get products by name pattern (cached)
     *
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsByName(string $name): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "products:name:" . md5($name);

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.products.ttl', 600), // 10 minutes
            function () use ($name) {
                return $this->productRepository->getByNamePattern($name);
            },
            ['products']
        );
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics(): array
    {
        return CacheService::getCacheStats();
    }

    /**
     * Validate productRepository data.
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function save(array $data)
    {
        return $this->productRepository->save($data);
    }

    /**
     * Update productRepository data
     * Store to DB if there are no errors.
     *
     * @param array $data
     * @return String
     */
    public function update(array $data, int $id)
    {
        DB::beginTransaction();
        try {
            $productRepository = $this->productRepository->update($data, $id);
            DB::commit();
            return $productRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to update post data');
        }
    }

    /**
     * Delete productRepository by id.
     *
     * @param $id
     * @return String
     */
    public function deleteById(int $id)
    {
        DB::beginTransaction();
        try {
            $productRepository = $this->productRepository->delete($id);
            DB::commit();
            return $productRepository;
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            throw new InvalidArgumentException('Unable to delete post data');
        }
    }
}

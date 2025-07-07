<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseBuilder;
use App\Services\ProductService;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * ProductController constructor.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'name',
                'category',
                'min_price',
                'max_price',
                'min_stock',
                'max_stock',
                'sort_by',
                'sort_direction',
                'in_stock',
                'featured'
            ]);

            $perPage = min($request->get('per_page', 2000), 2000);

            $products = $this->productService->getProductsForApi($filters, $perPage);


            return ResponseBuilder::paginated($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve products');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return ResponseBuilder::created(
                $this->productService->getProductForApi($product->id),
                'Product created successfully'
            );
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to create product');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductForApi($id);

            return ResponseBuilder::success($product, 'Product retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve product');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $this->productService->updateProduct($id, $request->validated());

            return ResponseBuilder::updated(
                $this->productService->getProductForApi($id),
                'Product updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update product');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);

            return ResponseBuilder::deleted('Product deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to delete product');
        }
    }

    /**
     * Search products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:255',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            $filters = $request->only([
                'search',
                'category',
                'min_price',
                'max_price',
                'min_stock',
                'max_stock',
                'sort_by',
                'sort_direction',
                'in_stock',
                'featured'
            ]);

            $filters['search'] = $request->get('query');
            $perPage = min($request->get('per_page', 15), 100);

            $products = $this->productService->getProductsForApi($filters, $perPage);

            return ResponseBuilder::paginated($products, 'Search results retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to search products');
        }
    }

    /**
     * Get low stock products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'threshold' => 'sometimes|integer|min:0|max:100'
            ]);

            $threshold = $request->get('threshold', 10);
            $products = $this->productService->getLowStockProducts($threshold);

            return ResponseBuilder::collection($products, 'Low stock products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve low stock products');
        }
    }

    /**
     * Get popular products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'sometimes|integer|min:1|max:50'
            ]);

            $limit = $request->get('limit', 10);
            $products = $this->productService->getPopularProducts($limit);

            return ResponseBuilder::collection($products, 'Popular products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve popular products');
        }
    }

    /**
     * Get featured products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'sometimes|integer|min:1|max:50'
            ]);

            $limit = $request->get('limit', 10);
            $products = $this->productService->getFeaturedProducts($limit);

            return ResponseBuilder::collection($products, 'Featured products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve featured products');
        }
    }

    /**
     * Get products statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->productService->getProductsStatistics();

            return ResponseBuilder::success($statistics, 'Products statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve products statistics');
        }
    }

    /**
     * Get recently added products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'sometimes|integer|min:1|max:365',
                'limit' => 'sometimes|integer|min:1|max:50'
            ]);

            $days = $request->get('days', 7);
            $limit = $request->get('limit', 10);

            $products = $this->productService->getRecentlyAddedProducts($days, $limit);

            return ResponseBuilder::collection($products, 'Recently added products retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve recently added products');
        }
    }

    /**
     * Update product stock.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStock(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'stock' => 'required|integer|min:0'
            ]);

            $this->productService->updateProductStock($id, $request->get('stock'));

            return ResponseBuilder::updated(
                $this->productService->getProductForApi($id),
                'Product stock updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Product not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update product stock');
        }
    }

    /**
     * Bulk update products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'updates' => 'required|array',
                'updates.*.id' => 'required|integer|exists:products,id',
                'updates.*.data' => 'required|array'
            ]);

            $result = $this->productService->bulkUpdate($request->get('updates'));

            if ($result) {
                return ResponseBuilder::success([], 'Products updated successfully');
            } else {
                return ResponseBuilder::error('Failed to update products');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to bulk update products');
        }
    }

    /**
     * Get in-stock products.
     *
     * @return JsonResponse
     */
    public function inStock(): JsonResponse
    {
        try {
            $products = $this->productService->getInStockProducts();

            return ResponseBuilder::collection($products, 'In-stock products retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve in-stock products');
        }
    }

    /**
     * Get out-of-stock products.
     *
     * @return JsonResponse
     */
    public function outOfStock(): JsonResponse
    {
        try {
            $products = $this->productService->getOutOfStockProducts();

            return ResponseBuilder::collection($products, 'Out-of-stock products retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve out-of-stock products');
        }
    }

    /**
     * Get cache statistics.
     *
     * @return JsonResponse
     */
    public function cacheStatistics(): JsonResponse
    {
        try {
            $statistics = $this->productService->getCacheStatistics();

            return ResponseBuilder::success($statistics, 'Cache statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve cache statistics');
        }
    }
}

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
        // Add auth middleware only to protected routes
        $this->middleware('auth:sanctum')->only([
            'store',
            'update',
            'destroy',
            'updateStock',
            'bulkUpdate',
            'statistics',
            'cacheStatistics'
        ]);
        // Add admin middleware to admin-only routes
        $this->middleware('admin')->only([
            'statistics',
            'cacheStatistics',
            'bulkUpdate'
        ]);
    }

    /**
     * Display a listing of products.
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

            $perPage = min($request->get('per_page', 15), 100);
            $products = $this->productService->getProductsForApi($filters, $perPage);

            return ResponseBuilder::paginated($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve products');
        }
    }

    /**
     * Store a newly created product in storage.
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
     * Display the specified product.
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
     * Update the specified product in storage.
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
     * Remove the specified product from storage.
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

            // Sanitize search query
            $searchQuery = strip_tags(trim($request->get('query')));

            $filters = array_merge(
                $request->only([
                    'category',
                    'min_price',
                    'max_price',
                    'min_stock',
                    'max_stock',
                    'sort_by',
                    'sort_direction',
                    'in_stock',
                    'featured'
                ]),
                ['search' => $searchQuery]
            );

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
     * Get product statistics (admin only).
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->productService->getProductStatistics();
            return ResponseBuilder::success($statistics, 'Product statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve product statistics');
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
                'stock' => 'required|integer|min:0',
                'reason' => 'required|string|max:255'
            ]);

            $data = [
                'stock' => $request->get('stock'),
                'reason' => $request->get('reason')
            ];

            $product = $this->productService->updateProductStock($id, $data);

            return ResponseBuilder::updated(
                $this->productService->getProductForApi($product->id),
                'Product stock updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update product stock');
        }
    }

    /**
     * Bulk update products (admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'products' => 'required|array|min:1|max:100',
                'products.*.id' => 'required|integer|exists:products,id',
                'products.*.stock' => 'sometimes|integer|min:0',
                'products.*.price' => 'sometimes|numeric|min:0.01',
                'products.*.featured' => 'sometimes|boolean'
            ]);

            $this->productService->bulkUpdateProducts($request->get('products'));

            return ResponseBuilder::success(null, 'Products updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update products');
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
     * Get cache statistics (admin only).
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

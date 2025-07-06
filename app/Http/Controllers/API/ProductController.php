<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * @var ProductService
     */
    protected ProductService $productService;

    /**
     * ProductController Constructor
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the products with search, filters, and pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // Generate cache key based on request parameters
        $cacheKey = CacheService::generateProductsCacheKey($request->all());

        // Use configured cache TTL
        $cacheTtl = config('cache.custom.products.ttl');

        $products = CacheService::remember($cacheKey, $cacheTtl, function () use ($request) {

            // Use Laravel Scout search if search parameter is provided
            if ($request->filled('search')) {
                $searchQuery = $request->get('search');
                $query = Product::search($searchQuery);

                // Apply additional filters to Scout query
                if ($request->filled('min_price') || $request->filled('max_price')) {
                    $query->where(function ($builder) use ($request) {
                        if ($request->filled('min_price')) {
                            $builder->where('price', '>=', $request->get('min_price'));
                        }
                        if ($request->filled('max_price')) {
                            $builder->where('price', '<=', $request->get('max_price'));
                        }
                    });
                }

                // Get paginated results from Scout
                $perPage = min($request->get('per_page', 15), 100);
                return $query->paginate($perPage);

            } else {
                // Use regular Eloquent query for non-search requests
                $query = Product::query();

                // Name filter using LIKE
                if ($request->filled('name')) {
                    $query->where('name', 'like', '%' . $request->get('name') . '%');
                }

                // Price range filter
                if ($request->filled('min_price')) {
                    $query->where('price', '>=', $request->get('min_price'));
                }

                if ($request->filled('max_price')) {
                    $query->where('price', '<=', $request->get('max_price'));
                }

                // Stock filter
                if ($request->filled('in_stock')) {
                    $inStock = filter_var($request->get('in_stock'), FILTER_VALIDATE_BOOLEAN);
                    if ($inStock) {
                        $query->where('stock', '>', 0);
                    }
                }

                // Sorting
                $sortBy = $request->get('sort_by', 'created_at');
                $sortDirection = $request->get('sort_direction', 'desc');

                if (in_array($sortBy, ['name', 'price', 'stock', 'created_at'])) {
                    $query->orderBy($sortBy, $sortDirection);
                }

                // Pagination
                $perPage = min($request->get('per_page', 15), 100);

                return $query->paginate($perPage);
            }
        }, ['products', 'search']);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param StoreProductRequest $request
     * @return ProductResource|\Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request): ProductResource|\Illuminate\Http\JsonResponse
    {
        try {
            $product = $this->productService->save($request->validated());

            // Cache will be cleared automatically by ProductObserver
            // Scout index will be updated automatically

            return new ProductResource($product);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified product.
     *
     * @param int $id
     * @return ProductResource
     */
    public function show(int $id): ProductResource
    {
        return ProductResource::make($this->productService->getById($id));
    }

    /**
     * Update the specified product in storage.
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return ProductResource|\Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): ProductResource|\Illuminate\Http\JsonResponse
    {
        try {
            $product = $this->productService->update($request->validated(), $id);

            // Cache will be cleared automatically by ProductObserver
            // Scout index will be updated automatically

            return new ProductResource($product);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->productService->deleteById($id);

            // Cache will be cleared automatically by ProductObserver
            // Scout index will be updated automatically

            return response()->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

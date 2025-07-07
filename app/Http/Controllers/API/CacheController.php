<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseBuilder;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    /**
     * Get cache statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = CacheService::getCacheStats();

            return ResponseBuilder::success($statistics, 'Cache statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve cache statistics');
        }
    }

    /**
     * Clear cache by tags.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearByTags(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tags' => 'required|array',
                'tags.*' => 'string|in:products,search,orders'
            ]);

            $tags = $request->get('tags');
            CacheService::clearCacheByTags($tags);

            return ResponseBuilder::success([], 'Cache cleared successfully for tags: ' . implode(', ', $tags));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to clear cache');
        }
    }

    /**
     * Clear all cache.
     *
     * @return JsonResponse
     */
    public function clearAll(): JsonResponse
    {
        try {
            CacheService::clearCacheByTags(['products', 'search', 'orders']);

            return ResponseBuilder::success([], 'All cache cleared successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to clear all cache');
        }
    }

    /**
     * Clear products cache.
     *
     * @return JsonResponse
     */
    public function clearProducts(): JsonResponse
    {
        try {
            CacheService::clearCacheByTags(['products']);

            return ResponseBuilder::success([], 'Products cache cleared successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to clear products cache');
        }
    }

    /**
     * Clear search cache.
     *
     * @return JsonResponse
     */
    public function clearSearch(): JsonResponse
    {
        try {
            CacheService::clearCacheByTags(['search']);

            return ResponseBuilder::success([], 'Search cache cleared successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to clear search cache');
        }
    }

    /**
     * Clear orders cache.
     *
     * @return JsonResponse
     */
    public function clearOrders(): JsonResponse
    {
        try {
            CacheService::clearCacheByTags(['orders']);

            return ResponseBuilder::success([], 'Orders cache cleared successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to clear orders cache');
        }
    }

    /**
     * Warm up cache.
     *
     * @return JsonResponse
     */
    public function warmUp(): JsonResponse
    {
        try {
            // This is a simple warm-up example
            // You can expand this to warm up specific cache keys
            $statistics = [
                'message' => 'Cache warm-up completed',
                'timestamp' => now()->toDateTimeString(),
                'cache_stats' => CacheService::getCacheStats()
            ];

            return ResponseBuilder::success($statistics, 'Cache warm-up completed successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to warm up cache');
        }
    }

    /**
     * Get cache info.
     *
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        try {
            $info = [
                'cache_driver' => config('cache.default'),
                'cache_store' => get_class(cache()->getStore()),
                'redis_connected' => cache()->getStore() instanceof \Illuminate\Cache\RedisStore,
                'tags_supported' => cache()->getStore() instanceof \Illuminate\Cache\TaggedCache,
                'cache_prefixes' => [
                    'products' => config('cache.custom.products.prefix', 'products:'),
                    'search' => config('cache.custom.search.prefix', 'search:'),
                    'orders' => config('cache.custom.orders.prefix', 'orders:'),
                ],
                'cache_ttls' => [
                    'products' => config('cache.custom.products.ttl', 600),
                    'search' => config('cache.custom.search.ttl', 300),
                    'orders' => config('cache.custom.orders.ttl', 300),
                ],
            ];

            return ResponseBuilder::success($info, 'Cache info retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve cache info');
        }
    }
}
